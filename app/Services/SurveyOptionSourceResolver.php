<?php

namespace App\Services;

use App\Models\SurveyOptionSource;

class SurveyOptionSourceResolver
{
    protected ?array $isoCountries = null;

    public function resolve(?SurveyOptionSource $source): array
    {
        if (!$source) {
            return ['options' => [], 'meta' => null];
        }

        $kind = $source->kind;
        $config = $source->config ?? [];

        if (str_starts_with($kind, 'algorithm:')) {
            $algorithm = substr($kind, strlen('algorithm:'));
            return [
                'options' => $this->resolveAlgorithm($algorithm, $config),
                'meta' => ['kind' => $kind, 'config' => $config],
            ];
        }

        switch ($kind) {
            case 'ISO_3166_COUNTRIES_EN':
                return [
                    'options' => $this->isoCountryOptions(),
                    'meta' => ['kind' => $kind, 'config' => $config],
                ];
            default:
                return [
                    'options' => [],
                    'meta' => ['kind' => $kind, 'config' => $config],
                ];
        }
    }

    protected function resolveAlgorithm(string $algorithm, array $config): array
    {
        return match ($algorithm) {
            'years_of_service', 'years_since_degree' => $this->buildYearsOptions($config),
            default => [],
        };
    }

    protected function isoCountryOptions(): array
    {
        if ($this->isoCountries !== null) {
            return $this->isoCountries;
        }

        $path = '/usr/share/zoneinfo/iso3166.tab';
        if (!is_readable($path)) {
            return $this->isoCountries = [];
        }

        $lines = file($path) ?: [];
        $options = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$code, $name] = array_map('trim', explode("\t", $line, 2));
            if ($code && $name) {
                $options[] = [
                    'value' => $code,
                    'label' => $name,
                ];
            }
        }

        return $this->isoCountries = $options;
    }

    protected function buildYearsOptions(array $config): array
    {
        $start = max(1, (int)($config['start'] ?? 1));
        $end = max($start, (int)($config['end'] ?? $start));
        $labels = $config['labels'] ?? [];
        $includeLt1 = (bool)($config['include_less_than_one'] ?? false);
        $includePlus = (bool)($config['include_50_plus'] ?? false);

        $options = [];
        if ($includeLt1) {
            $options[] = [
                'value' => 'LT1',
                'label' => $labels['lt1'] ?? 'Less than 1 year',
            ];
        }

        for ($year = $start; $year <= $end; $year++) {
            $options[] = [
                'value' => (string) $year,
                'label' => $year === 1 ? '1 year' : sprintf('%d years', $year),
            ];
        }

        if ($includePlus) {
            $label = $labels['n_plus'] ?? sprintf('%d+', $end);
            $options[] = [
                'value' => sprintf('%d_PLUS', $end),
                'label' => $label,
            ];
        }

        return $options;
    }
}
