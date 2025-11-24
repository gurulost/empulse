<?php

namespace App\Http\Controllers;

class DashboardAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function __invoke()
    {
        return response()->view('dashboard.analytics');
    }
}
