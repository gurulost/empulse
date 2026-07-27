<?php

namespace App\Http\Controllers;

use App\Models\ActionMeasurementPlan;
use App\Models\AdvisorCompanyGrant;
use App\Models\DiagnosticFinding;
use App\Models\LeadershipAction;
use App\Models\SurveyWave;
use App\Models\User;
use App\Services\ActionLoopService;
use App\Services\AdvisorAccessService;
use App\Services\AdvisorWorkspaceNoteService;
use App\Services\InterventionPlaybookService;
use App\Services\OrganizationScopeService;
use Illuminate\Http\Request;

class ActionLoopController extends Controller
{
    public function __construct(
        protected ActionLoopService $actions,
        protected InterventionPlaybookService $playbooks,
        protected AdvisorAccessService $advisorAccess,
        protected AdvisorWorkspaceNoteService $advisorNotes
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyId($request);
        $scope = app(OrganizationScopeService::class);
        $actor = $request->user();
        $canManage = $actor->hasCapability('actions.manage');
        $findings = DiagnosticFinding::with('actions')
            ->where('company_id', $companyId)
            ->orderByRaw("CASE status WHEN 'accepted' THEN 0 WHEN 'proposed' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (DiagnosticFinding $finding) => $scope->canViewCohort(
                $actor,
                $finding->cohort_snapshot
            ))
            ->values();
        $actions = LeadershipAction::with([
            'finding',
            'owner',
            'measurementPlans.followupWave',
            'measurementPlans.outcomes',
        ])
            ->where('company_id', $companyId)
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'committed' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")
            ->orderBy('target_date')
            ->get()
            ->filter(fn (LeadershipAction $action) => $scope->canViewCohort(
                $actor,
                $action->finding->cohort_snapshot
            ))
            ->values();
        $eligibleFinding = $findings
            ->where('status', 'accepted')
            ->filter(fn (DiagnosticFinding $finding) => $finding->actions->isEmpty())
            ->first();

        return view('actions.index', [
            'findings' => $findings,
            'actions' => $actions,
            'waves' => $canManage
                ? SurveyWave::where('company_id', $companyId)->orderByDesc('id')->get()
                : collect(),
            'owners' => $canManage
                ? User::where('company_id', $companyId)
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'playbooks' => $canManage
                ? $this->playbooks->publishedForMetric(
                    $eligibleFinding instanceof DiagnosticFinding
                        ? (string) $eligibleFinding->metric_id
                        : ''
                )
                : collect(),
            'advisorGrants' => $canManage
                ? AdvisorCompanyGrant::with('advisor')
                    ->where('company_id', $companyId)
                    ->orderByDesc('id')
                    ->get()
                : collect(),
            'availableAdvisors' => $canManage
                ? User::query()
                    ->where('is_admin', 1)
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'workspaceNotes' => $this->advisorNotes->visibleTo($actor, $companyId),
            'isCustomerApprovedAdvisor' => $this->advisorAccess->canAdvise($actor, $companyId),
            'companyId' => $companyId,
        ]);
    }

    public function storeFinding(Request $request)
    {
        $data = $request->validate([
            'wave_id' => 'required|integer',
            'metric_id' => ['required', 'string', 'max:160', 'regex:/^(opportunity|indicator|culture|impact)\.[A-Za-z0-9_]+$/'],
            'department' => 'nullable|string|max:255',
            'team' => 'nullable|string|max:255',
        ]);
        $finding = $this->actions->captureFinding(
            $this->companyId($request),
            $data['wave_id'],
            $data['metric_id'],
            $request->user(),
            array_filter([
                'department' => $data['department'] ?? null,
                'team' => $data['team'] ?? null,
            ])
        );

        return $this->respond($request, $finding, 'Finding captured from eligible evidence.');
    }

    public function decideFinding(Request $request, DiagnosticFinding $finding)
    {
        $this->assertScoped($request, $finding->company_id);
        $data = $request->validate([
            'decision' => 'required|in:accepted,dismissed,reopened',
            'rationale' => 'required|string|max:4000',
        ]);

        return $this->respond(
            $request,
            $this->actions->decideFinding(
                $finding,
                $data['decision'],
                $data['rationale'],
                $request->user()
            ),
            'Finding decision recorded.'
        );
    }

    public function storeAction(Request $request)
    {
        $data = $request->validate([
            'diagnostic_finding_id' => 'required|integer|exists:diagnostic_findings,id',
            'owner_user_id' => 'required|integer|exists:users,id',
            'intervention_playbook_version_id' => 'nullable|integer|exists:intervention_playbook_versions,id',
            'title' => 'required|string|max:255',
            'hypothesis' => 'required|string|max:4000',
            'planned_change' => 'required|string|max:8000',
            'success_criteria' => 'required|string|max:4000',
            'starts_on' => 'required|date',
            'target_date' => 'required|date|after_or_equal:starts_on',
        ]);
        $finding = DiagnosticFinding::findOrFail($data['diagnostic_finding_id']);
        $this->assertScoped($request, $finding->company_id);
        $action = $this->actions->createAction(
            $finding,
            User::findOrFail($data['owner_user_id']),
            $request->user(),
            [
                ...$data,
                'success_criteria' => ['statement' => $data['success_criteria']],
            ]
        );

        return $this->respond($request, $action, 'Leadership action drafted.');
    }

    public function storeMeasurementPlan(Request $request, LeadershipAction $action)
    {
        $this->assertScoped($request, $action->company_id);
        $data = $request->validate([
            'followup_wave_id' => 'nullable|integer|exists:survey_waves,id',
            'target_direction' => 'required|in:increase,decrease,change',
            'minimum_meaningful_change' => 'nullable|numeric|min:0|max:10',
        ]);
        if (! empty($data['followup_wave_id'])) {
            SurveyWave::where('company_id', $action->company_id)
                ->findOrFail($data['followup_wave_id']);
        }

        return $this->respond(
            $request,
            $this->actions->createMeasurementPlan($action, $request->user(), $data),
            'Follow-up measurement predeclared.'
        );
    }

    public function transitionAction(Request $request, LeadershipAction $action)
    {
        $this->assertScoped($request, $action->company_id);
        $data = $request->validate([
            'status' => 'required|in:committed,in_progress,completed,cancelled',
            'note' => 'nullable|string|max:4000',
        ]);

        return $this->respond(
            $request,
            $this->actions->transitionAction(
                $action,
                $data['status'],
                $request->user(),
                $data['note'] ?? null
            ),
            'Action status updated.'
        );
    }

    public function publishCommunication(Request $request, LeadershipAction $action)
    {
        $this->assertScoped($request, $action->company_id);
        $data = $request->validate([
            'audience' => 'required|string|max:160',
            'message' => 'required|string|max:10000',
        ]);

        return $this->respond(
            $request,
            $this->actions->publishCommunication(
                $action,
                $request->user(),
                $data['audience'],
                $data['message']
            ),
            'Leadership communication recorded as published.'
        );
    }

    public function evaluate(Request $request, ActionMeasurementPlan $plan)
    {
        $this->assertScoped($request, $plan->company_id);
        $data = $request->validate([
            'followup_wave_id' => 'required|integer|exists:survey_waves,id',
        ]);
        $wave = SurveyWave::where('company_id', $plan->company_id)
            ->findOrFail($data['followup_wave_id']);

        return $this->respond(
            $request,
            $this->actions->evaluate($plan, $wave, $request->user()),
            'Outcome evaluated with comparability and causality limits.'
        );
    }

    public function createFollowupWave(Request $request, ActionMeasurementPlan $plan)
    {
        $this->assertScoped($request, $plan->company_id);
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'opens_at' => 'required|date',
            'due_at' => 'required|date|after:opens_at',
        ]);
        $wave = $this->actions->createFollowupWave(
            $plan,
            $request->user(),
            $data['label'],
            new \DateTimeImmutable($data['opens_at']),
            new \DateTimeImmutable($data['due_at'])
        );

        return $this->respond($request, $wave, 'Governed follow-up pulse created.');
    }

    protected function companyId(Request $request): int
    {
        try {
            return $this->advisorAccess->companyIdForActor(
                $request->user(),
                $request->integer('company_id') ?: null
            );
        } catch (\DomainException $exception) {
            abort(403, $exception->getMessage());
        }
    }

    protected function assertScoped(Request $request, int $companyId): void
    {
        try {
            $this->advisorAccess->assertActorCanAccess($request->user(), $companyId);
        } catch (\DomainException $exception) {
            abort(403, $exception->getMessage());
        }
    }

    protected function respond(Request $request, $resource, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['data' => $resource, 'message' => $message]);
        }

        return redirect()
            ->route(
                'actions.index',
                $request->user()->hasCapability('actions.advisor')
                    ? ['company_id' => $request->integer('company_id')]
                    : []
            )
            ->with('status', $message);
    }
}
