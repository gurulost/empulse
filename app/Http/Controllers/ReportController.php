<?php

namespace App\Http\Controllers;

use App\Services\SurveyAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

