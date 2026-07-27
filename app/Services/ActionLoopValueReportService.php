<?php

namespace App\Services;

use App\Models\ActionMeasurementPlan;
use App\Models\ActionOutcome;
use App\Models\Companies;
use App\Models\DiagnosticFinding;
use App\Models\LeadershipAction;
use Illuminate\Database\Eloquent\Builder;

class ActionLoopValueReportService
{
    /**
     * @return array<string, mixed>
     */
    public function report(?int $companyId = null): array
    {
        $summary = $this->summary($companyId);
        $organizations = $companyId === null
            ? Companies::query()
                ->whereHas('diagnosticFindings')
                ->orderBy('title')
                ->get(['id', 'title'])
                ->map(function (Companies $company): array {
                    $organization = $this->summary((int) $company->id);

                    return [
                        'company_id' => (int) $company->id,
                        'title' => $company->title,
                        'reliable_findings' => $organization['counts']['reliable_findings'],
                        'findings_with_action' => $organization['counts']['findings_with_action'],
                        'findings_with_measured_outcome' => $organization['counts']['findings_with_measured_outcome'],
                        'finding_to_action_pct' => $organization['rates']['finding_to_action_pct'],
                        'finding_to_measured_outcome_pct' => $organization['rates']['finding_to_measured_outcome_pct'],
                    ];
                })
                ->values()
                ->all()
            : [];

        return [
            'schema_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'scope' => [
                'type' => $companyId === null ? 'platform' : 'organization',
                'company_id' => $companyId,
            ],
            ...$summary,
            'organizations' => $organizations,
            'definitions' => [
                'reliable_finding' => 'A finding captured only after the privacy, sample, metric-availability, and frozen-definition gates passed.',
                'action' => 'A leadership action with a named active owner, explicit success criteria, guardrails, and dates.',
                'measured_outcome' => 'An immutable follow-up evaluation that records sample and definition compatibility and preserves a non-causal interpretation.',
            ],
            'privacy' => [
                'grain' => 'organization_workflow',
                'contains_survey_answers' => false,
                'contains_employee_identity' => false,
            ],
        ];
    }

    /**
     * @return array{counts: array<string, int>, rates: array<string, float|null>, outcome_results: array<string, int>}
     */
    protected function summary(?int $companyId): array
    {
        $findings = DiagnosticFinding::query()
            ->when($companyId, fn (Builder $query, int $id) => $query->where('company_id', $id));
        $actions = LeadershipAction::query()
            ->when($companyId, fn (Builder $query, int $id) => $query->where('company_id', $id));
        $plans = ActionMeasurementPlan::query()
            ->when($companyId, fn (Builder $query, int $id) => $query->where('company_id', $id));
        $outcomes = ActionOutcome::query()
            ->when($companyId, fn (Builder $query, int $id) => $query->where('company_id', $id));

        $counts = [
            'reliable_findings' => (clone $findings)->count(),
            'findings_with_action' => (clone $findings)->whereHas('actions')->count(),
            'findings_with_measurement_plan' => (clone $findings)
                ->whereHas('actions.measurementPlans')
                ->count(),
            'findings_with_measured_outcome' => (clone $findings)
                ->whereHas('actions.measurementPlans.outcomes')
                ->count(),
            'leadership_actions' => (clone $actions)->count(),
            'actions_with_measurement_plan' => (clone $actions)->whereHas('measurementPlans')->count(),
            'measurement_plans' => (clone $plans)->count(),
            'measurement_plans_with_outcome' => (clone $plans)->whereHas('outcomes')->count(),
            'recorded_outcomes' => (clone $outcomes)->count(),
        ];

        return [
            'counts' => $counts,
            'rates' => [
                'finding_to_action_pct' => $this->percentage(
                    $counts['findings_with_action'],
                    $counts['reliable_findings']
                ),
                'finding_to_measurement_plan_pct' => $this->percentage(
                    $counts['findings_with_measurement_plan'],
                    $counts['reliable_findings']
                ),
                'finding_to_measured_outcome_pct' => $this->percentage(
                    $counts['findings_with_measured_outcome'],
                    $counts['reliable_findings']
                ),
                'action_to_measurement_plan_pct' => $this->percentage(
                    $counts['actions_with_measurement_plan'],
                    $counts['leadership_actions']
                ),
            ],
            'outcome_results' => collect([
                'movement_observed',
                'no_meaningful_movement',
                'declined',
                'inconclusive',
                'incompatible',
            ])->mapWithKeys(fn (string $result): array => [
                $result => (clone $outcomes)->where('result', $result)->count(),
            ])->all(),
        ];
    }

    protected function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator === 0
            ? null
            : round(($numerator / $denominator) * 100, 1);
    }
}
