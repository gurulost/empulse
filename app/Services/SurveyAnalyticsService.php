<?php

namespace App\Services;

use App\Models\MetricRegistryVersion;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveCycle;
use App\Models\User;
use App\Support\CompanyBilling;
use App\Support\SurveyWaveAutomation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyAnalyticsService
{
    public function __construct(
        protected AnalyticsSamplePolicyService $samplePolicy,
        protected MetricRegistryService $metricRegistry
    ) {}

    public function workContentAnalyticsForUser(User $user): array
    {
        if (! $user->company_id) {
            return [];
        }

        return $this->companyDashboardAnalytics($user->company_id);
    }

    /**
     * @param  int|array  $filters
     */
    public function companyDashboardAnalytics($filters): array
    {
        if (is_array($filters)) {
            $companyId = (int) ($filters['company_id'] ?? 0);
        } else {
            $companyId = (int) $filters;
            $filters = ['company_id' => $companyId];
        }

        if (! $companyId) {
            return ['availability' => 'unavailable'];
        }

        $responses = $this->latestResponsesWithAnswers(
            $companyId,
            $filters['wave'] ?? null,
            isset($filters['cycle_id']) ? (int) $filters['cycle_id'] : null
        );
        $responses = $this->filterResponses($responses, $filters);
        $sample = $this->samplePolicy->assess($companyId, $responses, $filters);
        $registry = $this->registryForResponses($responses);
        if (! $registry) {
            return [
                'availability' => 'provenance_incomplete',
                'sample' => $sample,
                'message' => 'The selected responses do not share one verified frozen metric definition.',
            ];
        }
        $definition = $registry->definition;
        if ($sample['status'] !== 'eligible') {
            return [
                'availability' => $sample['status'],
                'sample' => $sample,
                'metric_registry' => [
                    'version' => $registry->version,
                    'hash' => $registry->definition_hash,
                ],
            ];
        }

        $respondentScores = $responses->map(function (SurveyResponse $response) use ($definition) {
            $attributes = $this->aggregateAttributes($response->answers, $definition);

            return [
                'response_id' => $response->id,
                'attributes' => $attributes,
                'indicators' => $this->indicatorSatisfaction($attributes, $definition),
                'team_culture' => $this->teamCultureAnalytics($response->answers, $definition),
                'impact' => $this->impactAnalytics($response->answers, $definition),
            ];
        });
        $minimumMetricN = (int) $sample['minimum_n'];
        $attributes = $this->aggregateRespondentAttributes($respondentScores, $responses->count(), $minimumMetricN);
        $indicators = $this->aggregateRespondentIndicators($respondentScores, $responses->count(), $minimumMetricN);
        $teamCulture = $this->aggregateRespondentCulture($respondentScores, $responses->count(), $minimumMetricN);
        $weightedIndicator = $this->weightedIndicatorScore($indicators, $definition);
        $teamCultureEval = $this->teamCultureEvaluation($teamCulture);
        $temperature = $this->temperatureIndex($weightedIndicator, $teamCultureEval, $definition);
        $impact = $this->aggregateRespondentImpact(
            $respondentScores,
            $responses->count(),
            $minimumMetricN,
            $definition
        );
        $gapChart = $this->gapChartDataset($attributes);
        $scatter = $this->teamScatterDataset($responses, $definition);

        return [
            'availability' => 'eligible',
            'sample' => $sample,
            'metric_registry' => [
                'version' => $registry->version,
                'hash' => $registry->definition_hash,
            ],
            'attributes' => $attributes->sortByDesc(fn ($row) => $row['gap'] ?? -INF)->values()->all(),
            'indicators' => $indicators,
            'temperature' => $temperature,
            'team_culture' => $teamCulture,
            'impact' => $impact,
            'gap_chart' => $gapChart,
            'team_scatter' => $scatter,
            'weighted_indicator' => $weightedIndicator,
            'team_culture_evaluation' => $teamCultureEval,
            'missingness' => [
                'attributes' => $attributes->mapWithKeys(fn ($row) => [
                    $row['key'] => $row['missingness'] ?? [],
                ])->all(),
                'indicators' => collect($indicators)->mapWithKeys(fn ($row) => [
                    $row['key'] => $row['missingness'] ?? null,
                ])->all(),
            ],
            'reliability' => $this->reliabilitySummary($responses, $definition),
        ];
    }

    protected function filterResponses(Collection $responses, array $filters): Collection
    {
        if (($filters['deny_all'] ?? false) === true) {
            return collect();
        }

        $department = $filters['department'] ?? null;
        $team = $filters['team'] ?? null;
        $organizationUnitId = isset($filters['organization_unit_id'])
            ? (int) $filters['organization_unit_id']
            : null;
        $reportsToMembershipId = isset($filters['reports_to_membership_id'])
            ? (int) $filters['reports_to_membership_id']
            : null;
        if (! $department && ! $team && ! $organizationUnitId && ! $reportsToMembershipId) {
            return $responses;
        }

        $workersByEmail = $this->companyWorkersByEmail($responses);

        return $responses->filter(function (SurveyResponse $response) use (
            $department,
            $team,
            $organizationUnitId,
            $reportsToMembershipId,
            $workersByEmail
        ) {
            $cohort = $this->cohortForResponse($response, $workersByEmail);

            if ($department && (string) ($cohort['department'] ?? '') !== (string) $department) {
                return false;
            }

            if ($team && (string) ($cohort['team'] ?? '') !== (string) $team) {
                return false;
            }
            if ($organizationUnitId
                && (int) ($cohort['organization_unit_id'] ?? 0) !== $organizationUnitId) {
                return false;
            }
            if ($reportsToMembershipId
                && (int) ($cohort['reports_to_membership_id'] ?? 0) !== $reportsToMembershipId) {
                return false;
            }

            return true;
        });
    }

    protected function latestResponseIdsForCompany(
        int $companyId,
        ?string $wave = null,
        ?int $cycleId = null
    ): Collection {
        $effectiveCycleId = $cycleId;
        $latestWaveId = null;
        if (($wave === null || $wave === '') && $cycleId === null) {
            $latest = SurveyResponse::query()
                ->from('survey_responses as latest_sr')
                ->join('users as latest_u', 'latest_u.id', '=', 'latest_sr.user_id')
                ->where('latest_u.company_id', $companyId)
                ->whereNotNull('latest_sr.submitted_at')
                ->orderByDesc('latest_sr.submitted_at')
                ->orderByDesc('latest_sr.id')
                ->first([
                    'latest_sr.survey_wave_id',
                    'latest_sr.survey_wave_cycle_id',
                ]);
            $latestWaveId = $latest?->survey_wave_id;
            $effectiveCycleId = $latest?->survey_wave_cycle_id;
        }

        $query = SurveyResponse::query()
            ->from('survey_responses as sr')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sr.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->whereNotNull('sr.submitted_at')
            ->selectRaw('MAX(sr.id) as id')
            ->groupBy('sr.user_id');

        if ($latestWaveId) {
            $query->where('sr.survey_wave_id', $latestWaveId);
        } elseif ($wave !== null && $wave !== '') {
            $query->leftJoin('survey_assignments as sa', 'sa.id', '=', 'sr.assignment_id');
            $this->applyWaveFilterToLatestResponsesQuery($query, (string) $wave);
            if ($effectiveCycleId === null) {
                $cycleQuery = SurveyResponse::query()
                    ->from('survey_responses as sr')
                    ->join('users as u', function ($join) use ($companyId) {
                        $join->on('u.id', '=', 'sr.user_id')
                            ->where('u.company_id', '=', $companyId);
                    })
                    ->leftJoin('survey_assignments as sa', 'sa.id', '=', 'sr.assignment_id')
                    ->whereNotNull('sr.submitted_at');
                $this->applyWaveFilterToLatestResponsesQuery($cycleQuery, (string) $wave);
                $effectiveCycleId = $cycleQuery
                    ->whereNotNull('sr.survey_wave_cycle_id')
                    ->orderByDesc('sr.submitted_at')
                    ->orderByDesc('sr.id')
                    ->value('sr.survey_wave_cycle_id');
            }
        }
        if ($effectiveCycleId !== null) {
            $query->where('sr.survey_wave_cycle_id', $effectiveCycleId);
        } elseif ($latestWaveId) {
            // A cycle-backed wave must never be pooled across occurrences.
            $query->whereNull('sr.survey_wave_cycle_id');
        }

        return $query->pluck('id');
    }

    protected function latestResponsesWithAnswers(
        int $companyId,
        ?string $wave = null,
        ?int $cycleId = null
    ): Collection {
        $responseIds = $this->latestResponseIdsForCompany($companyId, $wave, $cycleId);
        if ($responseIds->isEmpty()) {
            return collect();
        }

        return SurveyResponse::with([
            'answers' => function ($query) {
                $query->select([
                    'id',
                    'response_id',
                    'question_key',
                    'value',
                    'value_numeric',
                    'metadata',
                ]);

                $query->whereNotNull('value_numeric');
            },
            'user:id,email,company_id,company_title',
            'assignment:id,survey_wave_id,survey_version_id,wave_label',
        ])
            ->whereIn('id', $responseIds)
            ->select([
                'id',
                'survey_id',
                'survey_version_id',
                'survey_wave_id',
                'assignment_id',
                'user_id',
                'wave_label',
                'submitted_at',
                'cohort_snapshot',
                'survey_wave_cycle_id',
                'metric_registry_version_id',
                'metric_definition_hash',
            ])
            ->get();
    }

    protected function registryForResponses(Collection $responses): ?MetricRegistryVersion
    {
        if ($responses->isEmpty()) {
            return $this->metricRegistry->publishedVersion();
        }

        $cycleBacked = $responses->contains(
            fn (SurveyResponse $response) => $response->survey_wave_cycle_id !== null
        );
        $registryIds = $responses->pluck('metric_registry_version_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($cycleBacked && $registryIds->count() !== 1) {
            return null;
        }

        if ($registryIds->isEmpty()) {
            // Historical pre-cycle records use the current published contract.
            // Every cycle-backed response must instead carry frozen provenance.
            return $cycleBacked ? null : $this->metricRegistry->publishedVersion();
        }

        $registry = MetricRegistryVersion::find($registryIds->first());
        if (! $registry || ! is_array($registry->definition)) {
            return null;
        }

        $hashes = $responses->pluck('metric_definition_hash')->filter()->unique();
        if ($cycleBacked
            && ($hashes->count() !== 1 || ! hash_equals($registry->definition_hash, (string) $hashes->first()))) {
            return null;
        }

        return $registry;
    }

    protected function registryForCycle(SurveyWaveCycle $cycle): ?MetricRegistryVersion
    {
        if (! $cycle->metric_registry_version_id || ! $cycle->metric_definition_hash) {
            return null;
        }

        $registry = MetricRegistryVersion::find($cycle->metric_registry_version_id);
        if (! $registry
            || ! is_array($registry->definition)
            || ! hash_equals($registry->definition_hash, $cycle->metric_definition_hash)) {
            return null;
        }

        return $registry;
    }

    protected function opportunityConfiguration(array $definition): array
    {
        $indicators = $this->indicatorConfiguration($definition);
        $indicatorByAttribute = [];
        foreach ($indicators as $indicatorKey => $indicator) {
            foreach ($indicator['attributes'] as $attributeKey) {
                $indicatorByAttribute[$attributeKey] = $indicatorKey;
            }
        }

        return collect($definition['metrics'] ?? [])
            ->filter(fn ($metric, $id) => str_starts_with((string) $id, 'opportunity.'))
            ->mapWithKeys(function ($metric, $id) use ($indicatorByAttribute) {
                $key = substr((string) $id, strlen('opportunity.'));

                return [$key => [
                    'label' => $metric['label'] ?? $key,
                    'indicator' => $indicatorByAttribute[$key] ?? null,
                ]];
            })
            ->all();
    }

    protected function indicatorConfiguration(array $definition): array
    {
        return collect($definition['metrics'] ?? [])
            ->filter(fn ($metric, $id) => str_starts_with((string) $id, 'indicator.'))
            ->mapWithKeys(function ($metric, $id) {
                $key = substr((string) $id, strlen('indicator.'));
                $attributes = collect($metric['items'] ?? [])
                    ->map(fn ($qid) => preg_replace('/_(A|B|C)$/', '', (string) $qid))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return [$key => [
                    'label' => $metric['label'] ?? $key,
                    'attributes' => $attributes,
                    'weight' => (float) ($metric['weight'] ?? 1),
                ]];
            })
            ->all();
    }

    protected function cultureConfiguration(array $definition): array
    {
        $dimensions = [];
        $negative = [];
        $all = [];
        foreach ($definition['metrics'] ?? [] as $id => $metric) {
            if (! str_starts_with((string) $id, 'culture.')) {
                continue;
            }
            $key = substr((string) $id, strlen('culture.'));
            $items = array_values($metric['items'] ?? []);
            $reversed = array_values($metric['reverse_coded_items'] ?? []);
            $dimensions[$key] = [
                'label' => $metric['label'] ?? $key,
                'questions' => $items,
                'weight' => (float) ($metric['weight'] ?? 1),
                'minimum_valid_item_ratio' => (float) ($metric['minimum_valid_item_ratio'] ?? 0.80),
            ];
            $all = array_merge($all, $items);
            $negative = array_merge($negative, $reversed);
        }

        $negative = array_values(array_unique($negative));

        return [
            'positive' => array_values(array_diff(array_unique($all), $negative)),
            'negative' => $negative,
            'dimensions' => $dimensions,
            'scale' => collect($definition['metrics'] ?? [])
                ->first(fn ($metric, $id) => str_starts_with((string) $id, 'culture.'))['scale']
                ?? ['min' => 1, 'max' => 9],
        ];
    }

    protected function impactConfiguration(array $definition): array
    {
        return collect($definition['metrics'] ?? [])
            ->filter(fn ($metric, $id) => str_starts_with((string) $id, 'impact.'))
            ->mapWithKeys(fn ($metric, $id) => [
                substr((string) $id, strlen('impact.')) => array_values($metric['items'] ?? []),
            ])
            ->all();
    }

    protected function applyWaveFilterToLatestResponsesQuery($query, string $wave): void
    {
        $selector = $this->parseWaveSelector($wave);
        if (empty($selector['wave_ids']) && empty($selector['version_ids']) && empty($selector['labels'])) {
            return;
        }

        $query->where(function ($waveQuery) use ($selector) {
            $hasCondition = false;
            $appendCondition = function (callable $callback) use (&$hasCondition, $waveQuery): void {
                if ($hasCondition) {
                    $waveQuery->orWhere($callback);
                } else {
                    $waveQuery->where($callback);
                    $hasCondition = true;
                }
            };

            if (! empty($selector['wave_ids'])) {
                $appendCondition(function ($q) use ($selector) {
                    $q->whereIn('sr.survey_wave_id', $selector['wave_ids'])
                        ->orWhereIn('sa.survey_wave_id', $selector['wave_ids']);
                });
            }

            if (! empty($selector['version_ids'])) {
                $appendCondition(function ($q) use ($selector) {
                    $q->whereIn('sr.survey_version_id', $selector['version_ids'])
                        ->orWhereIn('sa.survey_version_id', $selector['version_ids']);
                });
            }

            if (! empty($selector['labels'])) {
                $appendCondition(function ($q) use ($selector) {
                    $q->whereIn('sr.wave_label', $selector['labels'])
                        ->orWhereIn('sa.wave_label', $selector['labels']);
                });
            }
        });
    }

    protected function parseWaveSelector(string $wave): array
    {
        $raw = trim($wave);
        if ($raw === '') {
            return [
                'wave_ids' => [],
                'version_ids' => [],
                'labels' => [],
            ];
        }

        $waveIds = [];
        $versionIds = [];
        $labels = [];

        if (str_contains($raw, ':')) {
            [$prefix, $value] = explode(':', $raw, 2);
            $value = trim($value);

            if ($prefix === 'wave' && ctype_digit($value)) {
                $waveIds[] = (int) $value;
            } elseif ($prefix === 'version' && ctype_digit($value)) {
                $versionIds[] = (int) $value;
            } elseif ($prefix === 'label' && $value !== '') {
                $labels[] = $value;
            } elseif (ctype_digit($value)) {
                $waveIds[] = (int) $value;
                $versionIds[] = (int) $value;
            } elseif ($value !== '') {
                $labels[] = $value;
            }
        } elseif (ctype_digit($raw)) {
            $waveIds[] = (int) $raw;
            $versionIds[] = (int) $raw;
        } else {
            $labels[] = $raw;
        }

        return [
            'wave_ids' => array_values(array_unique($waveIds)),
            'version_ids' => array_values(array_unique($versionIds)),
            'labels' => array_values(array_unique($labels)),
        ];
    }

    protected function indicatorSatisfaction(Collection $attributes, array $definition): array
    {
        $indicatorConfig = $this->indicatorConfiguration($definition);
        $grouped = [];

        foreach ($indicatorConfig as $key => $config) {
            $rows = $attributes->whereIn('key', $config['attributes'] ?? []);
            $requiredAttributes = array_values($config['attributes'] ?? []);
            $completeRows = $rows->filter(
                fn ($row) => ($row['current'] ?? null) !== null && ($row['ideal'] ?? null) !== null
            );
            if ($rows->isEmpty() || $completeRows->count() < count($requiredAttributes)) {
                continue;
            }

            $current = $this->average($completeRows->pluck('current')->filter());
            $ideal = $this->average($completeRows->pluck('ideal')->filter());
            $desire = $this->average($rows->pluck('desire')->filter());
            $gap = ($current !== null && $ideal !== null) ? round($ideal - $current, 2) : null;
            $satisfaction = null;
            if ($current !== null) {
                if ($ideal !== null && $ideal > 0) {
                    $satisfaction = round(max(0, min(10, ($current / $ideal) * 10)), 2);
                } else {
                    $satisfaction = round(max(0, min(10, $current)), 2);
                }
            }

            $grouped[] = [
                'key' => $key,
                'label' => $config['label'] ?? $key,
                'current' => $current,
                'ideal' => $ideal,
                'desire' => $desire,
                'gap' => $gap,
                'satisfaction' => $satisfaction,
            ];
        }

        return collect($grouped)
            ->sortByDesc(fn ($row) => $row['gap'] ?? -INF)
            ->values()
            ->all();
    }

    protected function temperatureIndex(
        ?float $weightedIndicator,
        ?float $teamCultureEval,
        array $definition
    ): ?float {
        if ($weightedIndicator === null && $teamCultureEval === null) {
            return null;
        }

        if ($weightedIndicator === null) {
            return $this->normalizeCultureToTen($teamCultureEval, $definition);
        }

        if ($teamCultureEval === null) {
            return round($weightedIndicator, 2);
        }

        $temperature = $definition['derived_metrics']['temperature'] ?? [];
        $indicatorWeight = max(0, (float) ($temperature['indicator_weight'] ?? 0.65));
        $cultureWeight = max(0, (float) ($temperature['culture_weight'] ?? 0.35));
        $totalWeight = $indicatorWeight + $cultureWeight;

        if ($totalWeight <= 0) {
            return round($weightedIndicator, 2);
        }

        $cultureOnTen = $this->normalizeCultureToTen($teamCultureEval, $definition);

        return round((($weightedIndicator * $indicatorWeight) + ($cultureOnTen * $cultureWeight)) / $totalWeight, 2);
    }

    protected function normalizeCultureToTen(?float $score, array $definition): ?float
    {
        if ($score === null) {
            return null;
        }

        $scale = $definition['derived_metrics']['temperature']['culture_scale'] ?? [];
        $min = (float) ($scale['min'] ?? 1);
        $max = (float) ($scale['max'] ?? 9);

        if ($max <= $min) {
            return round($score, 2);
        }

        $clamped = max($min, min($max, $score));
        $normalized = (($clamped - $min) / ($max - $min)) * 10;

        return round($normalized, 2);
    }

    protected function weightedIndicatorScore(array $indicators, array $definition): ?float
    {
        if (empty($indicators)) {
            return null;
        }

        $config = $this->indicatorConfiguration($definition);
        $sum = 0;
        $weight = 0;

        foreach ($indicators as $indicator) {
            $value = $indicator['satisfaction'] ?? $indicator['current'] ?? null;
            if ($value === null) {
                continue;
            }

            $key = $indicator['key'] ?? null;
            $indicatorWeight = 1;
            if ($key && isset($config[$key]['weight'])) {
                $indicatorWeight = max(0, (float) $config[$key]['weight']);
            }

            $sum += $value * $indicatorWeight;
            $weight += $indicatorWeight;
        }

        if ($weight === 0) {
            return null;
        }

        return round($sum / $weight, 2);
    }

    public function companySetupSummary(int $companyId): array
    {
        $summary = [
            'has_company_context' => $companyId > 0,
            'member_count' => 0,
            'recipient_count' => 0,
            'department_count' => 0,
            'billing_status' => 'none',
            'billing_label' => SurveyWaveAutomation::billingStatusLabel('none'),
            'billing_allows_scheduling' => false,
            'plan_label' => SurveyWaveAutomation::planLabel(null),
            'drip_available' => false,
            'can_manage_survey_content' => false,
            'survey_content_owner' => 'workfit_admin',
            'has_live_survey' => false,
            'live_survey' => null,
            'wave_count' => 0,
            'dispatched_wave_count' => 0,
            'has_dispatched_wave' => false,
            'latest_wave' => null,
            'assignment_count' => 0,
            'response_count' => 0,
        ];

        if ($companyId <= 0) {
            return $summary;
        }

        $manager = CompanyBilling::manager($companyId);
        $billingStatus = CompanyBilling::status($companyId);
        $activeVersion = SurveyVersion::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first(['id', 'version', 'title']);
        $latestWave = SurveyWave::query()
            ->where('company_id', $companyId)
            ->orderByRaw('COALESCE(last_dispatched_at, opens_at, due_at, created_at) DESC')
            ->orderByDesc('id')
            ->first([
                'id',
                'label',
                'status',
                'kind',
                'cadence',
                'survey_version_id',
                'opens_at',
                'due_at',
                'last_dispatched_at',
            ]);

        $memberCount = (int) DB::table('company_worker')
            ->where('company_id', $companyId)
            ->count();
        $recipientCount = (int) DB::table('company_worker')
            ->where('company_id', $companyId)
            ->whereIn('role', [2, 3, 4])
            ->count();
        $departmentCount = (int) DB::table('company_department')
            ->where('company_id', $companyId)
            ->count();
        $waveCount = (int) SurveyWave::query()
            ->where('company_id', $companyId)
            ->count();
        $dispatchedWaveCount = (int) SurveyWave::query()
            ->where('company_id', $companyId)
            ->whereNotNull('last_dispatched_at')
            ->count();
        $assignmentCount = (int) SurveyAssignment::query()
            ->from('survey_assignments as sa')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sa.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->count('sa.id');
        $responseCount = (int) SurveyResponse::query()
            ->from('survey_responses as sr')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sr.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->whereNotNull('sr.submitted_at')
            ->count('sr.id');

        return array_merge($summary, [
            'member_count' => $memberCount,
            'recipient_count' => $recipientCount,
            'department_count' => $departmentCount,
            'billing_status' => $billingStatus,
            'billing_label' => SurveyWaveAutomation::billingStatusLabel($billingStatus),
            'billing_allows_scheduling' => CompanyBilling::allowsScheduling($companyId),
            'plan_label' => SurveyWaveAutomation::planLabel($manager?->tariff),
            'drip_available' => CompanyBilling::hasFeature($companyId, 'recurring_waves'),
            'has_live_survey' => $activeVersion !== null,
            'live_survey' => $activeVersion
                ? [
                    'id' => $activeVersion->id,
                    'version' => $activeVersion->version,
                    'title' => $activeVersion->title,
                ]
                : null,
            'wave_count' => $waveCount,
            'dispatched_wave_count' => $dispatchedWaveCount,
            'has_dispatched_wave' => $dispatchedWaveCount > 0,
            'latest_wave' => $latestWave
                ? [
                    'id' => $latestWave->id,
                    'label' => $latestWave->label,
                    'status' => $latestWave->status,
                    'kind' => $latestWave->kind,
                    'cadence' => $latestWave->cadence,
                    'survey_version_id' => $latestWave->survey_version_id,
                    'opens_at' => $latestWave->opens_at?->toIso8601String(),
                    'due_at' => $latestWave->due_at?->toIso8601String(),
                    'last_dispatched_at' => $latestWave->last_dispatched_at?->toIso8601String(),
                ]
                : null,
            'assignment_count' => $assignmentCount,
            'response_count' => $responseCount,
        ]);
    }

    public function availableWavesForCompany(int $companyId): array
    {
        $options = [];

        $waves = SurveyWave::with('surveyVersion:id,version')
            ->where('company_id', $companyId)
            ->orderByRaw('COALESCE(due_at, opens_at, created_at) DESC')
            ->orderByDesc('id')
            ->get();

        foreach ($waves as $wave) {
            $key = "wave:{$wave->id}";
            $label = $wave->label ?: "Wave {$wave->id}";
            $versionSuffix = $wave->surveyVersion?->version ? " (v{$wave->surveyVersion->version})" : '';
            $options[$key] = "{$label}{$versionSuffix}";
        }

        // Support historical rows created before wave records existed.
        $legacyLabels = SurveyResponse::query()
            ->from('survey_responses as sr')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sr.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->whereNotNull('sr.submitted_at')
            ->whereNull('sr.survey_wave_id')
            ->whereNotNull('sr.wave_label')
            ->where('sr.wave_label', '!=', '')
            ->select('sr.wave_label')
            ->distinct()
            ->orderByDesc('sr.wave_label')
            ->pluck('sr.wave_label');

        foreach ($legacyLabels as $label) {
            $key = 'label:'.$label;
            if (! array_key_exists($key, $options)) {
                $options[$key] = $label;
            }
        }

        $legacyVersionIds = SurveyResponse::query()
            ->from('survey_responses as sr')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sr.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->whereNotNull('sr.submitted_at')
            ->whereNull('sr.survey_wave_id')
            ->whereNull('sr.wave_label')
            ->whereNotNull('sr.survey_version_id')
            ->select('sr.survey_version_id')
            ->distinct()
            ->pluck('sr.survey_version_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($legacyVersionIds->isNotEmpty()) {
            $versionsById = SurveyVersion::whereIn('id', $legacyVersionIds)->pluck('version', 'id');
            foreach ($legacyVersionIds as $versionId) {
                $key = "version:{$versionId}";
                if (! array_key_exists($key, $options)) {
                    $versionLabel = $versionsById[$versionId] ?? (string) $versionId;
                    $options[$key] = "Version {$versionLabel}";
                }
            }
        }

        return $options;
    }

    protected function average(Collection $values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 2);
    }

    protected function teamCultureAnalytics(Collection $answers, array $definition): array
    {
        $config = $this->cultureConfiguration($definition);
        $positiveKeys = $config['positive'] ?? [];
        $negativeKeys = $config['negative'] ?? [];
        if (empty($positiveKeys) && empty($negativeKeys)) {
            return [];
        }

        $negativeLookup = array_fill_keys($negativeKeys, true);
        $scale = $config['scale'] ?? [];
        $scaleMin = (float) ($scale['min'] ?? 1);
        $scaleMax = (float) ($scale['max'] ?? 9);

        $positiveValues = collect();
        $negativeValues = collect();
        $normalizedValuesByQid = [];
        $items = [];

        foreach ($answers as $answer) {
            $key = $answer->question_key;
            $value = $this->answerNumericValue($answer);
            if ($value === null) {
                continue;
            }

            if (in_array($key, $positiveKeys, true)) {
                $positiveValues->push($value);
                $items[] = ['qid' => $key, 'value' => $value, 'polarity' => 'positive'];
            } elseif (in_array($key, $negativeKeys, true)) {
                $negativeValues->push($value);
                $items[] = ['qid' => $key, 'value' => $value, 'polarity' => 'negative'];
            } else {
                continue;
            }

            $normalized = isset($negativeLookup[$key])
                ? $this->reverseScore($value, $scaleMin, $scaleMax)
                : $value;
            $normalizedValuesByQid[$key][] = $normalized;
        }

        if ($positiveValues->isEmpty() && $negativeValues->isEmpty()) {
            return [];
        }

        $positiveAvg = $this->average($positiveValues);
        $negativeAvg = $this->average($negativeValues);
        $score = null;
        if ($positiveAvg !== null && $negativeAvg !== null) {
            $score = round($positiveAvg - $negativeAvg, 2);
        }

        $dimensionConfig = $config['dimensions'] ?? [];
        $dimensionScores = [];
        $weightedSum = 0.0;
        $weightedTotal = 0.0;

        foreach ($dimensionConfig as $dimensionKey => $dimension) {
            $questionIds = $dimension['questions'] ?? [];
            $weight = max(0, (float) ($dimension['weight'] ?? 1));
            if (empty($questionIds)) {
                continue;
            }

            $values = collect($questionIds)
                ->flatMap(fn ($qid) => $normalizedValuesByQid[$qid] ?? [])
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (float) $value)
                ->values();

            $minimumValid = max(1, (int) ceil(
                count($questionIds) * (float) ($dimension['minimum_valid_item_ratio'] ?? 0.80)
            ));
            if ($values->count() < $minimumValid) {
                continue;
            }

            $avg = $this->average($values);
            $dimensionScores[$dimensionKey] = [
                'label' => $dimension['label'] ?? $dimensionKey,
                'weight' => $weight,
                'average' => $avg,
                'count' => $values->count(),
            ];

            if ($avg !== null && $weight > 0) {
                $weightedSum += $avg * $weight;
                $weightedTotal += $weight;
            }
        }

        if ($weightedTotal > 0) {
            $evaluation = round($weightedSum / $weightedTotal, 2);
        } else {
            $allNormalized = collect($normalizedValuesByQid)->flatten();
            $evaluation = $this->average($allNormalized);
        }

        return [
            'score' => $score,
            'positive' => $positiveAvg,
            'negative' => $negativeAvg,
            'items' => $items,
            'dimensions' => $dimensionScores,
            'evaluation' => $evaluation,
        ];
    }

    protected function teamCultureEvaluation(array $teamCulture): ?float
    {
        if (array_key_exists('evaluation', $teamCulture) && $teamCulture['evaluation'] !== null) {
            return (float) $teamCulture['evaluation'];
        }

        return isset($teamCulture['score']) ? (float) $teamCulture['score'] : null;
    }

    protected function reverseScore(float $value, float $min, float $max): float
    {
        if ($max <= $min) {
            return $value;
        }

        return round(($min + $max) - $value, 2);
    }

    protected function impactAnalytics(Collection $answers, array $definition): array
    {
        $config = $this->impactConfiguration($definition);
        if (empty($config)) {
            return [];
        }

        $series = [];
        foreach ($config as $key => $qids) {
            $values = $answers->filter(fn ($answer) => in_array($answer->question_key, $qids, true))
                ->map(fn ($answer) => $this->answerNumericValue($answer))
                ->filter(fn ($value) => $value !== null);

            $series[$key] = $this->average($values);
        }

        return $series;
    }

    protected function aggregateAttributes(Collection $answers, array $definition): Collection
    {
        $attributesConfig = $this->opportunityConfiguration($definition);
        $stats = [];

        foreach ($answers as $answer) {
            $qid = $answer->question_key;
            if (! preg_match('/^(WCA_[A-Z]+)_(A|B|C)$/', $qid, $matches)) {
                continue;
            }

            $base = $matches[1];
            $role = $matches[2];
            $value = $this->answerNumericValue($answer);
            if ($value === null) {
                continue;
            }

            $entry = $stats[$base] ?? [
                'key' => $base,
                'label' => $answer->metadata['attribute_label'] ?? $attributesConfig[$base]['label'] ?? $base,
                'indicator' => $attributesConfig[$base]['indicator'] ?? null,
                'current_sum' => 0,
                'current_count' => 0,
                'ideal_sum' => 0,
                'ideal_count' => 0,
                'desire_sum' => 0,
                'desire_count' => 0,
            ];

            if ($role === 'A') {
                $entry['current_sum'] += $value;
                $entry['current_count']++;
            } elseif ($role === 'B') {
                $entry['ideal_sum'] += $value;
                $entry['ideal_count']++;
            } elseif ($role === 'C') {
                $entry['desire_sum'] += $value;
                $entry['desire_count']++;
            }

            $stats[$base] = $entry;
        }

        return collect($stats)->map(function ($entry) {
            $current = $entry['current_count'] ? $entry['current_sum'] / $entry['current_count'] : null;
            $ideal = $entry['ideal_count'] ? $entry['ideal_sum'] / $entry['ideal_count'] : null;
            $desire = $entry['desire_count'] ? $entry['desire_sum'] / $entry['desire_count'] : null;

            return [
                'key' => $entry['key'],
                'label' => $entry['label'],
                'indicator' => $entry['indicator'],
                'current' => $current,
                'ideal' => $ideal,
                'desire' => $desire,
                'gap' => ($current !== null && $ideal !== null) ? round($ideal - $current, 2) : null,
            ];
        })->filter(fn ($row) => $row['current'] !== null || $row['ideal'] !== null);
    }

    protected function aggregateRespondentAttributes(Collection $respondents, int $totalN, int $minimumN): Collection
    {
        $rows = $respondents
            ->flatMap(function ($respondent) {
                return $respondent['attributes']->values()->map(
                    fn ($row) => [...$row, '_response_id' => $respondent['response_id']]
                );
            })
            ->groupBy('key');

        return $rows->map(function (Collection $group, string $key) use ($totalN, $minimumN) {
            $first = $group->first();
            $values = [];
            $missingness = [];
            foreach (['current', 'ideal', 'desire'] as $field) {
                $valid = $group->pluck($field)->filter(fn ($value) => $value !== null);
                $values[$field] = $this->average($valid);
                $missingness[$field] = [
                    'valid_n' => $valid->count(),
                    'missing_n' => max(0, $totalN - $valid->count()),
                    'missing_rate' => $totalN > 0
                        ? round(max(0, $totalN - $valid->count()) / $totalN, 4)
                        : null,
                ];
            }

            $paired = $group->filter(
                fn ($row) => ($row['current'] ?? null) !== null
                    && ($row['ideal'] ?? null) !== null
            );
            $validN = $paired->pluck('_response_id')->unique()->count();
            $eligible = $validN >= $minimumN;
            $pairedCurrent = $this->average($paired->pluck('current'));
            $pairedIdeal = $this->average($paired->pluck('ideal'));

            return [
                'key' => $key,
                'label' => $first['label'] ?? $key,
                'indicator' => $first['indicator'] ?? null,
                // Current, ideal, and the derived gap always use the same
                // complete-case respondents. Disjoint samples cannot satisfy N.
                'current' => $eligible ? $pairedCurrent : null,
                'ideal' => $eligible ? $pairedIdeal : null,
                'desire' => $missingness['desire']['valid_n'] >= $minimumN ? $values['desire'] : null,
                'gap' => $eligible && $pairedCurrent !== null && $pairedIdeal !== null
                    ? round($pairedIdeal - $pairedCurrent, 2)
                    : null,
                'valid_n' => $validN,
                'availability' => $eligible ? 'eligible' : 'suppressed',
                'missingness' => $missingness,
            ];
        })->values();
    }

    protected function aggregateRespondentIndicators(Collection $respondents, int $totalN, int $minimumN): array
    {
        return $respondents
            ->flatMap(fn ($respondent) => $respondent['indicators'])
            ->groupBy('key')
            ->map(function (Collection $group, string $key) use ($totalN, $minimumN) {
                $first = $group->first();
                $row = [
                    'key' => $key,
                    'label' => $first['label'] ?? $key,
                ];
                foreach (['current', 'ideal', 'desire', 'gap', 'satisfaction'] as $field) {
                    $row[$field] = $this->average(
                        $group->pluck($field)->filter(fn ($value) => $value !== null)
                    );
                }
                $row['valid_n'] = $group->whereNotNull('satisfaction')->count();
                $row['availability'] = $row['valid_n'] >= $minimumN ? 'eligible' : 'suppressed';
                if ($row['availability'] !== 'eligible') {
                    foreach (['current', 'ideal', 'desire', 'gap', 'satisfaction'] as $field) {
                        $row[$field] = null;
                    }
                }
                $row['missingness'] = $totalN > 0
                    ? round(($totalN - $row['valid_n']) / $totalN, 4)
                    : null;

                return $row;
            })
            ->sortByDesc(fn ($row) => $row['gap'] ?? -INF)
            ->values()
            ->all();
    }

    protected function aggregateRespondentCulture(Collection $respondents, int $totalN, int $minimumN): array
    {
        $cultures = $respondents->pluck('team_culture')->filter(fn ($row) => $row !== []);
        if ($cultures->isEmpty()) {
            return [];
        }
        if ($cultures->count() < $minimumN) {
            return [
                'availability' => 'suppressed',
                'valid_n' => $cultures->count(),
                'minimum_n' => $minimumN,
                'missingness' => $totalN > 0
                    ? round(($totalN - $cultures->count()) / $totalN, 4)
                    : null,
            ];
        }

        $result = [];
        foreach (['score', 'positive', 'negative', 'evaluation'] as $field) {
            $result[$field] = $this->average(
                $cultures->pluck($field)->filter(fn ($value) => $value !== null)
            );
        }

        $items = $cultures->flatMap(fn ($culture) => $culture['items'] ?? [])
            ->groupBy('qid')
            ->map(function (Collection $group, string $qid) {
                return [
                    'qid' => $qid,
                    'value' => $this->average($group->pluck('value')),
                    'valid_n' => $group->count(),
                    'polarity' => $group->first()['polarity'] ?? null,
                ];
            })->values()->all();
        $dimensions = $cultures->flatMap(function ($culture) {
            return collect($culture['dimensions'] ?? [])->map(
                fn ($dimension, $key) => ['key' => $key, ...$dimension]
            );
        })->groupBy('key')->map(function (Collection $group) use ($totalN) {
            $first = $group->first();

            return [
                'label' => $first['label'],
                'weight' => $first['weight'],
                'average' => $this->average($group->pluck('average')),
                'valid_n' => $group->count(),
                'missingness' => $totalN > 0
                    ? round(($totalN - $group->count()) / $totalN, 4)
                    : null,
            ];
        })->all();

        return [
            ...$result,
            'availability' => 'eligible',
            'valid_n' => $cultures->count(),
            'missingness' => $totalN > 0
                ? round(($totalN - $cultures->count()) / $totalN, 4)
                : null,
            'items' => $items,
            'dimensions' => $dimensions,
        ];
    }

    protected function aggregateRespondentImpact(
        Collection $respondents,
        int $totalN,
        int $minimumN,
        array $definition
    ): array {
        $impacts = $respondents->pluck('impact')->filter(fn ($row) => $row !== []);
        if ($impacts->isEmpty()) {
            return [];
        }

        $result = [];
        foreach (array_keys($this->impactConfiguration($definition)) as $field) {
            $valid = $impacts->pluck($field)->filter(fn ($value) => $value !== null);
            $result[$field] = $valid->count() >= $minimumN ? $this->average($valid) : null;
            $result["{$field}_valid_n"] = $valid->count();
            $result["{$field}_missingness"] = $totalN > 0
                ? round(($totalN - $valid->count()) / $totalN, 4)
                : null;
        }

        return $result;
    }

    protected function gapChartDataset(Collection $attributes, int $limit = 10): array
    {
        return $attributes
            ->filter(fn ($row) => ($row['availability'] ?? 'eligible') === 'eligible' && $row['gap'] !== null)
            ->sortByDesc(fn ($row) => $row['gap'] ?? -INF)
            ->take($limit)
            ->values()
            ->map(fn ($row) => [
                'label' => $row['label'],
                'current' => $row['current'],
                'ideal' => $row['ideal'],
                'gap' => $row['gap'],
            ])
            ->all();
    }

    protected function teamScatterDataset(Collection $responses, array $definition): array
    {
        if ($responses->isEmpty()) {
            return [];
        }

        $workers = $this->companyWorkersByEmail($responses);
        $userMetrics = collect();

        foreach ($responses as $response) {
            $answers = $response->answers ?? collect();
            if ($answers->isEmpty()) {
                continue;
            }

            $attributes = $this->aggregateAttributes($answers, $definition);
            if ($attributes->isEmpty()) {
                continue;
            }

            $indicators = $this->indicatorSatisfaction($attributes, $definition);
            $indicatorScore = $this->weightedIndicatorScore($indicators, $definition);
            $cultureScore = $this->teamCultureEvaluation(
                $this->teamCultureAnalytics($answers, $definition)
            );
            if ($indicatorScore === null || $cultureScore === null) {
                continue;
            }

            $user = $response->user;
            $cohort = $this->cohortForResponse($response, $workers);

            $userMetrics->push([
                'indicator' => $indicatorScore,
                'culture' => $cultureScore,
                'department' => $cohort['department'] ?? null,
                'team' => $cohort['team'] ?? null,
                'company' => $cohort['company_title'] ?? $user?->company_title ?? 'Company',
            ]);
        }

        if ($userMetrics->isEmpty()) {
            return [];
        }

        $points = [];
        $companyPoint = $this->summarizeScatterGroup($userMetrics, 'Company', 'company');
        if ($companyPoint) {
            $points[] = $companyPoint;
        }

        $departmentGroups = $userMetrics->groupBy('department')
            ->filter(fn ($group, $department) => ! empty($department))
            ->all();
        $departmentPrivacy = $this->samplePolicy->visibleGroups($departmentGroups);
        collect($departmentPrivacy['visible'])
            ->each(function ($group, $department) use (&$points) {
                $summary = $this->summarizeScatterGroup($group, "Dept: {$department}", 'department');
                if ($summary) {
                    $points[] = $summary;
                }
            });

        $teamGroups = $userMetrics->groupBy('team')
            ->filter(fn ($group, $team) => ! empty($team))
            ->all();
        $teamPrivacy = $this->samplePolicy->visibleGroups($teamGroups);
        collect($teamPrivacy['visible'])
            ->each(function ($group, $team) use (&$points) {
                $summary = $this->summarizeScatterGroup($group, "Team: {$team}", 'team');
                if ($summary) {
                    $points[] = $summary;
                }
            });

        return $points;
    }

    protected function companyWorkersByEmail(Collection $responses)
    {
        $emails = $responses->pluck('user.email')->filter()->unique()->all();
        if (empty($emails)) {
            return [];
        }

        return DB::table('company_worker')
            ->whereIn('email', $emails)
            ->get()
            ->keyBy('email')
            ->all();
    }

    protected function cohortForResponse(SurveyResponse $response, array $legacyWorkers = []): array
    {
        if (is_array($response->cohort_snapshot) && $response->cohort_snapshot !== []) {
            return $response->cohort_snapshot;
        }

        $email = $response->user?->email;
        $worker = $email && isset($legacyWorkers[$email]) ? $legacyWorkers[$email] : null;

        return [
            'department' => $worker->department ?? null,
            'team' => $worker->supervisor ?? null,
            'company_title' => $worker->company_title ?? null,
        ];
    }

    protected function summarizeScatterGroup(Collection $group, string $label, string $level): ?array
    {
        $indicator = $this->average($group->pluck('indicator')->filter());
        $culture = $this->average($group->pluck('culture')->filter());
        if ($indicator === null || $culture === null) {
            return null;
        }

        return [
            'label' => $label,
            'level' => $level,
            'count' => $group->count(),
            'indicator' => $indicator,
            'culture' => $culture,
        ];
    }

    protected function answerNumericValue(SurveyAnswer $answer): ?float
    {
        if ($answer->value_numeric !== null) {
            return (float) $answer->value_numeric;
        }

        if (is_numeric($answer->value)) {
            return (float) $answer->value;
        }

        return null;
    }

    protected function reliabilitySummary(Collection $responses, array $definition): array
    {
        $culture = $this->cultureConfiguration($definition);
        $negative = array_fill_keys($culture['negative'] ?? [], true);
        $scale = $culture['scale'] ?? ['min' => 1, 'max' => 9];
        $result = [];

        foreach ($culture['dimensions'] ?? [] as $key => $dimension) {
            $qids = array_values($dimension['questions'] ?? []);
            if (count($qids) < 2) {
                continue;
            }

            $matrix = $responses->map(function (SurveyResponse $response) use ($qids, $negative, $scale) {
                $byQid = $response->answers->keyBy('question_key');
                $row = [];
                foreach ($qids as $qid) {
                    $answer = $byQid->get($qid);
                    $value = $answer ? $this->answerNumericValue($answer) : null;
                    if ($value === null) {
                        return null;
                    }
                    $row[] = isset($negative[$qid])
                        ? $this->reverseScore($value, (float) $scale['min'], (float) $scale['max'])
                        : $value;
                }

                return $row;
            })->filter()->values();

            $completeN = $matrix->count();
            $alpha = $completeN >= 10 ? $this->cronbachAlpha($matrix) : null;
            if ($completeN < 10) {
                $status = 'insufficient_n_for_internal_consistency';
            } elseif ($alpha === null) {
                $status = 'indeterminate';
            } elseif ($alpha >= 0.70) {
                $status = 'acceptable';
            } elseif ($alpha >= 0.60) {
                $status = 'caution';
            } else {
                $status = 'low';
            }

            $result[$key] = [
                'label' => $dimension['label'] ?? $key,
                'complete_case_n' => $completeN,
                'item_count' => count($qids),
                'cronbach_alpha' => $alpha,
                'status' => $status,
                'threshold_status' => 'provisional_prevalidation',
            ];
        }

        return $result;
    }

    protected function cronbachAlpha(Collection $matrix): ?float
    {
        $rows = $matrix->all();
        $itemCount = count($rows[0] ?? []);
        if ($itemCount < 2 || count($rows) < 2) {
            return null;
        }

        $itemVariances = 0.0;
        for ($column = 0; $column < $itemCount; $column++) {
            $itemVariances += $this->sampleVariance(collect($rows)->pluck($column)->map(fn ($v) => (float) $v));
        }
        $totals = collect($rows)->map(fn ($row) => array_sum($row));
        $totalVariance = $this->sampleVariance($totals);
        if ($totalVariance <= 0) {
            return null;
        }

        return round(($itemCount / ($itemCount - 1)) * (1 - ($itemVariances / $totalVariance)), 3);
    }

    protected function sampleVariance(Collection $values): float
    {
        $count = $values->count();
        if ($count < 2) {
            return 0.0;
        }
        $mean = $values->avg();

        return (float) ($values->sum(fn ($value) => (($value - $mean) ** 2)) / ($count - 1));
    }

    protected function analyticsQuestionKeys(): array
    {
        static $keys = null;
        if ($keys !== null) {
            return $keys;
        }

        $wcaKeys = [];
        foreach (array_keys(config('survey.work_content_attributes', [])) as $attributeKey) {
            $wcaKeys[] = "{$attributeKey}_A";
            $wcaKeys[] = "{$attributeKey}_B";
            $wcaKeys[] = "{$attributeKey}_C";
        }

        $teamCultureKeys = array_merge(
            config('survey.team_culture.positive', []),
            config('survey.team_culture.negative', [])
        );

        $impactKeys = collect(config('survey.impact_series', []))->flatten(1)->all();

        $keys = array_values(array_unique(array_filter(
            array_merge($wcaKeys, $teamCultureKeys, $impactKeys),
            fn ($value) => is_string($value) && $value !== ''
        )));

        return $keys;
    }

    public function getTrendData(
        int $companyId,
        string $metric = 'workfit_indicator',
        array $filters = []
    ): array {
        // Prefer scheduled/completed waves with due dates.
        $waves = SurveyWave::where('company_id', $companyId)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->get();

        // Fallback for legacy/manual waves that never had due_at but already have submissions.
        if ($waves->isEmpty()) {
            $waveIds = SurveyResponse::query()
                ->whereNotNull('submitted_at')
                ->whereNotNull('survey_wave_id')
                ->whereHas('user', fn ($query) => $query->where('company_id', $companyId))
                ->orderBy('submitted_at')
                ->pluck('survey_wave_id')
                ->unique()
                ->values();

            if ($waveIds->isNotEmpty()) {
                $waves = SurveyWave::whereIn('id', $waveIds)
                    ->orderByRaw('COALESCE(due_at, opens_at) asc')
                    ->orderBy('id')
                    ->get();
            }
        }

        $labels = [];
        $data = [];
        $points = [];
        $referenceInstrumentHash = null;
        $referenceMetricHash = null;

        foreach ($waves as $wave) {
            $cycles = $wave->cycles()->orderBy('sequence')->get();
            if ($cycles->isEmpty()) {
                $points[] = [
                    'wave_id' => $wave->id,
                    'cycle_id' => null,
                    'label' => $wave->label ?: "Wave {$wave->id}",
                    'value' => null,
                    'sample' => null,
                    'comparable' => false,
                    'comparability_reason' => 'Cycle provenance is missing.',
                    'instrument_hash' => null,
                    'metric_definition_hash' => null,
                ];

                continue;
            }

            foreach ($cycles as $cycle) {
                $responses = SurveyResponse::with(['answers', 'user'])
                    ->where('survey_wave_id', $wave->id)
                    ->where('survey_wave_cycle_id', $cycle->id)
                    ->whereNotNull('submitted_at')
                    ->whereHas('user', fn ($query) => $query->where('company_id', $companyId))
                    ->get();
                $responses = $this->filterResponses($responses, $filters);
                $sample = $this->samplePolicy->assess(
                    $companyId,
                    $responses,
                    [
                        ...$filters,
                        'wave' => "wave:{$wave->id}",
                        'cycle_id' => $cycle->id,
                    ]
                );
                $registry = $this->registryForCycle($cycle);
                $metricValues = $registry
                    ? $this->metricValues($responses, $metric, $registry->definition)
                    : collect();
                $sample['metric_valid_n'] = $metricValues->count();
                $sample['metric_minimum_n'] = $sample['minimum_n'];
                $sample['metric_status'] = $metricValues->count() >= $sample['minimum_n']
                    ? 'eligible'
                    : 'suppressed';

                $instrumentHash = $cycle->instrument_hash;
                $metricHash = $cycle->metric_definition_hash;
                $comparable = filled($instrumentHash) && filled($metricHash) && $registry !== null;
                $comparabilityReason = $comparable ? null : 'Cycle provenance is incomplete.';
                if ($comparable
                    && $referenceInstrumentHash !== null
                    && ($instrumentHash !== $referenceInstrumentHash || $metricHash !== $referenceMetricHash)) {
                    $comparable = false;
                    $comparabilityReason = 'Instrument or metric definition changed.';
                }

                $baseLabel = $wave->label
                    ?? optional($wave->due_at)->format('M Y')
                    ?? optional($wave->opens_at)->format('M Y')
                    ?? "Wave {$wave->id}";
                $label = $cycles->count() > 1
                    ? "{$baseLabel} · Cycle {$cycle->sequence}"
                    : $baseLabel;
                $eligible = $sample['status'] === 'eligible'
                    && $sample['metric_status'] === 'eligible'
                    && $comparable;
                $score = $eligible ? $this->average($metricValues) : null;

                $points[] = [
                    'wave_id' => $wave->id,
                    'cycle_id' => $cycle->id,
                    'label' => $label,
                    'value' => $score,
                    'sample' => $sample,
                    'comparable' => $comparable,
                    'comparability_reason' => $comparabilityReason,
                    'instrument_hash' => $instrumentHash,
                    'metric_definition_hash' => $metricHash,
                ];

                if ($score !== null) {
                    $labels[] = $label;
                    $data[] = $score;
                    $referenceInstrumentHash ??= $instrumentHash;
                    $referenceMetricHash ??= $metricHash;
                }
            }
        }

        return [
            'points' => $points,
            'metric' => $metric,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $metric === 'culture'
                        ? 'Culture Index'
                        : 'WorkFit Indicator (pre-validation)',
                    'data' => $data,
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.2)',
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function metricValues(
        Collection $responses,
        string $metric,
        array $definition
    ): Collection {
        return $responses->map(function (SurveyResponse $response) use ($metric, $definition) {
            if ($metric === 'workfit_indicator') {
                $attributes = $this->aggregateAttributes($response->answers, $definition);

                return $this->weightedIndicatorScore(
                    $this->indicatorSatisfaction($attributes, $definition),
                    $definition
                );
            }

            if ($metric === 'culture') {
                return $this->teamCultureEvaluation(
                    $this->teamCultureAnalytics($response->answers, $definition)
                );
            }

            return null;
        })->filter(fn ($value) => $value !== null)->values();
    }

    public function getComparisonData(
        int $companyId,
        int $waveId,
        string $dimension = 'department',
        array $filters = []
    ): array {
        $cycleId = SurveyResponse::query()
            ->where('survey_wave_id', $waveId)
            ->whereNotNull('survey_wave_cycle_id')
            ->whereNotNull('submitted_at')
            ->whereHas('user', fn ($query) => $query->where('company_id', $companyId))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->value('survey_wave_cycle_id');

        if (! $cycleId) {
            return $this->emptyComparisonDataset();
        }

        $cycle = SurveyWaveCycle::where('survey_wave_id', $waveId)->find($cycleId);
        $registry = $cycle ? $this->registryForCycle($cycle) : null;
        if (! $registry) {
            return $this->emptyComparisonDataset('provenance_incomplete');
        }
        $definition = $registry->definition;
        $responses = SurveyResponse::with(['answers', 'user'])
            ->where('survey_wave_id', $waveId)
            ->where('survey_wave_cycle_id', $cycleId)
            ->whereNotNull('submitted_at')
            ->whereHas('user', fn ($query) => $query->where('company_id', $companyId))
            ->get();
        $responses = $this->filterResponses($responses, $filters);

        if ($responses->isEmpty()) {
            return $this->emptyComparisonDataset();
        }

        $workers = $this->companyWorkersByEmail($responses);
        $groups = [];

        foreach ($responses as $response) {
            $cohort = $this->cohortForResponse($response, $workers);

            $key = 'Unknown';
            if ($dimension === 'department') {
                $key = $cohort['department'] ?? 'Unknown';
            } elseif ($dimension === 'team') {
                $key = $cohort['team'] ?? 'Unknown';
            }

            if (! isset($groups[$key])) {
                $groups[$key] = collect();
            }
            $groups[$key]->push($response);
        }

        $metricEligibleGroups = collect($groups)->map(function (Collection $group) use ($definition) {
            return $group->filter(function (SurveyResponse $response) use ($definition): bool {
                $single = collect([$response]);

                return $this->metricValues($single, 'workfit_indicator', $definition)->isNotEmpty()
                    && $this->metricValues($single, 'culture', $definition)->isNotEmpty();
            })->values();
        })->all();
        $privacy = $this->samplePolicy->visibleGroups($metricEligibleGroups);
        $groups = $privacy['visible'];

        $labels = [];
        $indicatorData = [];
        $cultureData = [];

        foreach ($groups as $key => $groupResponses) {
            $indicatorScore = $this->average(
                $this->metricValues($groupResponses, 'workfit_indicator', $definition)
            );
            $cultScore = $this->average($this->metricValues($groupResponses, 'culture', $definition));

            if ($indicatorScore !== null && $cultScore !== null) {
                $labels[] = $key;
                $indicatorData[] = $indicatorScore;
                $cultureData[] = $cultScore;
            }
        }

        return [
            'privacy' => [
                'minimum_n' => $privacy['minimum_n'],
                'suppressed_group_count' => count($privacy['suppressed']),
            ],
            'cycle_id' => (int) $cycleId,
            'metric_registry' => [
                'version' => $registry->version,
                'hash' => $registry->definition_hash,
            ],
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'WorkFit Indicator (pre-validation)',
                    'data' => $indicatorData,
                    'backgroundColor' => '#4f46e5',
                ],
                [
                    'label' => 'Culture',
                    'data' => $cultureData,
                    'backgroundColor' => '#10b981',
                ],
            ],
        ];
    }

    protected function emptyComparisonDataset(?string $reason = null): array
    {
        return [
            'privacy' => [
                'minimum_n' => (int) config('privacy.reporting.minimum_subgroup_n', 7),
                'suppressed_group_count' => 0,
            ],
            'availability' => $reason ?? 'empty',
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'WorkFit Indicator (pre-validation)',
                    'data' => [],
                    'backgroundColor' => '#4f46e5',
                ],
                [
                    'label' => 'Culture',
                    'data' => [],
                    'backgroundColor' => '#10b981',
                ],
            ],
        ];
    }
}
