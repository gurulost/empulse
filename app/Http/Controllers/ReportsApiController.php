<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use App\Models\SurveyWave;
use App\Services\SurveyAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsApiController extends Controller
{
    protected SurveyAnalyticsService $analytics;

    public function __construct(SurveyAnalyticsService $analytics)
    {
        $this->middleware('auth');
        $this->analytics = $analytics;
    }

    public function getTrends(Request $request)
    {
        [$companyId, $errorResponse] = $this->resolveCompanyContext($request);
        if ($errorResponse) {
            return $errorResponse;
        }

        $metric = $request->input('metric', 'engagement');
        $data = $this->analytics->getTrendData($companyId, $metric);

        return response()->json($data);
    }

    public function getComparison(Request $request)
    {
        [$companyId, $errorResponse] = $this->resolveCompanyContext($request);
        if ($errorResponse) {
            return $errorResponse;
        }

        $waveId = $request->input('wave_id');
        if (!$waveId) {
            // Default to most recent wave, falling back to the latest response wave when due dates are not set.
            $latestWave = SurveyWave::where('company_id', $companyId)
                ->orderByDesc('due_at')
                ->orderByDesc('id')
                ->first();
            
            if ($latestWave) {
                $waveId = $latestWave->id;
            } else {
                $waveId = SurveyResponse::query()
                    ->whereNotNull('submitted_at')
                    ->whereNotNull('survey_wave_id')
                    ->whereHas('user', fn ($query) => $query->where('company_id', $companyId))
                    ->orderByDesc('submitted_at')
                    ->value('survey_wave_id');
            }

            if (!$waveId) {
                return response()->json(['message' => 'No waves found'], 404);
            }
        }

        $dimension = $request->input('dimension', 'department');
        $data = $this->analytics->getComparisonData($companyId, $waveId, $dimension);

        return response()->json($data);
    }

    protected function resolveCompanyContext(Request $request): array
    {
        $user = Auth::user();
        if (!$user) {
            return [null, response()->json(['message' => 'Unauthenticated.'], 401)];
        }

        $isSuperAdmin = (int) $user->role === 0 || (int) ($user->is_admin ?? 0) === 1;
        $requestedCompanyId = $request->integer('company_id');
        $companyId = $requestedCompanyId ?: (int) ($user->company_id ?? 0);

        if (!$companyId) {
            return [null, response()->json(['message' => 'Company is required.'], 422)];
        }

        if (!$isSuperAdmin && (int) $user->company_id !== $companyId) {
            return [null, response()->json(['message' => 'Forbidden'], 403)];
        }

        return [$companyId, null];
    }
}
