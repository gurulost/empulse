<?php

namespace App\Http\Controllers;

use App\Services\OnboardingTelemetryService;
use App\Services\SurveyAnalyticsService;
use App\Services\SurveyService;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SurveyManagementController extends Controller
{
    protected SurveyService $surveyService;
    protected SurveyAnalyticsService $analyticsService;
    protected OnboardingTelemetryService $telemetry;

    public function __construct(
        SurveyService $surveyService,
        SurveyAnalyticsService $analyticsService,
        OnboardingTelemetryService $telemetry
    )
    {
        $this->middleware(['auth', 'admin']);
        $this->surveyService = $surveyService;
        $this->analyticsService = $analyticsService;
        $this->telemetry = $telemetry;
    }

    public function index(Request $request)
    {
        $companyId = (int) ($request->user()->company_id ?? 0);
        $hasCompanyContext = $companyId > 0;
        $survey = $this->surveyService->defaultSurvey();
        $activeVersion = SurveyVersion::query()
            ->with([
                'pages' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'pages.sections' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'pages.items',
                'pages.sections.items',
            ])
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
        $latestWave = $hasCompanyContext
            ? SurveyWave::query()
                ->with('surveyVersion')
                ->where('company_id', $companyId)
                ->orderByRaw('COALESCE(opens_at, due_at, created_at) DESC')
                ->orderByDesc('id')
                ->first()
            : null;
        $assignmentQuery = null;

        if ($survey && $hasCompanyContext) {
            $assignmentQuery = $survey->assignments()
                ->with(['user', 'response'])
                ->whereHas('user', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                });

            $assignments = (clone $assignmentQuery)
                ->orderByDesc('updated_at')
                ->paginate(10);
        } else {
            $assignments = new LengthAwarePaginator([], 0, 10);
        }

        $analytics = null;
        $completedResponsesCount = 0;
        if ($hasCompanyContext) {
            $analytics = $this->analyticsService->companyDashboardAnalytics([
                'company_id' => $companyId,
            ]);

            $completedResponsesCount = $assignmentQuery
                ? (clone $assignmentQuery)->where('status', 'completed')->count()
                : 0;
        }

        if ($hasCompanyContext && !$activeVersion) {
            $this->telemetry->record([
                'company_id' => $companyId,
                'name' => 'survey_activation_handoff_viewed',
                'context_surface' => 'surveys.manage',
                'task_id' => 'survey_activation',
                'user_segment' => 'novice',
                'guidance_level' => 'light',
                'properties' => [
                    'surface' => 'surveys.manage',
                    'has_live_survey' => false,
                ],
            ], $request->user());
        }

        $summary = [
            'pages' => $activeVersion?->pages?->count() ?? 0,
            'sections' => $activeVersion
                ? $activeVersion->pages->sum(fn ($page) => $page->sections->count())
                : 0,
            'items' => $activeVersion
                ? $activeVersion->pages->sum(fn ($page) => $page->items->count() + $page->sections->sum(fn ($section) => $section->items->count()))
                : 0,
            'assignments' => $assignmentQuery ? (clone $assignmentQuery)->count() : 0,
            'completed' => $completedResponsesCount,
            'pending' => $assignmentQuery ? (clone $assignmentQuery)->where('status', '!=', 'completed')->count() : 0,
        ];

        return view('surveys.manage', [
            'survey' => $survey,
            'activeVersion' => $activeVersion,
            'latestWave' => $latestWave,
            'assignments' => $assignments,
            'analytics' => $analytics,
            'completedResponsesCount' => $completedResponsesCount,
            'hasCompanyContext' => $hasCompanyContext,
            'summary' => $summary,
        ]);
    }
}
