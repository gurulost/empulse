<?php

namespace App\Http\Controllers;

use App\Models\SurveyAssignment;
use App\Services\SurveyDefinitionService;
use App\Services\SurveyService;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    protected SurveyService $surveyService;
    protected SurveyDefinitionService $definitionService;

    public function __construct(SurveyService $surveyService, SurveyDefinitionService $definitionService)
    {
        $this->surveyService = $surveyService;
        $this->definitionService = $definitionService;
    }

    public function show(string $token)
    {
        $assignment = SurveyAssignment::where('token', $token)->with(['survey.questions', 'user'])->firstOrFail();
        $survey = $assignment->survey;

        if ($assignment->status === 'completed' && $assignment->response) {
            return view('surveys.thank_you', [
                'alreadyCompleted' => true,
                'user' => $assignment->user,
            ]);
        }

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
            'responses' => 'nullable|array',
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
            'responses' => 'required|array',
            'duration_ms' => 'nullable|integer|min:0',
        ]);

        $this->surveyService->recordResponse($assignment, $data['responses'], [
            'duration_ms' => $data['duration_ms'] ?? null,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
