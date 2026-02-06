<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ExplainAnalytics extends Command
{
    protected $signature = 'analytics:explain
        {company_id : Company id to profile}
        {--wave= : Optional wave selector (e.g. wave:8, version:2, label:Dec 2025 Pulse)}
        {--no-analyze : Planner only (skip runtime ANALYZE where supported)}';

    protected $description = 'Run EXPLAIN plans for core analytics queries.';

    public function handle(): int
    {
        $companyId = (int) $this->argument('company_id');
        if ($companyId <= 0) {
            $this->error('company_id must be a positive integer.');

            return self::FAILURE;
        }

        $driver = DB::connection()->getDriverName();
        $wave = (string) ($this->option('wave') ?? '');
        $withAnalyze = !((bool) $this->option('no-analyze'));

        $this->info(sprintf(
            'Running analytics EXPLAIN on %s (company_id=%d%s, analyze=%s)',
            $driver,
            $companyId,
            $wave !== '' ? ', wave=' . $wave : '',
            $withAnalyze ? 'yes' : 'no'
        ));

        $queries = [
            'latest_response_ids' => $this->latestResponseIdsQuery($companyId),
            'legacy_wave_labels' => $this->legacyWaveLabelsQuery($companyId),
            'legacy_version_ids' => $this->legacyVersionIdsQuery($companyId),
            'answers_for_latest_responses' => $this->answersForLatestResponsesQuery($companyId),
        ];

        if ($wave !== '') {
            $queries['latest_response_ids_with_wave'] = $this->latestResponseIdsQuery($companyId, $wave);
        }

        foreach ($queries as $name => $query) {
            $this->line('');
            $this->line("=== {$name} ===");
            $this->line('SQL: ' . $query->toSql());
            $this->line('Bindings: ' . json_encode($query->getBindings()));

            $planRows = $this->runExplain($query, DB::connection(), $withAnalyze);
            foreach ($planRows as $line) {
                $this->line($line);
            }
        }

        $this->line('');
        $this->info('EXPLAIN audit complete.');

        return self::SUCCESS;
    }

    protected function latestResponseIdsQuery(int $companyId, ?string $wave = null): Builder
    {
        $query = DB::table('survey_responses as sr')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sr.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->whereNotNull('sr.submitted_at')
            ->selectRaw('MAX(sr.id) as id')
            ->groupBy('sr.user_id');

        if ($wave !== null && trim($wave) !== '') {
            $query->leftJoin('survey_assignments as sa', 'sa.id', '=', 'sr.assignment_id');
            $this->applyWaveSelectorToLatestQuery($query, $this->parseWaveSelector($wave));
        }

        return $query;
    }

    protected function answersForLatestResponsesQuery(int $companyId): Builder
    {
        $latestResponsesSubquery = $this->latestResponseIdsQuery($companyId);
        $analyticsQuestionKeys = $this->analyticsQuestionKeys();

        $query = DB::table('survey_answers as a')
            ->whereIn('a.response_id', $latestResponsesSubquery)
            ->whereNotNull('a.value_numeric');

        if (!empty($analyticsQuestionKeys)) {
            $query->whereIn('a.question_key', $analyticsQuestionKeys);
        }

        return $query->select('a.id', 'a.response_id', 'a.question_key', 'a.value_numeric');
    }

    protected function legacyWaveLabelsQuery(int $companyId): Builder
    {
        return DB::table('survey_responses as sr')
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
            ->orderByDesc('sr.wave_label');
    }

    protected function legacyVersionIdsQuery(int $companyId): Builder
    {
        return DB::table('survey_responses as sr')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sr.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->whereNotNull('sr.submitted_at')
            ->whereNull('sr.survey_wave_id')
            ->whereNull('sr.wave_label')
            ->whereNotNull('sr.survey_version_id')
            ->select('sr.survey_version_id')
            ->distinct();
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

    protected function applyWaveSelectorToLatestQuery(Builder $query, array $selector): void
    {
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

    protected function analyticsQuestionKeys(): array
    {
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

        return array_values(array_unique(array_filter(
            array_merge($wcaKeys, $teamCultureKeys, $impactKeys),
            fn ($value) => is_string($value) && $value !== ''
        )));
    }

    /**
     * @return array<int, string>
     */
    protected function runExplain(Builder $query, ConnectionInterface $connection, bool $withAnalyze): array
    {
        $driver = $connection->getDriverName();
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        if ($driver === 'pgsql') {
            $prefix = $withAnalyze ? 'EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT) ' : 'EXPLAIN ';
            $rows = $connection->select($prefix . $sql, $bindings);

            return array_map(fn ($row) => (string) $row->{'QUERY PLAN'}, $rows);
        }

        if ($driver === 'mysql') {
            if ($withAnalyze) {
                $rows = $connection->select('EXPLAIN ANALYZE ' . $sql, $bindings);

                return array_map(function ($row) {
                    return implode(' | ', array_map('strval', (array) $row));
                }, $rows);
            }

            $rows = $connection->select('EXPLAIN FORMAT=JSON ' . $sql, $bindings);

            return array_map(function ($row) {
                return implode(' | ', array_map('strval', (array) $row));
            }, $rows);
        }

        if ($driver === 'sqlite') {
            $rows = $connection->select('EXPLAIN QUERY PLAN ' . $sql, $bindings);

            return array_map(function ($row) {
                return implode(' | ', array_map('strval', (array) $row));
            }, $rows);
        }

        $rows = $connection->select('EXPLAIN ' . $sql, $bindings);

        return array_map(function ($row) {
            return implode(' | ', array_map('strval', (array) $row));
        }, $rows);
    }
}
