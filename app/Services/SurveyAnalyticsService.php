<?php

namespace App\Services;

use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyAnalyticsService
{
    protected array $metricKeys = [
        'engagement' => 'engagement_score',
        'enablement' => 'enablement_score',
        'alignment' => 'alignment_score',
        'culture' => 'culture_score',
    ];

    protected array $qidMetricMap = [
        'QID1_1' => ['metric' => 'engagement', 'offset' => -0.5],
        'QID1_2' => ['metric' => 'engagement', 'offset' => 0],
        'QID1_3' => ['metric' => 'engagement', 'offset' => 0.5],
        'QID2_1' => ['metric' => 'engagement', 'offset' => -0.25],
        'QID2_2' => ['metric' => 'engagement', 'offset' => 0.25],
        'QID2_3' => ['metric' => 'engagement', 'offset' => 0.75],
        'QID2_4' => ['metric' => 'engagement', 'offset' => 0.1],
        'QID3_1' => ['metric' => 'engagement', 'offset' => -0.1],
        'QID3_2' => ['metric' => 'engagement', 'offset' => 0.15],
        'QID3_3' => ['metric' => 'engagement', 'offset' => 0.4],
        'QID3_4' => ['metric' => 'engagement', 'offset' => 0.6],
        'QID4_1' => ['metric' => 'engagement', 'offset' => 0],
        'QID7_1' => ['metric' => 'enablement', 'offset' => -0.3],
        'QID7_2' => ['metric' => 'enablement', 'offset' => 0.2],
        'QID7_3' => ['metric' => 'enablement', 'offset' => 0.6],
        'QID7_6' => ['metric' => 'enablement', 'offset' => -0.1],
        'QID8_1' => ['metric' => 'enablement', 'offset' => -0.4],
        'QID8_2' => ['metric' => 'enablement', 'offset' => -0.1],
        'QID8_3' => ['metric' => 'enablement', 'offset' => 0.2],
        'QID8_6' => ['metric' => 'enablement', 'offset' => 0.4],
        'QID8_7' => ['metric' => 'enablement', 'offset' => 0.5],
        'QID8_8' => ['metric' => 'enablement', 'offset' => 0.6],
        'QID8_9' => ['metric' => 'enablement', 'offset' => 0.8],
        'QID9_1' => ['metric' => 'enablement', 'offset' => -0.25],
        'QID9_2' => ['metric' => 'enablement', 'offset' => 0.25],
        'QID9_3' => ['metric' => 'enablement', 'offset' => 0.75],
        'QID9_6' => ['metric' => 'enablement', 'offset' => 0.4],
        'QID10_1' => ['metric' => 'alignment', 'offset' => -0.3],
        'QID10_2' => ['metric' => 'alignment', 'offset' => 0.2],
        'QID10_3' => ['metric' => 'alignment', 'offset' => 0.6],
        'QID11_1' => ['metric' => 'alignment', 'offset' => -0.4],
        'QID11_2' => ['metric' => 'alignment', 'offset' => 0],
        'QID11_3' => ['metric' => 'alignment', 'offset' => 0.5],
        'QID12_1' => ['metric' => 'alignment', 'offset' => -0.1],
        'QID14_1' => ['metric' => 'alignment', 'offset' => 0.2],
        'QID15_1' => ['metric' => 'alignment', 'offset' => 0.35],
        'QID18_1' => ['metric' => 'alignment', 'offset' => 0.45],
        'QID21_1' => ['metric' => 'alignment', 'offset' => 0.55],
        'QID24_1' => ['metric' => 'alignment', 'offset' => 0.65],
        'QID27_1' => ['metric' => 'alignment', 'offset' => 0.75],
        'QID30_1' => ['metric' => 'culture', 'offset' => -0.2],
        'QID30_3' => ['metric' => 'culture', 'offset' => 0.5],
        'QID31_1' => ['metric' => 'culture', 'offset' => -0.1],
        'QID31_3' => ['metric' => 'culture', 'offset' => 0.4],
        'QID32_1' => ['metric' => 'culture', 'offset' => 0.1],
        'QID32_3' => ['metric' => 'culture', 'offset' => 0.7],
        'QID35_1' => ['metric' => 'culture', 'offset' => -0.15],
        'QID38_1' => ['metric' => 'culture', 'offset' => 0.05],
        'QID41_1' => ['metric' => 'culture', 'offset' => 0.15],
        'QID44_1' => ['metric' => 'culture', 'offset' => 0.25],
        'QID49_1' => ['metric' => 'culture', 'offset' => 0.35],
        'QID50_1' => ['metric' => 'culture', 'offset' => 0.45],
        'QID52_1' => ['metric' => 'culture', 'offset' => 0.55],
        'QID54_1' => ['metric' => 'culture', 'offset' => 0.65],
        'QID55_1' => ['metric' => 'culture', 'offset' => 0.25],
        'QID60_1' => ['metric' => 'culture', 'offset' => 0.35],
    ];

    public function datasetForCompany(?int $companyId = null): array
    {
        $query = SurveyResponse::with(['answers', 'user'])->orderByDesc('submitted_at');
        if ($companyId) {
            $query->whereHas('user', fn ($q) => $q->where('company_id', $companyId));
        }

        $responses = $query->get();
        if ($responses->isEmpty()) {
            return ['data' => [], 'time' => now()->valueOf()];
        }

        $userEmails = $responses->pluck('user.email')->filter()->unique()->all();
        $workers = DB::table('company_worker')
            ->whereIn('email', $userEmails)
            ->get()
            ->keyBy('email');

        $payloads = $responses->map(function (SurveyResponse $response) use ($workers) {
            $user = $response->user;
            $worker = $user && $user->email ? $workers->get($user->email) : null;
            $answers = $response->answers->pluck('value_numeric', 'question_key')->toArray();
            foreach ($response->answers as $answer) {
                if (!isset($answers[$answer->question_key])) {
                    $answers[$answer->question_key] = is_numeric($answer->value) ? (float) $answer->value : $answer->value;
                }
            }

            $values = $this->buildValues($answers, [
                'company' => $user?->company_title ?? ($worker->company_title ?? 'Unknown Company'),
                'department' => $worker->department ?? 'General',
                'supervisor' => $worker->supervisor ?? '',
                'email' => $user?->email ?? '',
            ]);

            return ['values' => $values];
        });

        return [
            'data' => $payloads->map(fn ($payload) => json_encode($payload))->all(),
            'time' => now()->valueOf(),
        ];
    }

    protected function buildValues(array $answers, array $context): array
    {
        $metrics = $this->metricScores($answers);
        $values = [];

        foreach ($this->qidMetricMap as $qid => $definition) {
            $metric = $definition['metric'];
            $offset = $definition['offset'] ?? 0;
            $values[$qid] = $this->scoreWithOffset($metrics[$metric], $offset);
        }

        $values['QID101_TEXT'] = $context['company'];
        $values['QID63_TEXT'] = $context['department'];
        $values['QID103_TEXT'] = $context['supervisor'];
        $values['QID62_TEXT'] = $context['email'];

        return $values;
    }

    protected function metricScores(array $answers): array
    {
        $scores = [];
        foreach ($this->metricKeys as $metric => $key) {
            $raw = $answers[$key] ?? 3;
            $scores[$metric] = $this->normalizeScore($raw);
        }

        return $scores;
    }

    protected function normalizeScore($value): float
    {
        if (!is_numeric($value)) {
            $value = 3;
        }

        $scaled = ((float) $value) * 2;
        return max(1, min(10, $scaled));
    }

    protected function scoreWithOffset(float $base, float $offset): float
    {
        return round(max(1, min(10, $base + $offset)), 2);
    }

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

        $responses = $this->latestResponsesWithAnswers($companyId);
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
        $temperature = $this->temperatureIndex($attributes);
        $teamCulture = $this->teamCultureAnalytics($answers);
        $impact = $this->impactAnalytics($answers);
        $gapChart = $this->gapChartDataset($attributes);
        $scatter = $this->teamScatterDataset($responses);
        $weightedIndicator = $this->weightedIndicatorScore($indicators);
        $teamCultureEval = $this->teamCultureEvaluation($teamCulture);

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
        $wave = $filters['wave'] ?? null;

        if (!$department && !$team && !$wave) {
            return $responses;
        }

        $workersByEmail = $this->companyWorkersByEmail($responses);

        return $responses->filter(function (SurveyResponse $response) use ($department, $team, $wave, $workersByEmail) {
            $user = $response->user;
            $email = $user?->email;
            $worker = $email && isset($workersByEmail[$email]) ? $workersByEmail[$email] : null;

            if ($department && (string) ($worker->department ?? '') !== (string) $department) {
                return false;
            }

            if ($team && (string) ($worker->supervisor ?? '') !== (string) $team) {
                return false;
            }

            if ($wave) {
                $versionId = (string) $response->survey_version_id;
                $assignmentWave = (string) ($response->assignment?->wave_label ?? $response->assignment?->survey_version_id);
                if ($versionId !== (string) $wave && $assignmentWave !== (string) $wave) {
                    return false;
                }
            }

            return true;
        });
    }

    protected function latestResponseIdsForCompany(int $companyId): Collection
    {
        return SurveyResponse::query()
            ->select(DB::raw('MAX(id) as id'))
            ->whereNotNull('submitted_at')
            ->whereHas('user', fn ($q) => $q->where('company_id', $companyId))
            ->groupBy('user_id')
            ->pluck('id');
    }

    protected function latestResponsesWithAnswers(int $companyId): Collection
    {
        $responseIds = $this->latestResponseIdsForCompany($companyId);
        if ($responseIds->isEmpty()) {
            return collect();
        }

        return SurveyResponse::with(['answers', 'user', 'assignment'])
            ->whereIn('id', $responseIds)
            ->get();
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
            $gap = ($current !== null && $ideal !== null) ? round($ideal - $current, 2) : null;

            $grouped[] = [
                'key' => $key,
                'label' => $config['label'] ?? $key,
                'current' => $current,
                'ideal' => $ideal,
                'gap' => $gap,
            ];
        }

        return collect($grouped)
            ->sortByDesc(fn ($row) => $row['gap'] ?? -INF)
            ->values()
            ->all();
    }

    protected function temperatureIndex(Collection $attributes): ?float
    {
        $currents = $attributes->pluck('current')->filter();
        if ($currents->isEmpty()) {
            return null;
        }

        return round($currents->avg(), 2);
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
            $value = $indicator['current'] ?? null;
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
        $responses = SurveyResponse::with('surveyWave')
            ->select('survey_version_id', 'wave_label', 'survey_wave_id')
            ->whereHas('user', fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(200)
            ->get();

        $waves = $responses->map(function ($response) {
            $wave = $response->surveyWave;
            $label = $wave->label ?? $response->wave_label ?? "Version {$response->survey_version_id}";
            $key = $wave?->id ?? $response->wave_label ?? (string) $response->survey_version_id;
            return [
                'key' => (string) $key,
                'label' => $label,
            ];
        })->unique('key');

        return $waves->pluck('label', 'key')->toArray();
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
        if (empty($config)) {
            return [];
        }

        $positiveKeys = $config['positive'] ?? [];
        $negativeKeys = $config['negative'] ?? [];
        if (empty($positiveKeys) && empty($negativeKeys)) {
            return [];
        }

        $positiveValues = collect();
        $negativeValues = collect();
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
            }
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

        return [
            'score' => $score,
            'positive' => $positiveAvg,
            'negative' => $negativeAvg,
            'items' => $items,
        ];
    }

    protected function teamCultureEvaluation(array $teamCulture): ?float
    {
        return $teamCulture['score'] ?? null;
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
            $currentValues = $attributes->pluck('current')->filter();
            if ($currentValues->isEmpty()) {
                continue;
            }

            $indicatorScore = $this->average($currentValues);
            $cultureScore = $this->teamCultureAnalytics($answers)['score'] ?? null;
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
}
