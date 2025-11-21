<?php

namespace App\Http\Controllers;

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
        $user = Auth::user();
        if (!$user->company_id) {
            return response()->json(['message' => 'No company associated'], 403);
        }

        $metric = $request->input('metric', 'engagement');
        $data = $this->analytics->getTrendData($user->company_id, $metric);

        return response()->json($data);
    }

    public function getComparison(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            return response()->json(['message' => 'No company associated'], 403);
        }

        $waveId = $request->input('wave_id');
        if (!$waveId) {
            // Default to latest wave
            $latestWave = \App\Models\SurveyWave::where('company_id', $user->company_id)
                ->whereNotNull('due_at')
                ->orderByDesc('due_at')
                ->first();
            
            if (!$latestWave) {
                return response()->json(['message' => 'No waves found'], 404);
            }
            $waveId = $latestWave->id;
        }

        $dimension = $request->input('dimension', 'department');
        $data = $this->analytics->getComparisonData($user->company_id, $waveId, $dimension);

        return response()->json($data);
    }
}
