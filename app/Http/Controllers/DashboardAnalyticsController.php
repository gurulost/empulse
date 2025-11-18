<?php

namespace App\Http\Controllers;

use App\Services\SurveyAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardAnalyticsController extends Controller
{
    protected SurveyAnalyticsService $analytics;

    public function __construct(SurveyAnalyticsService $analytics)
    {
        $this->middleware('auth');
        $this->analytics = $analytics;
    }

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $companyId = $request->integer('company_id') ?: $user->company_id;

        if (!$companyId) {
            return response()->json(['message' => 'Company is required.'], 422);
        }

        // Allow admins (role 0) to fetch any company. Everyone else limited to their own company.
        if ($user->role !== 0 && (int) $user->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $filters = [
            'company_id' => $companyId,
            'department' => $request->input('department'),
            'team' => $request->input('team'),
            'wave' => $request->input('wave'),
        ];

        $data = $this->analytics->companyDashboardAnalytics($filters);

        return response()->json([
            'data' => $data,
        ]);
    }
}
