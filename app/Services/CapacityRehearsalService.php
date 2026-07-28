<?php

namespace App\Services;

use App\Models\SurveyWave;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

class CapacityRehearsalService
{
    public function __construct(protected SurveyAnalyticsService $analytics) {}

    /**
     * @return array<string, mixed>
     */
    public function run(
        int $companyId,
        int $waveId,
        int $iterations = 10,
        int $minimumInvited = 500,
        float $analyticsP95BudgetMs = 3000.0
    ): array {
        $iterations = max(1, min(50, $iterations));
        $minimumInvited = max(1, $minimumInvited);
        $analyticsP95BudgetMs = max(1.0, $analyticsP95BudgetMs);

        $wave = SurveyWave::query()
            ->whereKey($waveId)
            ->where('company_id', $companyId)
            ->first();

        if (! $wave) {
            throw new RuntimeException('The selected wave does not belong to the selected company.');
        }

        $counts = $this->cohortCounts($companyId, $waveId);
        $invariants = $this->integrityInvariants($companyId, $waveId);

        // Warm the application and database paths before recording samples.
        $this->analytics->companyDashboardAnalytics([
            'company_id' => $companyId,
            'wave' => "wave:{$waveId}",
        ]);

        $durations = [];
        $availability = [];
        $sample = null;

        for ($iteration = 0; $iteration < $iterations; $iteration++) {
            $startedAt = hrtime(true);
            $analytics = $this->analytics->companyDashboardAnalytics([
                'company_id' => $companyId,
                'wave' => "wave:{$waveId}",
            ]);
            $durations[] = round((hrtime(true) - $startedAt) / 1_000_000, 2);
            $availability[] = (string) ($analytics['availability'] ?? 'missing');
            $sample = $analytics['sample'] ?? null;
        }

        sort($durations, SORT_NUMERIC);
        $availability = array_values(array_unique($availability));
        $p95 = $this->percentile($durations, 0.95);
        $driver = DB::connection()->getDriverName();
        $source = $this->sourceTruth();

        $checks = [
            'source_checkout' => [
                'expected' => [
                    'recognized_commit' => true,
                    'clean' => true,
                ],
                'actual' => [
                    'recognized_commit' => $source['sha'] !== 'unknown',
                    'clean' => $source['clean'],
                ],
                'passed' => $source['sha'] !== 'unknown' && $source['clean'] === true,
            ],
            'database_engine' => [
                'expected' => 'pgsql',
                'actual' => $driver,
                'passed' => $driver === 'pgsql',
            ],
            'minimum_invited_cohort' => [
                'expected' => ">={$minimumInvited}",
                'actual' => $counts['invited_users'],
                'passed' => $counts['invited_users'] >= $minimumInvited,
            ],
            'eligible_privacy_result' => [
                'expected' => ['eligible'],
                'actual' => $availability,
                'passed' => $availability === ['eligible'],
            ],
            'analytics_p95_budget_ms' => [
                'expected' => "<={$analyticsP95BudgetMs}",
                'actual' => $p95,
                'passed' => $p95 <= $analyticsP95BudgetMs,
            ],
        ];

        foreach ($invariants as $name => $findingCount) {
            $checks[$name] = [
                'expected' => 0,
                'actual' => $findingCount,
                'passed' => $findingCount === 0,
            ];
        }

        return [
            'schema_version' => 1,
            'scope' => 'repository_capacity_rehearsal',
            'generated_at_utc' => now('UTC')->toIso8601String(),
            'release_sha' => $source['sha'],
            'source_clean' => $source['clean'],
            'production_signoff' => false,
            'environment' => [
                'app_environment' => app()->environment(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database_driver' => $driver,
                'database_version' => $this->databaseVersion($driver),
            ],
            'profile' => [
                'company_id' => $companyId,
                'wave_id' => $waveId,
                'wave_label' => $wave->label,
                'minimum_invited' => $minimumInvited,
                'iterations' => $iterations,
                'analytics_p95_budget_ms' => $analyticsP95BudgetMs,
            ],
            'counts' => $counts,
            'analytics' => [
                'durations_ms' => $durations,
                'minimum_ms' => $durations[0],
                'median_ms' => $this->percentile($durations, 0.50),
                'p95_ms' => $p95,
                'maximum_ms' => $durations[count($durations) - 1],
                'availability' => $availability,
                'sample' => $sample,
                'peak_process_memory_bytes' => memory_get_peak_usage(true),
            ],
            'integrity_findings' => $invariants,
            'checks' => $checks,
            'passed' => collect($checks)->every(
                fn (array $check): bool => $check['passed'] === true
            ),
            'limitations' => [
                'This rehearsal does not prove provider staging or production capacity.',
                'It does not exercise shared cache/session services, a mail provider sandbox, Stripe test mode, queue age, worker recovery, provider alerts, backup/PITR, or a deployed application.',
                'It does not replace independent accessibility, security, privacy, methodology, legal, or commercial approval.',
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function cohortCounts(int $companyId, int $waveId): array
    {
        $assignmentScope = DB::table('survey_assignments as sa')
            ->join('users as u', 'u.id', '=', 'sa.user_id')
            ->where('sa.survey_wave_id', $waveId)
            ->where('u.company_id', $companyId);

        $responseScope = DB::table('survey_responses as sr')
            ->join('users as u', 'u.id', '=', 'sr.user_id')
            ->where('sr.survey_wave_id', $waveId)
            ->where('u.company_id', $companyId);

        return [
            'invited_users' => (clone $assignmentScope)->distinct()->count('sa.user_id'),
            'assignments' => (clone $assignmentScope)->count('sa.id'),
            'submitted_responses' => (clone $responseScope)
                ->whereNotNull('sr.submitted_at')
                ->count('sr.id'),
            'answers' => DB::table('survey_answers as answer')
                ->join('survey_responses as sr', 'sr.id', '=', 'answer.response_id')
                ->join('users as u', 'u.id', '=', 'sr.user_id')
                ->where('sr.survey_wave_id', $waveId)
                ->where('u.company_id', $companyId)
                ->count('answer.id'),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function integrityInvariants(int $companyId, int $waveId): array
    {
        $duplicateAssignments = DB::table('survey_assignments')
            ->where('survey_wave_id', $waveId)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1');

        $duplicateResponses = DB::table('survey_responses')
            ->where('survey_wave_id', $waveId)
            ->select('assignment_id')
            ->groupBy('assignment_id')
            ->havingRaw('COUNT(*) > 1');

        $duplicateAnswers = DB::table('survey_answers as answer')
            ->join('survey_responses as sr', 'sr.id', '=', 'answer.response_id')
            ->where('sr.survey_wave_id', $waveId)
            ->select('answer.response_id', 'answer.question_key')
            ->groupBy('answer.response_id', 'answer.question_key')
            ->havingRaw('COUNT(*) > 1');

        return [
            'duplicate_assignment_groups' => DB::query()
                ->fromSub($duplicateAssignments, 'duplicate_assignments')
                ->count(),
            'duplicate_response_groups' => DB::query()
                ->fromSub($duplicateResponses, 'duplicate_responses')
                ->count(),
            'duplicate_answer_groups' => DB::query()
                ->fromSub($duplicateAnswers, 'duplicate_answers')
                ->count(),
            'cross_tenant_assignments' => DB::table('survey_assignments as sa')
                ->join('users as u', 'u.id', '=', 'sa.user_id')
                ->where('sa.survey_wave_id', $waveId)
                ->where('u.company_id', '!=', $companyId)
                ->count(),
            'cross_tenant_responses' => DB::table('survey_responses as sr')
                ->join('users as u', 'u.id', '=', 'sr.user_id')
                ->where('sr.survey_wave_id', $waveId)
                ->where('u.company_id', '!=', $companyId)
                ->count(),
            'response_assignment_mismatches' => DB::table('survey_responses as sr')
                ->join('survey_assignments as sa', 'sa.id', '=', 'sr.assignment_id')
                ->where('sr.survey_wave_id', $waveId)
                ->where(function ($query) {
                    $query->whereColumn('sr.user_id', '!=', 'sa.user_id')
                        ->orWhereColumn('sr.survey_wave_id', '!=', 'sa.survey_wave_id');
                })
                ->count(),
        ];
    }

    /**
     * @param  list<float>  $sortedValues
     */
    protected function percentile(array $sortedValues, float $percentile): float
    {
        $rank = max(0, (int) ceil(count($sortedValues) * $percentile) - 1);

        return $sortedValues[$rank];
    }

    /**
     * @return array{sha: string, clean: bool}
     */
    protected function sourceTruth(): array
    {
        $environmentSha = trim((string) (getenv('GITHUB_SHA') ?: getenv('RELEASE_SHA') ?: ''));
        if (preg_match('/^[a-f0-9]{40}$/i', $environmentSha)) {
            return [
                'sha' => strtolower($environmentSha),
                'clean' => true,
            ];
        }

        $shaProcess = new Process(['git', 'rev-parse', 'HEAD'], base_path());
        $shaProcess->run();
        $sha = trim($shaProcess->getOutput());

        $statusProcess = new Process(['git', 'status', '--porcelain'], base_path());
        $statusProcess->run();

        return [
            'sha' => $shaProcess->isSuccessful() && preg_match('/^[a-f0-9]{40}$/i', $sha)
                ? strtolower($sha)
                : 'unknown',
            'clean' => $statusProcess->isSuccessful() && trim($statusProcess->getOutput()) === '',
        ];
    }

    protected function databaseVersion(string $driver): string
    {
        if ($driver === 'pgsql') {
            return (string) (DB::selectOne('SELECT version() AS version')->version ?? 'unknown');
        }

        if ($driver === 'sqlite') {
            return (string) (DB::selectOne('SELECT sqlite_version() AS version')->version ?? 'unknown');
        }

        if ($driver === 'mysql') {
            return (string) (DB::selectOne('SELECT VERSION() AS version')->version ?? 'unknown');
        }

        return 'unknown';
    }
}
