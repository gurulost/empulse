<?php

namespace App\Services;

use App\Models\ActionMeasurementPlan;
use App\Models\DiagnosticFinding;
use App\Models\LeadershipAction;
use App\Models\PulseVariantVersion;

class PulseVariantService
{
    public function __construct(protected MetricRegistryService $registry) {}

    public function forMeasurementPlan(ActionMeasurementPlan $plan): PulseVariantVersion
    {
        $action = LeadershipAction::findOrFail($plan->leadership_action_id);
        $finding = DiagnosticFinding::findOrFail($action->diagnostic_finding_id);
        $registryVersion = $this->registry->publishedVersion();
        if ($registryVersion->definition_hash !== $plan->baseline_metric_hash) {
            throw new \DomainException('The measurement plan uses a retired metric registry.');
        }

        $metric = $registryVersion->definition['metrics'][$plan->metric_id] ?? null;
        if (! $metric) {
            throw new \DomainException('The planned metric is not in the frozen registry.');
        }
        $questionIds = array_values(array_unique($metric['items'] ?? []));
        if ($questionIds === []) {
            throw new \DomainException('The planned metric has no governed pulse items.');
        }
        $definition = [
            'metric_registry_hash' => $registryVersion->definition_hash,
            'metric_id' => $plan->metric_id,
            'question_ids' => $questionIds,
            'minimum_days_between_invites' => 30,
            'maximum_pulses_per_90_days' => 3,
        ];
        $hash = hash('sha256', json_encode($definition, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $variantKey = 'followup.'.str_replace('.', '_', $plan->metric_id);

        return PulseVariantVersion::firstOrCreate(
            ['definition_hash' => $hash],
            [
                'variant_key' => $variantKey,
                'version' => '1.0.0',
                'title' => 'Follow-up: '.$metric['label'],
                'purpose' => 'action_followup',
                'metric_registry_version_id' => $registryVersion->id,
                'metric_id' => $plan->metric_id,
                'question_ids' => $questionIds,
                'estimated_minutes' => max(1, (int) ceil(count($questionIds) / 8)),
                'minimum_days_between_invites' => 30,
                'maximum_pulses_per_90_days' => 3,
                'claims_limit' => 'This pulse evaluates a predeclared descriptive metric. It does not establish causality.',
                'status' => 'published',
                'published_at' => now(),
            ]
        );
    }
}
