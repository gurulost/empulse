<?php

namespace App\Http\Controllers;

use App\Services\SurveyAnalyticsService;
use App\Services\SurveyService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SurveyManagementController extends Controller
{
    protected SurveyService $surveyService;
    protected SurveyAnalyticsService $analyticsService;

    public function __construct(SurveyService $surveyService, SurveyAnalyticsService $analyticsService)
    {
        $this->middleware(['auth', 'admin']);
        $this->surveyService = $surveyService;
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $companyId = (int) ($request->user()->company_id ?? 0);
        $survey = $this->surveyService->defaultSurvey();
        $assignmentQuery = null;

        if ($survey) {
            $assignmentQuery = $survey->assignments()
                ->with(['user', 'response'])
                ->whereHas('user', function ($query) use ($companyId) {
                    if ($companyId > 0) {
                        $query->where('company_id', $companyId);
                        return;
                    }

                    // Fail closed if a manager is not attached to a company.
                    $query->whereNull('id');
                });

            $assignments = (clone $assignmentQuery)
                ->orderByDesc('updated_at')
                ->paginate(10);
        } else {
            $assignments = new LengthAwarePaginator([], 0, 10);
        }

        $analytics = null;
        $completedResponsesCount = 0;
        if ($companyId > 0) {
            $analytics = $this->analyticsService->companyDashboardAnalytics([
                'company_id' => $companyId,
            ]);

            $completedResponsesCount = $assignmentQuery
                ? (clone $assignmentQuery)->where('status', 'completed')->count()
                : 0;
        }

        return view('surveys.manage', [
            'survey' => $survey,
            'assignments' => $assignments,
            'analytics' => $analytics,
            'completedResponsesCount' => $completedResponsesCount,
        ]);
    }
}
