<?php

namespace App\Services;

use App\Models\AdvisorWorkItem;
use App\Models\DiagnosticFinding;
use App\Models\LeadershipAction;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AdvisorWorkQueueService
{
    public function __construct(
        protected AdvisorAccessService $access,
        protected AuditTrailService $audit
    ) {}

    public function report(
        User $advisor,
        ?string $status = null,
        ?string $kind = null,
        int $page = 1
    ): LengthAwarePaginator {
        $this->synchronize($advisor);
        $companyIds = $this->companyIds($advisor);

        $report = AdvisorWorkItem::query()
            ->with([
                'company:id,title',
                'finding:id,metric_id',
                'action:id,title',
                'assignee:id,name',
            ])
            ->whereIn('company_id', $companyIds)
            ->when($status, fn ($query, string $value) => $query->where('status', $value))
            ->when($kind, fn ($query, string $value) => $query->where('kind', $value))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderBy('id')
            ->paginate(25, ['*'], 'page', $page);

        $this->audit->record(
            'advisor.queue_viewed',
            $advisor,
            null,
            null,
            null,
            [],
            [
                'authorized_company_count' => count($companyIds),
                'result_count' => $report->count(),
                'status_filter' => $status,
                'kind_filter' => $kind,
                'page' => $page,
            ]
        );

        return $report;
    }

    public function transition(
        AdvisorWorkItem $item,
        User $advisor,
        string $status
    ): AdvisorWorkItem {
        if (! $this->access->canAdvise($advisor, (int) $item->company_id)) {
            throw new \DomainException('Customer-approved advisor access is required for this work item.');
        }

        $allowed = [
            'open' => ['claimed', 'dismissed'],
            'claimed' => ['completed', 'dismissed'],
            'completed' => [],
            'dismissed' => [],
        ];
        if (! in_array($status, $allowed[$item->status], true)) {
            throw new \DomainException("Advisor work item cannot move from {$item->status} to {$status}.");
        }
        if ($item->assigned_to_user_id
            && (int) $item->assigned_to_user_id !== (int) $advisor->id) {
            throw new \DomainException('This work item is assigned to another advisor.');
        }

        $from = $item->status;
        $item->update([
            'status' => $status,
            'assigned_to_user_id' => $status === 'claimed'
                ? $advisor->id
                : $item->assigned_to_user_id,
        ]);
        $this->audit->record(
            'advisor.work_item_status_changed',
            $advisor,
            (int) $item->company_id,
            AdvisorWorkItem::class,
            $item->id,
            ['from' => $from, 'to' => $status],
            ['kind' => $item->kind]
        );

        return $item->fresh();
    }

    public function synchronize(User $advisor): void
    {
        foreach ($this->companyIds($advisor) as $companyId) {
            $hasResponse = SurveyResponse::query()
                ->whereHas('user', fn ($query) => $query->where('company_id', $companyId))
                ->whereNotNull('submitted_at')
                ->exists();
            if (! $hasResponse) {
                $this->ensure($companyId, 'activation_risk', 'high');
            } else {
                $this->resolve($companyId, 'activation_risk');
            }

            DiagnosticFinding::query()
                ->where('company_id', $companyId)
                ->where('status', 'proposed')
                ->each(fn (DiagnosticFinding $finding) => $this->ensure(
                    $companyId,
                    'finding_review',
                    'normal',
                    $finding->id
                ));
            AdvisorWorkItem::query()
                ->where('company_id', $companyId)
                ->where('kind', 'finding_review')
                ->whereIn('status', ['open', 'claimed'])
                ->whereHas('finding', fn ($query) => $query->where('status', 'accepted'))
                ->update(['status' => 'completed']);
            AdvisorWorkItem::query()
                ->where('company_id', $companyId)
                ->where('kind', 'finding_review')
                ->whereIn('status', ['open', 'claimed'])
                ->whereHas('finding', fn ($query) => $query->where('status', 'dismissed'))
                ->update(['status' => 'dismissed']);
            DiagnosticFinding::query()
                ->where('company_id', $companyId)
                ->where('status', 'accepted')
                ->whereDoesntHave('actions')
                ->each(fn (DiagnosticFinding $finding) => $this->ensure(
                    $companyId,
                    'action_plan_assistance',
                    'normal',
                    $finding->id
                ));
            AdvisorWorkItem::query()
                ->where('company_id', $companyId)
                ->where('kind', 'action_plan_assistance')
                ->whereIn('status', ['open', 'claimed'])
                ->whereHas('finding.actions')
                ->update(['status' => 'completed']);

            LeadershipAction::query()
                ->where('company_id', $companyId)
                ->whereIn('status', ['committed', 'in_progress'])
                ->whereNotNull('target_date')
                ->whereDate('target_date', '<', today())
                ->whereHas('measurementPlans', fn ($query) => $query->whereNotIn('status', ['evaluated']))
                ->each(fn (LeadershipAction $action) => $this->ensure(
                    $companyId,
                    'overdue_followup',
                    'urgent',
                    null,
                    $action->id,
                    $action->target_date?->endOfDay()
                ));
            AdvisorWorkItem::query()
                ->where('company_id', $companyId)
                ->where('kind', 'overdue_followup')
                ->whereIn('status', ['open', 'claimed'])
                ->whereHas('action', fn ($query) => $query->whereIn('status', ['completed', 'cancelled']))
                ->update(['status' => 'completed']);
        }
    }

    protected function ensure(
        int $companyId,
        string $kind,
        string $priority,
        ?int $findingId = null,
        ?int $actionId = null,
        ?\DateTimeInterface $dueAt = null
    ): AdvisorWorkItem {
        return AdvisorWorkItem::firstOrCreate(
            [
                'company_id' => $companyId,
                'kind' => $kind,
                'diagnostic_finding_id' => $findingId,
                'leadership_action_id' => $actionId,
            ],
            [
                'public_id' => (string) Str::uuid(),
                'priority' => $priority,
                'status' => 'open',
                'due_at' => $dueAt,
                'context' => null,
            ]
        );
    }

    protected function resolve(int $companyId, string $kind): void
    {
        AdvisorWorkItem::query()
            ->where('company_id', $companyId)
            ->where('kind', $kind)
            ->whereIn('status', ['open', 'claimed'])
            ->update(['status' => 'completed']);
    }

    protected function companyIds(User $advisor): array
    {
        return $this->access->activeForAdvisor($advisor)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
