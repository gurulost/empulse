<?php

namespace App\Http\Controllers;

use App\Models\SurveyAssignment;
use App\Services\OnboardingTelemetryService;
use App\Services\PrivacyGovernanceService;
use App\Services\SurveyAssignmentAccessService;
use App\Services\SurveyDefinitionService;
use App\Services\SurveyResponseValidationService;
use App\Services\SurveyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    protected SurveyService $surveyService;

    protected SurveyDefinitionService $definitionService;

    protected SurveyResponseValidationService $validationService;

    protected SurveyAssignmentAccessService $accessService;

    protected OnboardingTelemetryService $telemetry;

    protected PrivacyGovernanceService $privacy;

    public function __construct(
        SurveyService $surveyService,
        SurveyDefinitionService $definitionService,
        SurveyResponseValidationService $validationService,
        SurveyAssignmentAccessService $accessService,
        OnboardingTelemetryService $telemetry,
        PrivacyGovernanceService $privacy
    ) {
        $this->surveyService = $surveyService;
        $this->definitionService = $definitionService;
        $this->validationService = $validationService;
        $this->accessService = $accessService;
        $this->telemetry = $telemetry;
        $this->privacy = $privacy;
    }

    public function show(string $token)
    {
        $assignment = $this->accessService->resolve($token);
        $assignment->loadMissing(['survey.questions', 'user']);

        $surveyMeta = $this->definitionService->surveyMetaForAssignment($assignment);
        $this->recordEmployeeSurveyEntryView($assignment, $surveyMeta);

        return view('surveys.show', [
            'assignment' => $assignment,
            'accessToken' => $token,
        ]);
    }

    public function definition(string $token)
    {
        $assignment = $this->accessService->resolve($token);

        $definition = $this->definitionService->definitionForAssignment($assignment);

        return response()->json($definition);
    }

    public function autosave(Request $request, string $token)
    {
        $assignment = $this->accessService->resolve($token);

        if (strlen($request->getContent()) > 262144) {
            abort(413, 'Autosave payload is too large.');
        }

        $data = $request->validate([
            'responses' => 'nullable|array|max:500',
            'revision' => 'required|integer|min:0',
        ]);

        $responses = $this->validationService->validateAndSanitize(
            $assignment,
            $data['responses'] ?? [],
            false
        );

        $savedAt = now();
        $updated = SurveyAssignment::query()
            ->whereKey($assignment->id)
            ->where('status', 'pending')
            ->where('draft_revision', $data['revision'])
            ->update([
                'draft_answers' => $responses,
                'last_autosaved_at' => $savedAt,
                'draft_revision' => DB::raw('draft_revision + 1'),
                'updated_at' => $savedAt,
            ]);

        if ($updated !== 1) {
            return response()->json([
                'status' => 'conflict',
                'message' => 'A newer draft exists. Reload before saving again.',
                'revision' => (int) $assignment->fresh()->draft_revision,
            ], 409);
        }

        return response()->json([
            'status' => 'ok',
            'last_autosaved_at' => $savedAt->toIso8601String(),
            'revision' => (int) $data['revision'] + 1,
        ]);
    }

    public function acknowledgePrivacy(Request $request, string $token)
    {
        $assignment = $this->accessService->resolve($token);
        $request->validate(['accepted' => 'required|accepted']);
        $acknowledgment = $this->privacy->acknowledge($assignment);

        return response()->json([
            'status' => 'ok',
            'policy_version' => $acknowledgment->policy_version,
            'acknowledged_at' => $acknowledgment->acknowledged_at->toIso8601String(),
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $assignment = $this->accessService->resolve($token);
        $assignment->loadMissing([
            'surveyVersion.pages.sections.items',
            'surveyVersion.pages.items',
        ]);

        $data = $request->validate([
            'responses' => 'required|array|max:500',
            'duration_ms' => 'nullable|integer|min:0|max:86400000',
        ]);

        if (! $assignment->privacy_acknowledged_at
            || $assignment->privacy_policy_version !== config('privacy.policy.version')) {
            return response()->json([
                'message' => 'Please review and accept the current respondent data promise before submitting.',
            ], 428);
        }

        $responses = $this->validationService->validateAndSanitize(
            $assignment,
            $data['responses']
        );

        try {
            $this->surveyService->recordResponse($assignment, $responses, [
                'duration_ms' => $data['duration_ms'] ?? null,
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'status' => 'conflict',
                'message' => $exception->getMessage(),
            ], 409);
        }

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
