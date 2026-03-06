<?php

namespace App\Http\Controllers;

use App\Models\SurveyAssignment;
use App\Services\SurveyDefinitionService;
use App\Services\OnboardingTelemetryService;
use App\Services\SurveyResponseValidationService;
use App\Services\SurveyService;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    protected SurveyService $surveyService;
    protected SurveyDefinitionService $definitionService;
    protected SurveyResponseValidationService $validationService;
    protected OnboardingTelemetryService $telemetry;

    public function __construct(
        SurveyService $surveyService,
        SurveyDefinitionService $definitionService,
        SurveyResponseValidationService $validationService,
        OnboardingTelemetryService $telemetry
    )
    {
        $this->surveyService = $surveyService;
        $this->definitionService = $definitionService;
        $this->validationService = $validationService;
        $this->telemetry = $telemetry;
    }

    public function show(string $token)
    {
        $assignment = SurveyAssignment::where('token', $token)->with(['survey.questions', 'user'])->firstOrFail();

        if ($assignment->status === 'completed' && $assignment->response) {
            return view('surveys.thank_you', [
                'alreadyCompleted' => true,
                'user' => $assignment->user,
            ]);
        }

        $surveyMeta = $this->definitionService->surveyMetaForAssignment($assignment);
        $this->recordEmployeeSurveyEntryView($assignment, $surveyMeta);

        return view('surveys.show', [
            'assignment' => $assignment,
        ]);
    }

    public function definition(string $token)
    {
        $assignment = SurveyAssignment::where('token', $token)
            ->with(['user', 'surveyVersion'])
            ->firstOrFail();

        $definition = $this->definitionService->definitionForAssignment($assignment);
        return response()->json($definition);
    }

    public function autosave(Request $request, string $token)
    {
        $assignment = SurveyAssignment::where('token', $token)->firstOrFail();
        if ($assignment->status === 'completed') {
            return response()->json(['status' => 'completed'], 409);
        }

        $data = $request->validate([
            'responses' => 'nullable|array|max:500',
        ]);

        $assignment->update([
            'draft_answers' => $data['responses'] ?? [],
            'last_autosaved_at' => now(),
        ]);

        return response()->json([
            'status' => 'ok',
            'last_autosaved_at' => optional($assignment->last_autosaved_at)->toIso8601String(),
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $assignment = SurveyAssignment::where('token', $token)
            ->with(['surveyVersion.pages.sections.items', 'surveyVersion.pages.items'])
            ->firstOrFail();

        if ($assignment->status === 'completed') {
            return response()->json(['status' => 'completed'], 409);
        }

        $data = $request->validate([
            'responses' => 'required|array|max:500',
            'duration_ms' => 'nullable|integer|min:0|max:86400000',
        ]);

        $responses = $data['responses'];
        if (config('survey.validation.strict_server_validation', false)) {
            $responses = $this->validationService->validateAndSanitize($assignment, $responses);
        }

        $this->surveyService->recordResponse($assignment, $responses, [
            'duration_ms' => $data['duration_ms'] ?? null,
        ]);

        return response()->json(['status' => 'ok']);
    }

    protected function recordEmployeeSurveyEntryView(SurveyAssignment $assignment, array $surveyMeta): void
    {
        $assignment->loadMissing('user');

        if ((int) ($assignment->user?->role ?? 0) !== 4) {
            return;
        }

        $this->telemetry->record([
            'user_id' => $assignment->user_id,
            'company_id' => $assignment->user?->company_id,
            'name' => 'employee_survey_entry_viewed',
            'context_surface' => 'survey.take',
            'task_id' => 'survey_take',
            'user_segment' => 'employee',
            'guidance_level' => 'light',
            'properties' => [
                'assignment_id' => $assignment->id,
                'survey_version_id' => $assignment->survey_version_id,
                'question_count' => $surveyMeta['question_count'] ?? null,
                'estimated_minutes' => $surveyMeta['estimated_minutes'] ?? null,
            ],
        ], $assignment->user);
    }
}
