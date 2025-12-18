<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
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
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isSuperAdmin = (int) $user->role === 0;

        if (!$isSuperAdmin && !$user->company_id) {
            return response()->json(['message' => 'User must be associated with a company.'], 422);
        }

        $companyId = $request->integer('company_id') ?: $user->company_id;

        if (!$companyId) {
            return response()->json(['message' => 'Company is required.'], 422);
        }

        // Authorization: Super Admins (role 0) can view any company
        // All other users can only view their own company
        $isOwnCompany = (int) $user->company_id === (int) $companyId;

        if (!$isSuperAdmin && !$isOwnCompany) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $filters = [
            'company_id' => $companyId,
            'department' => $request->input('department'),
            'team' => $request->input('team'),
            'wave' => $request->input('wave'),
        ];

        $data = $this->analytics->companyDashboardAnalytics($filters);
        
        // Fetch available filter options - all scoped to authorized company
        $exist_departments = \DB::table('company_department')
            ->where('company_id', $companyId)
            ->pluck('title')
            ->toArray();
            
        $departments = \DB::table('company_worker')
            ->where('company_id', $companyId)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->select('department')
            ->distinct()
            ->get();
            
        $teamleads = \DB::table('company_worker')
            ->where('company_id', $companyId)
            ->where('role', 3)
            ->select('name')
            ->distinct()
            ->get();

        $waves = $this->availableWavesForCompany($companyId);

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

    protected function availableWavesForCompany(int $companyId): array
    {
        return SurveyResponse::with('surveyWave')
            ->select('survey_version_id', 'wave_label', 'survey_wave_id')
            ->whereHas('user', fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(200)
            ->get()
            ->map(function ($response) {
                $wave = $response->surveyWave;
                $label = $wave->label ?? $response->wave_label ?? "Version {$response->survey_version_id}";
                $key = $wave?->id ?? $response->wave_label ?? (string) $response->survey_version_id;

                return [
                    'key' => (string) $key,
                    'label' => $label,
                ];
            })
            ->unique('key')
            ->pluck('label', 'key')
            ->toArray();
    }
}
