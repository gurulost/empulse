<?php

namespace App\Http\Controllers;

use App\Services\SurveyAnalyticsService;

class ReportController extends Controller
{
    protected SurveyAnalyticsService $analytics;

    public function __construct(SurveyAnalyticsService $analytics)
    {
        $this->middleware('auth');
        $this->analytics = $analytics;
    }

    public function index()
    {
        return view('reports.dashboard');
    }
}
