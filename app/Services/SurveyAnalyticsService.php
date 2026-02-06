<?php

namespace App\Services;

use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyAnalyticsService
{
    public function workContentAnalyticsForUser(User $user): array
    {
        if (!$user->company_id) {
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

        if (!$companyId) {
            return [];
        }

        $responses = $this->latestResponsesWithAnswers($companyId, $filters['wave'] ?? null);
        if ($responses->isEmpty()) {
            return [];
        }

        $responses = $this->filterResponses($responses, $filters);
        if ($responses->isEmpty()) {
            return [];
        }

        $answers = $responses->flatMap(fn (SurveyResponse $response) => $response->answers);
        $attributes = $this->aggregateAttributes($answers);
        $indicators = $this->indicatorSatisfaction($attributes);
        $teamCulture = $this->teamCultureAnalytics($answers);
        $weightedIndicator = $this->weightedIndicatorScore($indicators);
        $teamCultureEval = $this->teamCultureEvaluation($teamCulture);
        $temperature = $this->temperatureIndex($weightedIndicator, $teamCultureEval);
        $impact = $this->impactAnalytics($answers);
        $gapChart = $this->gapChartDataset($attributes);
        $scatter = $this->teamScatterDataset($responses);

        return [
            'attributes' => $attributes->sortByDesc(fn ($row) => $row['gap'] ?? -INF)->values()->all(),
            'indicators' => $indicators,
            'temperature' => $temperature,
            'team_culture' => $teamCulture,
            'impact' => $impact,
            'gap_chart' => $gapChart,
            'team_scatter' => $scatter,
            'weighted_indicator' => $weightedIndicator,
            'team_culture_evaluation' => $teamCultureEval,
        ];
    }

    protected function filterResponses(Collection $responses, array $filters): Collection
    {
        $department = $filters['department'] ?? null;
        $team = $filters['team'] ?? null;
        if (!$department && !$team) {
            return $responses;
        }

        $workersByEmail = $this->companyWorkersByEmail($responses);

        return $responses->filter(function (SurveyResponse $response) use ($department, $team, $workersByEmail) {
            $user = $response->user;
            $email = $user?->email;
            $worker = $email && isset($workersByEmail[$email]) ? $workersByEmail[$email] : null;

            if ($department && (string) ($worker->department ?? '') !== (string) $department) {
                return false;
            }

            if ($team && (string) ($worker->supervisor ?? '') !== (string) $team) {
                return false;
            }

            return true;
        });
    }

    protected function latestResponseIdsForCompany(int $companyId, ?string $wave = null): Collection
    {
        $query = SurveyResponse::query()
            ->from('survey_responses as sr')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sr.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->whereNotNull('sr.submitted_at')
            ->selectRaw('MAX(sr.id) as id')
            ->groupBy('sr.user_id');

        if ($wave !== null && $wave !== '') {
            $query->leftJoin('survey_assignments as sa', 'sa.id', '=', 'sr.assignment_id');
            $this->applyWaveFilterToLatestResponsesQuery($query, (string) $wave);
        }

        return $query->pluck('id');
    }

    protected function latestResponsesWithAnswers(int $companyId, ?string $wave = null): Collection
    {
        $responseIds = $this->latestResponseIdsForCompany($companyId, $wave);
        if ($responseIds->isEmpty()) {
            return collect();
        }

        $analyticsQuestionKeys = $this->analyticsQuestionKeys();

        return SurveyResponse::with([
                'answers' => function ($query) use ($analyticsQuestionKeys) {
                    $query->select([
                        'id',
                        'response_id',
                        'question_key',
                        'value',
                        'value_numeric',
                        'metadata',
                    ]);

                    if (!empty($analyticsQuestionKeys)) {
                        $query->whereIn('question_key', $analyticsQuestionKeys);
                    }

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
            ])
            ->get();
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

            if (!empty($selector['wave_ids'])) {
                $appendCondition(function ($q) use ($selector) {
                    $q->whereIn('sr.survey_wave_id', $selector['wave_ids'])
                        ->orWhereIn('sa.survey_wave_id', $selector['wave_ids']);
                });
            }

            if (!empty($selector['version_ids'])) {
                $appendCondition(function ($q) use ($selector) {
                    $q->whereIn('sr.survey_version_id', $selector['version_ids'])
                        ->orWhereIn('sa.survey_version_id', $selector['version_ids']);
                });
            }

            if (!empty($selector['labels'])) {
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

    protected function indicatorSatisfaction(Collection $attributes): array
    {
        $indicatorConfig = config('survey.indicators', []);
        $grouped = [];

        foreach ($indicatorConfig as $key => $config) {
            $rows = $attributes->whereIn('key', $config['attributes'] ?? []);
            if ($rows->isEmpty()) {
                continue;
            }

            $current = $this->average($rows->pluck('current')->filter());
            $ideal = $this->average($rows->pluck('ideal')->filter());
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

    protected function temperatureIndex(?float $weightedIndicator, ?float $teamCultureEval): ?float
    {
        if ($weightedIndicator === null && $teamCultureEval === null) {
            return null;
        }

        if ($weightedIndicator === null) {
            return $this->normalizeCultureToTen($teamCultureEval);
        }

        if ($teamCultureEval === null) {
            return round($weightedIndicator, 2);
        }

        $weights = config('survey.temperature.weights', []);
        $indicatorWeight = max(0, (float) ($weights['indicator'] ?? 0.65));
        $cultureWeight = max(0, (float) ($weights['culture'] ?? 0.35));
        $totalWeight = $indicatorWeight + $cultureWeight;

        if ($totalWeight <= 0) {
            return round($weightedIndicator, 2);
        }

        $cultureOnTen = $this->normalizeCultureToTen($teamCultureEval);

        return round((($weightedIndicator * $indicatorWeight) + ($cultureOnTen * $cultureWeight)) / $totalWeight, 2);
    }

    protected function normalizeCultureToTen(?float $score): ?float
    {
        if ($score === null) {
            return null;
        }

        $scale = config('survey.team_culture_evaluation.scale', []);
        $min = (float) ($scale['min'] ?? 1);
        $max = (float) ($scale['max'] ?? 9);

        if ($max <= $min) {
            return round($score, 2);
        }

        $clamped = max($min, min($max, $score));
        $normalized = (($clamped - $min) / ($max - $min)) * 10;

        return round($normalized, 2);
    }

    protected function weightedIndicatorScore(array $indicators): ?float
    {
        if (empty($indicators)) {
            return null;
        }

        $config = config('survey.indicators', []);
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
            $key = 'label:' . $label;
            if (!array_key_exists($key, $options)) {
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
                if (!array_key_exists($key, $options)) {
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

    protected function teamCultureAnalytics(Collection $answers): array
    {
        $config = config('survey.team_culture', []);
        $positiveKeys = $config['positive'] ?? [];
        $negativeKeys = $config['negative'] ?? [];
        if (empty($positiveKeys) && empty($negativeKeys)) {
            return [];
        }

        $negativeLookup = array_fill_keys($negativeKeys, true);
        $scale = config('survey.team_culture_evaluation.scale', []);
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

        $dimensionConfig = config('survey.team_culture_evaluation.dimensions', []);
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

            if ($values->isEmpty()) {
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

    protected function impactAnalytics(Collection $answers): array
    {
        $config = config('survey.impact_series', []);
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

    protected function aggregateAttributes(Collection $answers): Collection
    {
        $attributesConfig = config('survey.work_content_attributes', []);
        $stats = [];

        foreach ($answers as $answer) {
            $qid = $answer->question_key;
            if (!preg_match('/^(WCA_[A-Z]+)_(A|B|C)$/', $qid, $matches)) {
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

    protected function gapChartDataset(Collection $attributes, int $limit = 10): array
    {
        return $attributes
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

    protected function teamScatterDataset(Collection $responses): array
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

            $attributes = $this->aggregateAttributes($answers);
            if ($attributes->isEmpty()) {
                continue;
            }

            $indicators = $this->indicatorSatisfaction($attributes);
            $indicatorScore = $this->weightedIndicatorScore($indicators);
            $cultureScore = $this->teamCultureEvaluation($this->teamCultureAnalytics($answers));
            if ($indicatorScore === null || $cultureScore === null) {
                continue;
            }

            $user = $response->user;
            $email = $user?->email;
            $worker = $email && isset($workers[$email]) ? $workers[$email] : null;

            $userMetrics->push([
                'indicator' => $indicatorScore,
                'culture' => $cultureScore,
                'department' => $worker->department ?? null,
                'team' => $worker->supervisor ?? null,
                'company' => $worker->company_title ?? $user?->company_title ?? 'Company',
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

        $userMetrics->groupBy('department')
            ->filter(fn ($group, $department) => !empty($department))
            ->each(function ($group, $department) use (&$points) {
                $summary = $this->summarizeScatterGroup($group, "Dept: {$department}", 'department');
                if ($summary) {
                    $points[] = $summary;
                }
            });

        $userMetrics->groupBy('team')
            ->filter(fn ($group, $team) => !empty($team))
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

    public function getTrendData(int $companyId, string $metric = 'engagement'): array
    {
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

        foreach ($waves as $wave) {
            // Get responses for this wave
            $responses = SurveyResponse::with(['answers', 'user'])
                ->where('survey_wave_id', $wave->id)
                ->whereNotNull('submitted_at')
                ->get();

            if ($responses->isEmpty()) {
                continue;
            }

            $answers = $responses->flatMap(fn ($r) => $r->answers);
            $score = null;

            if ($metric === 'engagement') {
                // Calculate weighted indicator for this wave
                $attributes = $this->aggregateAttributes($answers);
                $indicators = $this->indicatorSatisfaction($attributes);
                $score = $this->weightedIndicatorScore($indicators);
            } elseif ($metric === 'culture') {
                $culture = $this->teamCultureAnalytics($answers);
                $score = $this->teamCultureEvaluation($culture);
            }

            if ($score !== null) {
                $labels[] = $wave->label
                    ?? optional($wave->due_at)->format('M Y')
                    ?? optional($wave->opens_at)->format('M Y')
                    ?? "Wave {$wave->id}";
                $data[] = $score;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => ucfirst($metric) . ' Score',
                    'data' => $data,
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.2)',
                    'fill' => true,
                ]
            ]
        ];
    }

    public function getComparisonData(int $companyId, int $waveId, string $dimension = 'department'): array
    {
        $responses = SurveyResponse::with(['answers', 'user'])
            ->where('survey_wave_id', $waveId)
            ->whereNotNull('submitted_at')
            ->whereHas('user', fn ($query) => $query->where('company_id', $companyId))
            ->get();

        if ($responses->isEmpty()) {
            return $this->emptyComparisonDataset();
        }

        $workers = $this->companyWorkersByEmail($responses);
        $groups = [];

        foreach ($responses as $response) {
            $user = $response->user;
            $email = $user?->email;
            $worker = $email && isset($workers[$email]) ? $workers[$email] : null;
            
            $key = 'Unknown';
            if ($dimension === 'department') {
                $key = $worker->department ?? 'Unknown';
            } elseif ($dimension === 'team') {
                $key = $worker->supervisor ?? 'Unknown';
            }

            if (!isset($groups[$key])) {
                $groups[$key] = collect();
            }
            $groups[$key]->push($response);
        }

        $labels = [];
        $engagementData = [];
        $cultureData = [];

        foreach ($groups as $key => $groupResponses) {
            $answers = $groupResponses->flatMap(fn ($r) => $r->answers);
            
            // Engagement
            $attributes = $this->aggregateAttributes($answers);
            $indicators = $this->indicatorSatisfaction($attributes);
            $engScore = $this->weightedIndicatorScore($indicators);

            // Culture
            $culture = $this->teamCultureAnalytics($answers);
            $cultScore = $this->teamCultureEvaluation($culture) ?? 0;

            if ($engScore !== null) {
                $labels[] = $key;
                $engagementData[] = $engScore;
                $cultureData[] = $cultScore;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Engagement',
                    'data' => $engagementData,
                    'backgroundColor' => '#4f46e5',
                ],
                [
                    'label' => 'Culture',
                    'data' => $cultureData,
                    'backgroundColor' => '#10b981',
                ]
            ]
        ];
    }

    protected function emptyComparisonDataset(): array
    {
        return [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Engagement',
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
