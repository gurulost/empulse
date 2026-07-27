<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticFinding;
use App\Models\LeadershipAction;
use App\Services\OrganizationScopeService;
use App\Services\SurveyAnalyticsService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected SurveyAnalyticsService $surveyAnalytics;

    public function __construct(SurveyAnalyticsService $surveyAnalytics)
    {
        $this->middleware('auth');
        $this->surveyAnalytics = $surveyAnalytics;
    }

    public function index(OrganizationScopeService $scope)
    {
        $user = Auth::user();
        $companyId = (int) ($user?->company_id ?? 0);
        if ($user?->hasCapability('workfit.admin') && $companyId === 0) {
            return redirect()->route('admin.dashboard');
        }

        $findings = $companyId
            ? DiagnosticFinding::with('actions')
                ->where('company_id', $companyId)
                ->whereIn('status', ['proposed', 'accepted'])
                ->orderByDesc('created_at')
                ->get()
                ->filter(fn (DiagnosticFinding $finding) => $scope->canViewCohort(
                    $user,
                    $finding->cohort_snapshot
                ))
                ->take(5)
            : collect();
        $actions = $companyId
            ? LeadershipAction::with('finding')
                ->where('company_id', $companyId)
                ->whereIn('status', ['draft', 'committed', 'in_progress'])
                ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'committed' THEN 1 ELSE 2 END")
                ->orderBy('target_date')
                ->get()
                ->filter(fn (LeadershipAction $action) => $scope->canViewCohort(
                    $user,
                    $action->finding?->cohort_snapshot
                ))
                ->take(8)
            : collect();

        return view('dashboard.manager-home', compact('findings', 'actions'));
    }
}
