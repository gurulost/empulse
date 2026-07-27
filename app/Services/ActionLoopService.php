<?php

namespace App\Services;

use App\Models\ActionCommunication;
use App\Models\ActionMeasurementPlan;
use App\Models\ActionOutcome;
use App\Models\DiagnosticFinding;
use App\Models\LeadershipAction;
use App\Models\OrganizationMembership;
use App\Models\OrganizationUnit;
use App\Models\SurveyWave;
use App\Models\User;
use App\Support\CompanyBilling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActionLoopService
{
    private const CAUSALITY_LIMIT =
        'Observed movement is descriptive and temporally associated with the recorded action. Empulse does not establish that the action caused the change.';

    public function __construct(
        protected SurveyAnalyticsService $analytics,
        protected AuditTrailService $audit,
        protected PulseVariantService $pulseVariants,
        protected InterventionPlaybookService $playbooks
    ) {}

    public function captureFinding(
        int $companyId,
        int $waveId,
        string $metricId,
        User $actor,
        array $cohort = []
    ): DiagnosticFinding {
        $this->assertCompanyActor($companyId, $actor);
        $wave = SurveyWave::where('company_id', $companyId)->findOrFail($waveId);
        $cycle = $wave->cycles()->orderByDesc('sequence')->first();
        if (! $cycle || ! $cycle->metric_registry_version_id) {
            throw new \DomainException('The wave has no frozen metric registry and cannot produce a finding.');
        }

        $filters = [
            'company_id' => $companyId,
            'wave' => "wave:{$wave->id}",
            'cycle_id' => $cycle->id,
            'department' => $cohort['department'] ?? null,
            'team' => $cohort['team'] ?? null,
        ];
        $analytics = $this->analytics->companyDashboardAnalytics($filters);
        if (($analytics['availability'] ?? null) !== 'eligible') {
            throw new \DomainException('This cohort has not earned an interpretable finding.');
        }
        if (($analytics['metric_registry']['hash'] ?? null) !== $cycle->metric_definition_hash) {
            throw new \DomainException('The frozen wave metric definition does not match the calculation runtime.');
        }

        $metric = $this->extractMetric($analytics, $metricId);
        if (! $metric || ($metric['availability'] ?? 'eligible') !== 'eligible') {
            throw new \DomainException('The requested metric is unavailable or suppressed.');
        }

        $cohortKey = $this->cohortKey($cohort);
        $organizationUnitId = ! empty($cohort['department'])
            ? OrganizationUnit::query()
                ->where('company_id', $companyId)
                ->where('name', $cohort['department'])
                ->whereNull('valid_to')
                ->value('id')
            : null;
        $reportsToMembershipId = ! empty($cohort['team'])
            ? OrganizationMembership::query()
                ->where('company_id', $companyId)
                ->whereNull('valid_to')
                ->whereHas('currentAssignment')
                ->whereHas('user', fn ($query) => $query->where('name', $cohort['team']))
                ->value('id')
            : null;
        $evidence = [
            'schema_version' => 1,
            'wave_id' => $wave->id,
            'wave_cycle_id' => $cycle->id,
            'instrument_hash' => $cycle->instrument_hash,
            'metric_registry_version_id' => $cycle->metric_registry_version_id,
            'metric_definition_hash' => $cycle->metric_definition_hash,
            'audience_hash' => $cycle->audience_hash,
            'metric_id' => $metricId,
            'metric' => $metric,
            'sample' => $analytics['sample'],
            'reliability' => $this->reliabilityForMetric($analytics, $metricId),
        ];
        $hash = $this->hash($evidence);
        $evidence['captured_at'] = now()->toIso8601String();
        $interpretation = $this->interpretation($metricId, $metric);

        $finding = DiagnosticFinding::firstOrCreate(
            [
                'company_id' => $companyId,
                'survey_wave_id' => $wave->id,
                'metric_id' => $metricId,
                'cohort_key' => $cohortKey,
                'evidence_hash' => $hash,
            ],
            [
                'public_id' => (string) Str::uuid(),
                'survey_wave_cycle_id' => $cycle->id,
                'metric_registry_version_id' => $cycle->metric_registry_version_id,
                'cohort_snapshot' => [
                    'key' => $cohortKey,
                    'department' => $cohort['department'] ?? null,
                    'team' => $cohort['team'] ?? null,
                    'organization_unit_id' => $organizationUnitId,
                    'reports_to_membership_id' => $reportsToMembershipId,
                ],
                'evidence_snapshot' => $evidence,
                'interpretation' => $interpretation,
                'limits' => 'This is a descriptive cohort pattern, not an individual, causal, clinical, or predictive conclusion.',
                'status' => 'proposed',
                'created_by_user_id' => $actor->id,
            ]
        );

        if ($finding->wasRecentlyCreated) {
            DB::table('advisor_work_items')->insert([
                'public_id' => (string) Str::uuid(),
                'company_id' => $companyId,
                'diagnostic_finding_id' => $finding->id,
                'kind' => 'finding_review',
                'priority' => 'normal',
                'status' => 'open',
                'context' => json_encode(['metric_id' => $metricId], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->event($companyId, $actor, 'reliable_finding_captured', $finding);
            $this->audit->record(
                'action.finding.captured',
                $actor,
                $companyId,
                DiagnosticFinding::class,
                $finding->id,
                ['metric_id' => $metricId, 'evidence_hash' => $hash]
            );
        }

        return $finding;
    }

    public function decideFinding(
        DiagnosticFinding $finding,
        string $decision,
        string $rationale,
        User $actor
    ): DiagnosticFinding {
        $this->assertCompanyActor($finding->company_id, $actor);
        if (! in_array($decision, ['accepted', 'dismissed', 'reopened'], true)) {
            throw new \InvalidArgumentException('Unsupported finding decision.');
        }
        $status = $decision === 'reopened' ? 'proposed' : $decision;

        return DB::transaction(function () use ($finding, $decision, $rationale, $actor, $status) {
            DB::table('finding_decisions')->insert([
                'diagnostic_finding_id' => $finding->id,
                'decision' => $decision,
                'rationale' => $rationale,
                'actor_user_id' => $actor->id,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $finding->update([
                'status' => $status,
                'decided_by_user_id' => $actor->id,
                'decided_at' => now(),
            ]);
            $this->event($finding->company_id, $actor, "finding_{$decision}", $finding);

            return $finding->fresh();
        });
    }

    public function createAction(
        DiagnosticFinding $finding,
        User $owner,
        User $actor,
        array $input
    ): LeadershipAction {
        $this->assertCompanyActor($finding->company_id, $actor);
        if ($finding->status !== 'accepted') {
            throw new \DomainException('A finding must be accepted before an action is created.');
        }
        if ((int) $owner->company_id !== (int) $finding->company_id || $owner->status !== 'active') {
            throw new \DomainException('The action owner must be an active member of the organization.');
        }
        $playbook = ! empty($input['intervention_playbook_version_id'])
            ? $this->playbooks->resolveApplicable(
                (int) $input['intervention_playbook_version_id'],
                $finding->metric_id
            )
            : null;

        $action = LeadershipAction::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $finding->company_id,
            'diagnostic_finding_id' => $finding->id,
            'intervention_playbook_version_id' => $playbook?->id,
            'title' => $input['title'],
            'hypothesis' => $input['hypothesis'],
            'planned_change' => $input['planned_change'],
            'success_criteria' => $input['success_criteria'],
            'guardrails' => $input['guardrails'] ?? [
                'Do not expose individual responses.',
                'Do not claim the intervention is proven to cause an outcome.',
            ],
            'owner_user_id' => $owner->id,
            'created_by_user_id' => $actor->id,
            'status' => 'draft',
            'starts_on' => $input['starts_on'] ?? null,
            'target_date' => $input['target_date'] ?? null,
        ]);
        $this->event($finding->company_id, $actor, 'leadership_action_created', $action);
        $this->audit->record(
            'action.plan.created',
            $actor,
            $finding->company_id,
            LeadershipAction::class,
            $action->id,
            ['finding_id' => $finding->id, 'owner_user_id' => $owner->id]
        );

        return $action;
    }

    public function transitionAction(
        LeadershipAction $action,
        string $toStatus,
        User $actor,
        ?string $note = null
    ): LeadershipAction {
        $this->assertCompanyActor($action->company_id, $actor);
        $transitions = [
            'draft' => ['committed', 'cancelled'],
            'committed' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];
        if (! in_array($toStatus, $transitions[$action->status] ?? [], true)) {
            throw new \DomainException("Invalid action transition {$action->status} → {$toStatus}.");
        }
        if ($toStatus === 'committed' && ! $action->measurementPlans()->exists()) {
            throw new \DomainException('A predeclared follow-up measurement plan is required before commitment.');
        }

        return DB::transaction(function () use ($action, $toStatus, $actor, $note) {
            $from = $action->status;
            DB::table('action_status_events')->insert([
                'leadership_action_id' => $action->id,
                'from_status' => $from,
                'to_status' => $toStatus,
                'note' => $note,
                'actor_user_id' => $actor->id,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $updates = ['status' => $toStatus];
            if ($toStatus === 'committed') {
                $updates['committed_at'] = now();
            }
            if ($toStatus === 'completed') {
                $updates['completed_at'] = now();
            }
            $action->update($updates);
            $this->event($action->company_id, $actor, "leadership_action_{$toStatus}", $action);

            return $action->fresh();
        });
    }

    public function createMeasurementPlan(
        LeadershipAction $action,
        User $actor,
        array $input
    ): ActionMeasurementPlan {
        $this->assertCompanyActor($action->company_id, $actor);
        $finding = DiagnosticFinding::findOrFail($action->diagnostic_finding_id);
        $baseline = $finding->evidence_snapshot;

        $plan = ActionMeasurementPlan::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $action->company_id,
            'leadership_action_id' => $action->id,
            'baseline_wave_id' => $finding->survey_wave_id,
            'followup_wave_id' => $input['followup_wave_id'] ?? null,
            'metric_id' => $finding->metric_id,
            'baseline_instrument_hash' => $baseline['instrument_hash'] ?? null,
            'baseline_metric_hash' => $baseline['metric_definition_hash'],
            'target_direction' => $input['target_direction'],
            'minimum_meaningful_change' => $input['minimum_meaningful_change'] ?? null,
            'audience_definition' => $input['audience_definition'] ?? $finding->cohort_snapshot,
            'status' => isset($input['followup_wave_id']) ? 'ready' : 'planned',
            'created_by_user_id' => $actor->id,
        ]);
        $this->event($action->company_id, $actor, 'followup_measurement_planned', $plan);

        return $plan;
    }

    public function publishCommunication(
        LeadershipAction $action,
        User $actor,
        string $audience,
        string $message
    ): ActionCommunication {
        $this->assertCompanyActor($action->company_id, $actor);
        if (! in_array($action->status, ['committed', 'in_progress', 'completed'], true)) {
            throw new \DomainException('Only a committed action can be communicated.');
        }
        $communication = ActionCommunication::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $action->company_id,
            'leadership_action_id' => $action->id,
            'audience' => $audience,
            'channel' => 'manager_shared',
            'message' => $message,
            'status' => 'published',
            'created_by_user_id' => $actor->id,
            'published_by_user_id' => $actor->id,
            'published_at' => now(),
        ]);
        $this->event($action->company_id, $actor, 'leadership_communication_published', $communication);
        $this->audit->record(
            'action.communication.published',
            $actor,
            $action->company_id,
            ActionCommunication::class,
            $communication->id,
            ['action_id' => $action->id, 'audience' => $audience]
        );

        return $communication;
    }

    public function createFollowupWave(
        ActionMeasurementPlan $plan,
        User $actor,
        string $label,
        \DateTimeInterface $opensAt,
        \DateTimeInterface $dueAt
    ): SurveyWave {
        $this->assertCompanyActor($plan->company_id, $actor);
        if (! CompanyBilling::hasFeature($plan->company_id, 'recurring_waves')) {
            throw new \DomainException('The organization plan does not include governed follow-up pulses.');
        }
        if ($dueAt < $opensAt) {
            throw new \DomainException('The follow-up due date must be after its opening date.');
        }
        $action = LeadershipAction::findOrFail($plan->leadership_action_id);
        $finding = DiagnosticFinding::findOrFail($action->diagnostic_finding_id);
        $baseline = SurveyWave::findOrFail($finding->survey_wave_id);
        $variant = $this->pulseVariants->forMeasurementPlan($plan);

        $wave = SurveyWave::create([
            'company_id' => $plan->company_id,
            'survey_id' => $baseline->survey_id,
            'survey_version_id' => $baseline->survey_version_id,
            'kind' => 'drip',
            'status' => 'scheduled',
            'cadence' => 'manual',
            'label' => $label,
            'target_roles' => [4],
            'opens_at' => $opensAt,
            'due_at' => $dueAt,
            'pulse_variant_version_id' => $variant->id,
            'action_measurement_plan_id' => $plan->id,
            'measurement_purpose' => 'action_followup',
            'reminder_limit' => 2,
        ]);
        $plan->update(['followup_wave_id' => $wave->id, 'status' => 'ready']);
        $this->event($plan->company_id, $actor, 'governed_followup_wave_created', $wave, [
            'metric_id' => $plan->metric_id,
            'pulse_variant_version_id' => $variant->id,
        ]);
        $this->audit->record(
            'action.followup_wave.created',
            $actor,
            $plan->company_id,
            SurveyWave::class,
            $wave->id,
            [
                'measurement_plan_id' => $plan->id,
                'pulse_variant_version_id' => $variant->id,
            ]
        );

        return $wave;
    }

    public function evaluate(
        ActionMeasurementPlan $plan,
        SurveyWave $followupWave,
        User $actor
    ): ActionOutcome {
        $this->assertCompanyActor($plan->company_id, $actor);
        if ((int) $followupWave->company_id !== (int) $plan->company_id) {
            throw new \DomainException('Follow-up wave belongs to another organization.');
        }
        $action = LeadershipAction::findOrFail($plan->leadership_action_id);
        $finding = DiagnosticFinding::findOrFail($action->diagnostic_finding_id);
        $followupCycle = $followupWave->cycles()->orderByDesc('sequence')->first();
        $analytics = $this->analytics->companyDashboardAnalytics([
            'company_id' => $plan->company_id,
            'wave' => "wave:{$followupWave->id}",
            'cycle_id' => $followupCycle?->id,
            'department' => $plan->audience_definition['department'] ?? null,
            'team' => $plan->audience_definition['team'] ?? null,
        ]);
        $compatible = $followupCycle
            && $followupCycle->instrument_hash === $plan->baseline_instrument_hash
            && $followupCycle->metric_definition_hash === $plan->baseline_metric_hash;
        $metric = ($analytics['availability'] ?? null) === 'eligible'
            ? $this->extractMetric($analytics, $plan->metric_id)
            : null;
        $baselineValue = $this->metricScalar($finding->evidence_snapshot['metric'] ?? []);
        $followupValue = $metric ? $this->metricScalar($metric) : null;
        $change = $baselineValue !== null && $followupValue !== null
            ? round($followupValue - $baselineValue, 3)
            : null;

        if (! $compatible) {
            $result = 'incompatible';
        } elseif (! $metric || $change === null) {
            $result = 'inconclusive';
        } else {
            $threshold = (float) ($plan->minimum_meaningful_change ?? 0);
            $result = match ($plan->target_direction) {
                'increase' => $change >= $threshold ? 'movement_observed' : ($change < 0 ? 'declined' : 'no_meaningful_movement'),
                'decrease' => $change <= -$threshold ? 'movement_observed' : ($change > 0 ? 'declined' : 'no_meaningful_movement'),
                default => abs($change) >= $threshold ? 'movement_observed' : 'no_meaningful_movement',
            };
        }

        $snapshot = [
            'schema_version' => 1,
            'baseline_wave_id' => $plan->baseline_wave_id,
            'followup_wave_id' => $followupWave->id,
            'metric_id' => $plan->metric_id,
            'baseline_value' => $baselineValue,
            'followup_value' => $followupValue,
            'change' => $change,
            'sample' => $analytics['sample'] ?? null,
            'compatible' => $compatible,
            'baseline_instrument_hash' => $plan->baseline_instrument_hash,
            'followup_instrument_hash' => $followupCycle?->instrument_hash,
            'baseline_metric_hash' => $plan->baseline_metric_hash,
            'followup_metric_hash' => $followupCycle?->metric_definition_hash,
        ];
        $outcome = ActionOutcome::firstOrNew([
            'action_measurement_plan_id' => $plan->id,
            'followup_wave_id' => $followupWave->id,
        ]);
        if (! $outcome->exists) {
            $outcome->public_id = (string) Str::uuid();
        }
        $outcome->fill([
            'company_id' => $plan->company_id,
            'result' => $result,
            'evaluation_snapshot' => $snapshot,
            'evaluation_hash' => $this->hash($snapshot),
            'interpretation' => $this->outcomeInterpretation($result, $change),
            'causality_limit' => self::CAUSALITY_LIMIT,
            'evaluated_by_user_id' => $actor->id,
            'evaluated_at' => now(),
        ]);
        $outcome->save();
        $plan->update([
            'followup_wave_id' => $followupWave->id,
            'status' => $compatible ? 'evaluated' : 'incompatible',
        ]);
        $this->event($plan->company_id, $actor, 'action_outcome_evaluated', $outcome, [
            'result' => $result,
        ]);

        return $outcome;
    }

    protected function extractMetric(array $analytics, string $metricId): ?array
    {
        [$family, $key] = array_pad(explode('.', $metricId, 2), 2, null);

        return match ($family) {
            'opportunity' => collect($analytics['attributes'] ?? [])->firstWhere('key', $key),
            'indicator' => collect($analytics['indicators'] ?? [])->firstWhere('key', $key),
            'culture' => isset($analytics['team_culture']['dimensions'][$key])
                ? ['key' => $key, ...$analytics['team_culture']['dimensions'][$key]]
                : null,
            'impact' => array_key_exists($key, $analytics['impact'] ?? [])
                ? ['key' => $key, 'value' => $analytics['impact'][$key]]
                : null,
            default => null,
        };
    }

    protected function metricScalar(array $metric): ?float
    {
        foreach (['gap', 'satisfaction', 'average', 'value', 'current'] as $field) {
            if (isset($metric[$field]) && is_numeric($metric[$field])) {
                return (float) $metric[$field];
            }
        }

        return null;
    }

    protected function reliabilityForMetric(array $analytics, string $metricId): ?array
    {
        if (! str_starts_with($metricId, 'culture.')) {
            return null;
        }

        return $analytics['reliability'][substr($metricId, strlen('culture.'))] ?? null;
    }

    protected function interpretation(string $metricId, array $metric): string
    {
        $value = $this->metricScalar($metric);
        $formatted = $value === null ? 'unavailable' : number_format($value, 2);

        return "The eligible cohort produced a descriptive {$metricId} value of {$formatted}. Leadership should investigate the context with employees before choosing an action.";
    }

    protected function outcomeInterpretation(string $result, ?float $change): string
    {
        $movement = $change === null ? 'could not be calculated' : 'was '.number_format($change, 2);

        return "The predeclared follow-up result is {$result}; observed change {$movement}. Review participation, reliability, cohort changes, and implementation evidence before deciding what to do next.";
    }

    protected function cohortKey(array $cohort): string
    {
        if (! empty($cohort['team'])) {
            return 'team:'.$cohort['team'];
        }
        if (! empty($cohort['department'])) {
            return 'department:'.$cohort['department'];
        }

        return 'company';
    }

    protected function assertCompanyActor(int $companyId, User $actor): void
    {
        if (! $actor->hasCapability('actions.manage') && ! $actor->hasCapability('actions.advisor')) {
            throw new \DomainException('Action management capability is required.');
        }
        app(AdvisorAccessService::class)->assertActorCanAccess($actor, $companyId);
    }

    protected function event(
        int $companyId,
        User $actor,
        string $name,
        object $subject,
        array $properties = []
    ): void {
        DB::table('action_loop_events')->insert([
            'event_id' => (string) Str::uuid(),
            'company_id' => $companyId,
            'actor_user_id' => $actor->id,
            'name' => $name,
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->id,
            'properties' => $properties ? json_encode($properties, JSON_THROW_ON_ERROR) : null,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function hash(array $payload): string
    {
        $normalize = function ($value) use (&$normalize) {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($normalize, $value);
        };

        return hash('sha256', json_encode(
            $normalize($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
    }
}
