<?php

namespace App\Services;

use App\Models\MetricRegistryVersion;
use App\Models\SurveyVersion;

class MetricRegistryService
{
    public function definition(): array
    {
        $metrics = [];

        foreach (config('survey.work_content_attributes', []) as $key => $attribute) {
            $metrics["opportunity.{$key}"] = [
                'id' => "opportunity.{$key}",
                'label' => $attribute['label'] ?? $key,
                'construct_status' => 'descriptive_opportunity_input',
                'items' => ["{$key}_A", "{$key}_B", "{$key}_C"],
                'respondent_transform' => 'current_A_ideal_B_desire_C_gap_B_minus_A',
                'scale' => ['min' => 1, 'max' => 10],
                'minimum_valid_item_ratio' => 1.0,
                'minimum_n' => (int) config('privacy.reporting.minimum_company_n', 5),
                'comparison_compatibility' => 'same_metric_registry_hash_and_instrument_hash',
                'evidence_reference' => 'docs/METHODOLOGY_AND_CLAIMS_DOSSIER.md',
            ];
        }

        foreach (config('survey.indicators', []) as $key => $indicator) {
            $attributes = array_values($indicator['attributes'] ?? []);
            $metrics["indicator.{$key}"] = [
                'id' => "indicator.{$key}",
                'label' => $indicator['label'] ?? $key,
                'construct_status' => 'operational_index',
                'items' => collect($attributes)->flatMap(fn ($attribute) => [
                    "{$attribute}_A",
                    "{$attribute}_B",
                ])->values()->all(),
                'respondent_transform' => 'mean_current_divided_by_mean_ideal_times_10_clamped_0_10',
                'weight' => (float) ($indicator['weight'] ?? 1),
                'scale' => ['min' => 0, 'max' => 10],
                'minimum_valid_item_ratio' => 1.0,
                'minimum_n' => (int) config('privacy.reporting.minimum_company_n', 5),
                'comparison_compatibility' => 'same_metric_registry_hash_and_instrument_hash',
                'evidence_reference' => 'docs/METHODOLOGY_AND_CLAIMS_DOSSIER.md',
            ];
        }

        foreach (config('survey.team_culture_evaluation.dimensions', []) as $key => $dimension) {
            $metrics["culture.{$key}"] = [
                'id' => "culture.{$key}",
                'label' => $dimension['label'] ?? $key,
                'construct_status' => 'exploratory_operational_index',
                'items' => array_values($dimension['questions'] ?? []),
                'reverse_coded_items' => array_values(array_intersect(
                    $dimension['questions'] ?? [],
                    config('survey.team_culture.negative', [])
                )),
                'respondent_transform' => 'reverse_negative_then_mean',
                'weight' => (float) ($dimension['weight'] ?? 1),
                'scale' => config('survey.team_culture_evaluation.scale', ['min' => 1, 'max' => 9]),
                'minimum_valid_item_ratio' => 0.80,
                'minimum_n' => (int) config('privacy.reporting.minimum_company_n', 5),
                'comparison_compatibility' => 'same_metric_registry_hash_and_instrument_hash',
                'evidence_reference' => 'docs/METHODOLOGY_AND_CLAIMS_DOSSIER.md',
            ];
        }

        foreach (config('survey.impact_series', []) as $key => $items) {
            $metrics["impact.{$key}"] = [
                'id' => "impact.{$key}",
                'label' => ucfirst($key).' impact signal',
                'construct_status' => 'descriptive_opportunity_input',
                'items' => array_values($items),
                'respondent_transform' => 'mean',
                'scale' => ['min' => 1, 'max' => 10],
                'minimum_valid_item_ratio' => 1.0,
                'minimum_n' => (int) config('privacy.reporting.minimum_company_n', 5),
                'comparison_compatibility' => 'same_metric_registry_hash_and_instrument_hash',
                'evidence_reference' => 'docs/METHODOLOGY_AND_CLAIMS_DOSSIER.md',
            ];
        }

        $definition = [
            'registry_key' => 'empulse_workfit',
            'status' => 'prevalidation_operational',
            'metrics' => $metrics,
            'derived_metrics' => [
                'temperature' => [
                    'status' => 'prevalidation_operational',
                    'indicator_weight' => (float) config('survey.temperature.weights.indicator', 0.65),
                    'culture_weight' => (float) config('survey.temperature.weights.culture', 0.35),
                    'culture_scale' => config(
                        'survey.team_culture_evaluation.scale',
                        ['min' => 1, 'max' => 9]
                    ),
                ],
            ],
            'reporting_policy' => [
                'minimum_company_n' => (int) config('privacy.reporting.minimum_company_n', 5),
                'minimum_subgroup_n' => (int) config('privacy.reporting.minimum_subgroup_n', 7),
                'minimum_completion_rate' => (float) config('privacy.reporting.minimum_completion_rate', 0.40),
                'complementary_suppression' => true,
            ],
        ];

        // The version changes automatically whenever any calculation, item,
        // weight, scale, or reporting policy in the frozen definition changes.
        // This prevents a config edit from silently reusing a human-maintained
        // version label for a materially different metric contract.
        $definition['version'] = '1.0.0+'.substr($this->hash($definition), 0, 12);

        return $definition;
    }

    public function hash(?array $definition = null): string
    {
        $definition ??= $this->definition();
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
            $normalize($definition),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
    }

    public function publishedVersion(): MetricRegistryVersion
    {
        $definition = $this->definition();
        $hash = $this->hash($definition);

        return MetricRegistryVersion::firstOrCreate(
            ['definition_hash' => $hash],
            [
                'registry_key' => $definition['registry_key'],
                'version' => $definition['version'],
                'definition' => $definition,
                'status' => 'published',
                'published_at' => now(),
                'published_by' => auth()->id(),
            ]
        );
    }

    public function requiredQuestionIds(): array
    {
        return collect($this->definition()['metrics'])
            ->flatMap(fn ($metric) => $metric['items'] ?? [])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function assertInstrumentCompatible(SurveyVersion $version): void
    {
        if ($version->instrument_id !== 'empulse_workfit_baseline') {
            return;
        }

        $version->loadMissing('pages.sections.items', 'pages.items');
        $actual = $version->pages
            ->flatMap(fn ($page) => $page->items->concat(
                $page->sections->flatMap(fn ($section) => $section->items)
            ))
            ->pluck('qid')
            ->unique()
            ->all();
        $missing = array_values(array_diff($this->requiredQuestionIds(), $actual));

        if ($missing !== []) {
            throw new \DomainException(
                'Metric registry references missing instrument QIDs: '.implode(', ', $missing)
            );
        }
    }
}
