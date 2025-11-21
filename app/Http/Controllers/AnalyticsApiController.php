<?php

namespace App\Http\Controllers;

use App\Services\SurveyAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsApiController extends Controller
{
    protected SurveyAnalyticsService $analytics;

    public function __construct(SurveyAnalyticsService $analytics)
    {
        $this->middleware('auth');
        $this->analytics = $analytics;
    }

    public function index(Request $request)
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
        
        // Also fetch available filter options
        $exist_departments = \DB::table('company_department')->where('company_id', $companyId)->pluck('title')->toArray();
        $departments = \DB::table('company_worker')
            ->where([["company_id", '=', $companyId], ["department", "!=", NULL], ["department", "!=", ""]])
            ->select('department')
            ->distinct()
            ->get();
            
        $teamleads = \DB::table('company_worker')
            ->where(["company_id" => $companyId, "role" => 3])
            ->select('name')
            ->distinct()
            ->get();

        $waves = $this->analytics->availableWavesForCompany($companyId);

        return response()->json([
            'data' => $data,
            'filters' => [
                'departments' => $departments,
                'teamleads' => $teamleads,
                'waves' => $waves,
                'exist_departments' => $exist_departments
            ]
        ]);
    }
}
