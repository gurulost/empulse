<?php

namespace App\Services;

use App\Models\SurveyVersion;
use App\Support\SurveyWaveAutomation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OnboardingReportService
{
    public function report(int $page = 1, ?string $search = null, int $perPage = 10, ?string $stage = null): array
    {
        $search = trim((string) $search) ?: null;
        $stageFilter = $this->normalizeStageFilter($stage);

        $companies = $this->companiesForSearch($search);
        $companyIds = $companies->pluck('id')->map(fn ($id) => (int) $id)->all();
        $eventMetrics = $this->eventMetricsByCompany($companyIds);
        $subscriptionStatuses = $this->latestSubscriptionStatusByManager(
            $companies->pluck('manager_user_id')->filter()->map(fn ($id) => (int) $id)->all()
        );

        $rows = $companies->map(function ($company) use ($eventMetrics, $subscriptionStatuses) {
            return $this->companyRow(
                $company,
                $eventMetrics->get((int) $company->id),
                $subscriptionStatuses->get((int) ($company->manager_user_id ?? 0))
            );
        })->values();

        $alertsByCompany = $rows->mapWithKeys(function (array $row) {
            $alert = $this->alertForRow($row);

            return $alert ? [$row['id'] => $alert] : [];
        });

        $rows = $rows->map(function (array $row) use ($alertsByCompany) {
            $row['alert'] = $alertsByCompany->get($row['id']);

            return $row;
        })->values();

        $filteredRows = $this->applyStageFilter($rows, $stageFilter);
        $filteredCompanyIds = $filteredRows->pluck('id')->map(fn ($id) => (int) $id)->all();

        return [
            'summary' => $this->summary($rows, $alertsByCompany->values()),
            'system_status' => $this->systemStatus(),
            'filters' => [
                'search' => $search ?? '',
                'stage' => $stageFilter,
                'stage_options' => $this->stageOptions(),
            ],
            'stage_breakdown' => $this->stageBreakdown($rows, $alertsByCompany),
            'plan_breakdown' => $this->planBreakdown($rows, $alertsByCompany),
            'companies' => $this->paginateRows($filteredRows, $page, $perPage),
            'alerts' => $this->alerts($filteredRows),
            'recent_events' => $this->recentEvents($filteredCompanyIds ?: $companyIds),
        ];
    }

    protected function systemStatus(): array
    {
        $activeVersion = SurveyVersion::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first(['id', 'version', 'title']);

        return [
            'has_live_survey' => $activeVersion !== null,
            'live_survey' => $activeVersion
                ? [
                    'id' => (int) $activeVersion->id,
                    'version' => $activeVersion->version,
                    'title' => $activeVersion->title,
                ]
                : null,
            'survey_content_owner' => 'workfit_admin',
            'blocking_companies_count' => $activeVersion
                ? 0
                : (int) DB::table('companies')->count(),
        ];
    }

    protected function companiesForSearch(?string $search): Collection
    {
        $managerQuery = DB::table('users')
            ->selectRaw('company_id, MIN(id) as manager_user_id, MAX(tariff) as tariff')
            ->where('role', 1)
            ->groupBy('company_id');

        $query = DB::table('companies')
            ->leftJoinSub($managerQuery, 'manager_users', function ($join) {
                $join->on('manager_users.company_id', '=', 'companies.id');
            })
            ->leftJoin('users as manager_accounts', 'manager_accounts.id', '=', 'manager_users.manager_user_id')
            ->select([
                'companies.id',
                'companies.title',
                'companies.manager',
                'companies.manager_email',
                'companies.created_at',
                'manager_users.manager_user_id',
                'manager_users.tariff',
                'manager_accounts.created_at as manager_user_created_at',
            ]);

        if ($search) {
            $query->where(function ($builder) use ($search) {
                $builder->where('companies.title', 'like', "%{$search}%")
                    ->orWhere('companies.manager', 'like', "%{$search}%")
                    ->orWhere('companies.manager_email', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('companies.title')
            ->get();
    }

    protected function latestSubscriptionStatusByManager(array $managerIds): Collection
    {
        if (empty($managerIds)) {
            return collect();
        }

        $latestSubscriptions = DB::table('subscriptions')
            ->selectRaw('user_id, MAX(id) as latest_id')
            ->whereIn('user_id', $managerIds)
            ->groupBy('user_id');

        return DB::table('subscriptions as subscriptions')
            ->joinSub($latestSubscriptions, 'latest_subscriptions', function ($join) {
                $join->on('latest_subscriptions.latest_id', '=', 'subscriptions.id');
            })
            ->select([
                'subscriptions.user_id',
                'subscriptions.stripe_status',
            ])
            ->get()
            ->keyBy('user_id');
    }

    protected function companyRow(object $company, ?array $metrics, ?object $subscription): array
    {
        $plan = $this->planContext($company);
        $billingStatus = $this->billingStatus($plan['tariff'], $subscription?->stripe_status ?? null);
        $stage = $this->stageForMetrics($metrics);
        $createdAt = $company->created_at
            ? (string) $company->created_at
            : ($company->manager_user_created_at ? (string) $company->manager_user_created_at : null);
        $startedAt = $metrics['started_at'] ?? null;
        $firstWaveAt = $metrics['first_wave_at'] ?? null;
        $firstResponseAt = $metrics['first_response_at'] ?? null;

        return [
            'id' => (int) $company->id,
            'title' => $company->title,
            'manager' => $company->manager,
            'manager_email' => $company->manager_email,
            'created_at' => $createdAt,
            'stage' => $stage,
            'plan_key' => $plan['key'],
            'plan_label' => $plan['label'],
            'tariff' => $plan['tariff'],
            'billing_status' => $billingStatus,
            'billing_label' => SurveyWaveAutomation::billingStatusLabel($billingStatus),
            'billing_allows_scheduling' => $this->billingAllowsScheduling($billingStatus),
            'started_at' => $startedAt,
            'first_wave_at' => $firstWaveAt,
            'first_response_at' => $firstResponseAt,
            'minutes_to_first_wave' => $metrics['minutes_to_first_wave'] ?? null,
            'minutes_to_first_response' => $metrics['minutes_to_first_response'] ?? null,
            'checklist_views' => $metrics['checklist_views'] ?? 0,
            'cta_clicks' => $metrics['cta_clicks'] ?? 0,
            'recent_event_count' => $metrics['recent_event_count'] ?? 0,
            'last_event_name' => $metrics['last_event_name'] ?? null,
            'last_event_at' => $metrics['last_event_at'] ?? null,
            'hours_since_created' => $this->hoursSince($createdAt),
            'hours_since_started' => $this->hoursSince($startedAt),
            'hours_since_first_wave' => $this->hoursSince($firstWaveAt),
            'hours_since_first_response' => $this->hoursSince($firstResponseAt),
        ];
    }

    protected function planContext(object $company): array
    {
        $managerUserId = $company->manager_user_id ? (int) $company->manager_user_id : null;
        $tariff = $company->tariff !== null ? (int) $company->tariff : null;

        if ($managerUserId === null) {
            return [
                'key' => 'no_manager',
                'label' => 'No Manager Account',
                'tariff' => null,
            ];
        }

        return [
            'key' => sprintf('tariff_%s', $tariff ?? 'unknown'),
            'label' => SurveyWaveAutomation::planLabel($tariff),
            'tariff' => $tariff,
        ];
    }

    protected function billingStatus(?int $tariff, ?string $stripeStatus): string
    {
        if ($stripeStatus) {
            return $stripeStatus;
        }

        if ($tariff !== null && SurveyWaveAutomation::dripEnabledForTariff($tariff)) {
            return 'manual-premium';
        }

        return 'none';
    }

    protected function billingAllowsScheduling(string $billingStatus): bool
    {
        if ($billingStatus === 'manual-premium') {
            return true;
        }

        return SurveyWaveAutomation::billingStatusAllows($billingStatus);
    }

    protected function applyStageFilter(Collection $rows, string $stageFilter): Collection
    {
        if ($stageFilter === 'all') {
            return $rows->values();
        }

        return $rows
            ->filter(fn (array $row) => ($row['stage']['key'] ?? 'dormant') === $stageFilter)
            ->values();
    }

    protected function paginateRows(Collection $rows, int $page, int $perPage): LengthAwarePaginator
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $items = $rows->forPage($page, $perPage)->values()->all();

        return new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    protected function eventMetricsByCompany(array $companyIds): Collection
    {
        if (empty($companyIds)) {
            return collect();
        }

        $rows = DB::table('onboarding_events')
            ->selectRaw("
                company_id,
                MIN(CASE WHEN name = 'session_started' THEN created_at END) as started_at,
                MIN(CASE WHEN name = 'first_wave_dispatched' THEN created_at END) as first_wave_at,
                MIN(CASE WHEN name = 'first_response_completed' THEN created_at END) as first_response_at,
                MAX(created_at) as last_event_at,
                COUNT(CASE WHEN name = 'onboarding_checklist_viewed' THEN 1 END) as checklist_views,
                COUNT(CASE WHEN name IN ('onboarding_step_cta_clicked', 'survey_wave_action_selected') THEN 1 END) as cta_clicks,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as recent_event_count
            ", [now()->subDays(7)])
            ->whereIn('company_id', $companyIds)
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        $latestEvents = DB::table('onboarding_events as events')
            ->select('events.company_id', 'events.name', 'events.created_at')
            ->join(DB::raw('(
                SELECT company_id, MAX(created_at) as max_created_at
                FROM onboarding_events
                WHERE company_id IN (' . implode(',', array_map('intval', $companyIds)) . ')
                GROUP BY company_id
            ) as latest_events'), function ($join) {
                $join->on('latest_events.company_id', '=', 'events.company_id')
                    ->on('latest_events.max_created_at', '=', 'events.created_at');
            })
            ->get()
            ->keyBy('company_id');

        return collect($companyIds)->mapWithKeys(function (int $companyId) use ($rows, $latestEvents) {
            $row = $rows->get($companyId);
            $latest = $latestEvents->get($companyId);

            $startedAt = $row?->started_at ? (string) $row->started_at : null;
            $firstWaveAt = $row?->first_wave_at ? (string) $row->first_wave_at : null;
            $firstResponseAt = $row?->first_response_at ? (string) $row->first_response_at : null;

            return [$companyId => [
                'started_at' => $startedAt,
                'first_wave_at' => $firstWaveAt,
                'first_response_at' => $firstResponseAt,
                'minutes_to_first_wave' => $this->minutesBetween($startedAt, $firstWaveAt),
                'minutes_to_first_response' => $this->minutesBetween($startedAt, $firstResponseAt),
                'checklist_views' => (int) ($row?->checklist_views ?? 0),
                'cta_clicks' => (int) ($row?->cta_clicks ?? 0),
                'recent_event_count' => (int) ($row?->recent_event_count ?? 0),
                'last_event_name' => $latest?->name,
                'last_event_at' => $row?->last_event_at ? (string) $row->last_event_at : null,
            ]];
        });
    }

    protected function recentEvents(array $companyIds, int $limit = 12): array
    {
        if (empty($companyIds)) {
            return [];
        }

        return DB::table('onboarding_events')
            ->join('companies', 'companies.id', '=', 'onboarding_events.company_id')
            ->select([
                'onboarding_events.id',
                'onboarding_events.company_id',
                'companies.title as company_title',
                'onboarding_events.name',
                'onboarding_events.context_surface',
                'onboarding_events.task_id',
                'onboarding_events.guidance_level',
                'onboarding_events.created_at',
            ])
            ->whereIn('onboarding_events.company_id', $companyIds)
            ->orderByDesc('onboarding_events.created_at')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => (int) $event->id,
                    'company_id' => (int) $event->company_id,
                    'company_title' => $event->company_title,
                    'name' => $event->name,
                    'context_surface' => $event->context_surface,
                    'task_id' => $event->task_id,
                    'guidance_level' => $event->guidance_level,
                    'created_at' => (string) $event->created_at,
                ];
            })
            ->all();
    }

    protected function summary(Collection $rows, Collection $alerts): array
    {
        $started = $rows->filter(fn (array $row) => !empty($row['started_at']));
        $dispatched = $rows->filter(fn (array $row) => !empty($row['first_wave_at']));
        $responded = $rows->filter(fn (array $row) => !empty($row['first_response_at']));
        $dormant = $rows->filter(fn (array $row) => ($row['stage']['key'] ?? 'dormant') === 'dormant');
        $stalled = $rows->filter(function (array $row) {
            if (empty($row['started_at']) || !empty($row['first_response_at'])) {
                return false;
            }

            return ($row['hours_since_started'] ?? 0) >= 24 * 7;
        });

        return [
            'companies_total' => $rows->count(),
            'companies_dormant' => $dormant->count(),
            'companies_started' => $started->count(),
            'companies_dispatched' => $dispatched->count(),
            'companies_responded' => $responded->count(),
            'stalled_companies' => $stalled->count(),
            'actionable_alerts' => $alerts->count(),
            'high_priority_alerts' => $alerts->filter(fn (array $alert) => $alert['severity'] === 'high')->count(),
            'median_minutes_to_first_wave' => $this->median(
                $rows->pluck('minutes_to_first_wave')->filter(fn ($value) => $value !== null)->values()
            ),
            'median_minutes_to_first_response' => $this->median(
                $rows->pluck('minutes_to_first_response')->filter(fn ($value) => $value !== null)->values()
            ),
            'recent_event_count' => (int) $rows->sum('recent_event_count'),
        ];
    }

    protected function stageBreakdown(Collection $rows, Collection $alertsByCompany): array
    {
        $total = max(1, $rows->count());

        return collect($this->stageOptions())
            ->reject(fn (array $stage) => $stage['key'] === 'all')
            ->map(function (array $stage) use ($rows, $alertsByCompany, $total) {
                $stageRows = $rows->filter(fn (array $row) => ($row['stage']['key'] ?? 'dormant') === $stage['key']);

                return [
                    'key' => $stage['key'],
                    'label' => $stage['label'],
                    'tone' => $stage['tone'],
                    'count' => $stageRows->count(),
                    'share' => (int) round(($stageRows->count() / $total) * 100),
                    'alert_count' => $stageRows->filter(fn (array $row) => $alertsByCompany->has($row['id']))->count(),
                ];
            })
            ->values()
            ->all();
    }

    protected function planBreakdown(Collection $rows, Collection $alertsByCompany): array
    {
        return $rows
            ->groupBy('plan_key')
            ->map(function (Collection $planRows) use ($alertsByCompany) {
                $first = $planRows->first();
                $responded = $planRows->filter(fn (array $row) => ($row['stage']['key'] ?? null) === 'live_data')->count();
                $billingReady = $planRows->filter(fn (array $row) => !empty($row['billing_allows_scheduling']))->count();

                return [
                    'key' => $first['plan_key'],
                    'label' => $first['plan_label'],
                    'count' => $planRows->count(),
                    'billing_ready_count' => $billingReady,
                    'live_data_count' => $responded,
                    'alert_count' => $planRows->filter(fn (array $row) => $alertsByCompany->has($row['id']))->count(),
                    'activation_rate' => $planRows->isEmpty()
                        ? 0
                        : (int) round(($responded / $planRows->count()) * 100),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    protected function alerts(Collection $rows, int $limit = 8): array
    {
        return $rows
            ->pluck('alert')
            ->filter()
            ->sortByDesc(function (array $alert) {
                return ($alert['priority'] * 100000) + ($alert['age_hours'] ?? 0);
            })
            ->take($limit)
            ->values()
            ->all();
    }

    protected function alertForRow(array $row): ?array
    {
        $stageKey = $row['stage']['key'] ?? 'dormant';
        $hoursSinceCreated = $row['hours_since_created'];
        $hoursSinceStarted = $row['hours_since_started'];
        $hoursSinceFirstWave = $row['hours_since_first_wave'];

        if ($stageKey === 'dormant' && $hoursSinceCreated !== null && $hoursSinceCreated >= 72) {
            return $this->buildAlert(
                $row,
                key: 'no_session_started',
                title: 'No onboarding session recorded',
                reason: 'This company exists, but no manager session has been recorded in the onboarding flow.',
                recommendedAction: 'Reach out to the manager and have them open the analytics dashboard to start setup.',
                severity: 'medium',
                ageHours: $hoursSinceCreated
            );
        }

        if ($stageKey === 'started' && !$row['billing_allows_scheduling']) {
            return $this->buildAlert(
                $row,
                key: 'billing_blocked_before_first_wave',
                title: 'Billing is blocking the first wave',
                reason: 'The manager started setup, but billing is not active enough to dispatch a survey wave.',
                recommendedAction: 'Resolve billing or move the account onto a plan that allows scheduling before more setup effort is lost.',
                severity: $this->showsHighIntent($row) || ($hoursSinceStarted ?? 0) >= 24 ? 'high' : 'medium',
                ageHours: $hoursSinceStarted
            );
        }

        if ($stageKey === 'started' && $this->showsHighIntent($row) && ($hoursSinceStarted ?? 0) >= 12) {
            return $this->buildAlert(
                $row,
                key: 'engaged_without_wave',
                title: 'Manager is engaged but has not sent a wave',
                reason: 'Checklist views or CTA clicks suggest the manager is trying to launch, but no wave has been dispatched yet.',
                recommendedAction: 'Offer guided help or confirm a live survey exists so the manager can send the first wave.',
                severity: 'high',
                ageHours: $hoursSinceStarted
            );
        }

        if ($stageKey === 'started' && $hoursSinceStarted !== null && $hoursSinceStarted >= 48) {
            return $this->buildAlert(
                $row,
                key: 'first_wave_delayed',
                title: 'First wave is delayed',
                reason: 'The manager started setup more than two days ago and still has not dispatched a survey wave.',
                recommendedAction: 'Follow up with the manager to complete team setup and send the first wave.',
                severity: 'medium',
                ageHours: $hoursSinceStarted
            );
        }

        if ($stageKey === 'wave_sent' && $hoursSinceFirstWave !== null && $hoursSinceFirstWave >= 72) {
            return $this->buildAlert(
                $row,
                key: 'no_response_after_wave',
                title: 'Wave sent but no completed response',
                reason: 'The first survey wave went out more than three days ago, but the company still has no completed response.',
                recommendedAction: 'Check recipient coverage and follow up with the manager on response collection.',
                severity: 'high',
                ageHours: $hoursSinceFirstWave
            );
        }

        return null;
    }

    protected function showsHighIntent(array $row): bool
    {
        return ($row['checklist_views'] ?? 0) >= 2 || ($row['cta_clicks'] ?? 0) >= 2;
    }

    protected function buildAlert(
        array $row,
        string $key,
        string $title,
        string $reason,
        string $recommendedAction,
        string $severity,
        ?int $ageHours
    ): array {
        $priority = $this->severityPriority($severity);

        return [
            'key' => $key,
            'title' => $title,
            'company_id' => $row['id'],
            'company_title' => $row['title'],
            'manager' => $row['manager'],
            'manager_email' => $row['manager_email'],
            'stage' => $row['stage'],
            'plan_label' => $row['plan_label'],
            'billing_label' => $row['billing_label'],
            'severity' => $severity,
            'severity_label' => ucfirst($severity),
            'priority' => $priority,
            'reason' => $reason,
            'recommended_action' => $recommendedAction,
            'age_hours' => $ageHours,
            'age_label' => $this->formatAgeHours($ageHours),
            'checklist_views' => $row['checklist_views'],
            'cta_clicks' => $row['cta_clicks'],
            'last_event_name' => $row['last_event_name'],
            'last_event_at' => $row['last_event_at'],
        ];
    }

    protected function severityPriority(string $severity): int
    {
        return match ($severity) {
            'high' => 3,
            'medium' => 2,
            default => 1,
        };
    }

    protected function stageOptions(): array
    {
        return [
            ['key' => 'all', 'label' => 'All Stages', 'tone' => 'dark'],
            ['key' => 'dormant', 'label' => 'Dormant', 'tone' => 'secondary'],
            ['key' => 'started', 'label' => 'Started', 'tone' => 'warning'],
            ['key' => 'wave_sent', 'label' => 'Wave Sent', 'tone' => 'primary'],
            ['key' => 'live_data', 'label' => 'Live Data', 'tone' => 'success'],
        ];
    }

    protected function normalizeStageFilter(?string $stage): string
    {
        $stage = trim((string) $stage);
        $allowed = collect($this->stageOptions())->pluck('key')->all();

        return in_array($stage, $allowed, true) ? $stage : 'all';
    }

    protected function stageForMetrics(?array $metrics): array
    {
        if (!$metrics || empty($metrics['started_at'])) {
            return [
                'key' => 'dormant',
                'label' => 'Dormant',
                'tone' => 'secondary',
            ];
        }

        if (!empty($metrics['first_response_at'])) {
            return [
                'key' => 'live_data',
                'label' => 'Live Data',
                'tone' => 'success',
            ];
        }

        if (!empty($metrics['first_wave_at'])) {
            return [
                'key' => 'wave_sent',
                'label' => 'Wave Sent',
                'tone' => 'primary',
            ];
        }

        return [
            'key' => 'started',
            'label' => 'Started',
            'tone' => 'warning',
        ];
    }

    protected function minutesBetween(?string $from, ?string $to): ?int
    {
        if (!$from || !$to) {
            return null;
        }

        return (int) max(0, round((strtotime($to) - strtotime($from)) / 60));
    }

    protected function hoursSince(?string $value): ?int
    {
        if (!$value) {
            return null;
        }

        return (int) max(0, round((time() - strtotime($value)) / 3600));
    }

    protected function formatAgeHours(?int $hours): ?string
    {
        if ($hours === null) {
            return null;
        }

        if ($hours < 24) {
            return sprintf('%d hr', $hours);
        }

        $days = round($hours / 24, 1);

        return sprintf('%s day%s', $days, $days === 1.0 ? '' : 's');
    }

    protected function median(Collection $values): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $mid = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (int) $sorted[$mid];
        }

        return (int) round(($sorted[$mid - 1] + $sorted[$mid]) / 2);
    }
}
