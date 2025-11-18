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
        $survey = $this->surveyService->defaultSurvey();
        if ($survey) {
            $assignments = $survey->assignments()
                ->with(['user', 'response'])
                ->orderByDesc('updated_at')
                ->paginate(10);
        } else {
            $assignments = new LengthAwarePaginator([], 0, 10);
        }

        $analytics = null;
        if ($request->user()->company_id) {
            $analytics = $this->analyticsService->datasetForCompany($request->user()->company_id);
        }

        return view('surveys.manage', [
            'survey' => $survey,
            'assignments' => $assignments,
            'analytics' => $analytics,
        ]);
    }
}
