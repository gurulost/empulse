<?php

namespace App\Console\Commands;

use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyResponse;
use App\Services\SurveyAssignmentAccessService;
use App\Services\SurveyResponseValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class RunSubmissionConcurrencyRehearsal extends Command
{
    protected $signature = 'readiness:submission-concurrency
        {autosave_assignment_id : Pending assignment used for the same-revision autosave race}
        {submit_assignment_id : Different pending assignment used for the final-submission race}
        {source_response_id : Completed same-version synthetic response used as the answer payload}
        {--execute : Confirm mutation of the two isolated rehearsal assignments}
        {--output= : Optional repository-relative or absolute JSON evidence path}
        {--force : Replace an existing output file}';

    protected $description = 'Race two processes against autosave and final submission on isolated PostgreSQL.';

    public function handle(
        SurveyAssignmentAccessService $access,
        SurveyResponseValidationService $validation
    ): int {
        $tempRoot = null;

        try {
            if (! (bool) $this->option('execute')) {
                throw new RuntimeException(
                    'This command mutates two isolated rehearsal assignments. Re-run with --execute.'
                );
            }

            if (app()->environment('production')) {
                throw new RuntimeException('The concurrency rehearsal refuses to run in production.');
            }

            $driver = DB::connection()->getDriverName();
            if ($driver !== 'pgsql') {
                throw new RuntimeException('The concurrency rehearsal requires PostgreSQL.');
            }

            $source = $this->sourceTruth();
            if ($source['sha'] === 'unknown' || ! $source['clean']) {
                throw new RuntimeException(
                    'Run the concurrency rehearsal only from a clean, recognized Git commit.'
                );
            }

            $autosaveAssignment = SurveyAssignment::query()
                ->findOrFail((int) $this->argument('autosave_assignment_id'));
            $submitAssignment = SurveyAssignment::query()
                ->findOrFail((int) $this->argument('submit_assignment_id'));

            if ($autosaveAssignment->is($submitAssignment)) {
                throw new RuntimeException('Autosave and submission assignments must be different.');
            }

            $access->assertEligible($autosaveAssignment);
            $access->assertEligible($submitAssignment);
            $this->assertRehearsalAssignment($autosaveAssignment, true);
            $this->assertRehearsalAssignment($submitAssignment, false);

            $sourceResponse = SurveyResponse::query()
                ->whereNotNull('submitted_at')
                ->findOrFail((int) $this->argument('source_response_id'));

            if ((int) $sourceResponse->survey_version_id !== (int) $autosaveAssignment->survey_version_id
                || (int) $sourceResponse->survey_version_id !== (int) $submitAssignment->survey_version_id) {
                throw new RuntimeException(
                    'The source response and both target assignments must use the same survey version.'
                );
            }

            $payload = $this->responsePayload($sourceResponse);
            $autosavePayload = $validation->validateAndSanitize(
                $autosaveAssignment,
                $payload,
                false
            );
            $submissionPayload = $validation->validateAndSanitize(
                $submitAssignment,
                $payload,
                true
            );

            if ($autosavePayload === [] || $submissionPayload === []) {
                throw new RuntimeException('The source response did not yield a usable answer payload.');
            }

            ksort($autosavePayload);
            ksort($submissionPayload);
            $tempRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'empulse-submission-race-'.bin2hex(random_bytes(8));
            File::ensureDirectoryExists($tempRoot);

            $autosaveStarted = hrtime(true);
            $autosaveWorkers = $this->runRace(
                'autosave',
                $autosaveAssignment->id,
                [
                    'responses' => $autosavePayload,
                    'revision' => 0,
                ],
                $tempRoot.DIRECTORY_SEPARATOR.'autosave'
            );
            $autosaveElapsedMs = round((hrtime(true) - $autosaveStarted) / 1_000_000, 2);

            $submissionStarted = hrtime(true);
            $submissionWorkers = $this->runRace(
                'submit',
                $submitAssignment->id,
                [
                    'responses' => $submissionPayload,
                    'duration_ms' => 1000,
                ],
                $tempRoot.DIRECTORY_SEPARATOR.'submit'
            );
            $submissionElapsedMs = round((hrtime(true) - $submissionStarted) / 1_000_000, 2);

            $autosaveAssignment->refresh();
            $submitAssignment->refresh();
            $responses = SurveyResponse::query()
                ->where('assignment_id', $submitAssignment->id)
                ->get();
            $response = $responses->first();

            $expectedDraftHash = $this->payloadHash($autosavePayload);
            $actualDraftHash = $this->payloadHash($autosaveAssignment->draft_answers ?? []);
            $expectedAnswerKeys = array_keys($submissionPayload);
            sort($expectedAnswerKeys);
            $actualAnswerKeys = $response
                ? SurveyAnswer::query()
                    ->where('response_id', $response->id)
                    ->pluck('question_key')
                    ->all()
                : [];
            sort($actualAnswerKeys);
            $answerCount = $response
                ? SurveyAnswer::query()->where('response_id', $response->id)->count()
                : 0;

            $autosaveStatuses = collect($autosaveWorkers)->countBy('status');
            $submissionStatuses = collect($submissionWorkers)->countBy('status');
            $usageEvents = $response
                ? DB::table('organization_usage_events')
                    ->where('idempotency_key', "survey_response:{$response->id}")
                    ->count()
                : 0;

            $checks = [
                'source_checkout' => [
                    'expected' => ['recognized_commit' => true, 'clean' => true],
                    'actual' => [
                        'recognized_commit' => $source['sha'] !== 'unknown',
                        'clean' => $source['clean'],
                    ],
                    'passed' => true,
                ],
                'database_engine' => [
                    'expected' => 'pgsql',
                    'actual' => $driver,
                    'passed' => true,
                ],
                'autosave_one_winner_one_conflict' => [
                    'expected' => ['saved' => 1, 'conflict' => 1, 'error' => 0],
                    'actual' => [
                        'saved' => (int) ($autosaveStatuses['saved'] ?? 0),
                        'conflict' => (int) ($autosaveStatuses['conflict'] ?? 0),
                        'error' => (int) ($autosaveStatuses['error'] ?? 0),
                    ],
                    'passed' => ($autosaveStatuses['saved'] ?? 0) === 1
                        && ($autosaveStatuses['conflict'] ?? 0) === 1
                        && ($autosaveStatuses['error'] ?? 0) === 0,
                ],
                'autosave_revision_advanced_once' => [
                    'expected' => 1,
                    'actual' => (int) $autosaveAssignment->draft_revision,
                    'passed' => (int) $autosaveAssignment->draft_revision === 1,
                ],
                'autosave_payload_preserved' => [
                    'expected_sha256' => $expectedDraftHash,
                    'actual_sha256' => $actualDraftHash,
                    'passed' => hash_equals($expectedDraftHash, $actualDraftHash),
                ],
                'submission_one_winner_one_conflict' => [
                    'expected' => ['submitted' => 1, 'conflict' => 1, 'error' => 0],
                    'actual' => [
                        'submitted' => (int) ($submissionStatuses['submitted'] ?? 0),
                        'conflict' => (int) ($submissionStatuses['conflict'] ?? 0),
                        'error' => (int) ($submissionStatuses['error'] ?? 0),
                    ],
                    'passed' => ($submissionStatuses['submitted'] ?? 0) === 1
                        && ($submissionStatuses['conflict'] ?? 0) === 1
                        && ($submissionStatuses['error'] ?? 0) === 0,
                ],
                'single_response' => [
                    'expected' => 1,
                    'actual' => $responses->count(),
                    'passed' => $responses->count() === 1,
                ],
                'single_complete_answer_set' => [
                    'expected_count' => count($expectedAnswerKeys),
                    'actual_count' => count($actualAnswerKeys),
                    'keys_match' => $expectedAnswerKeys === $actualAnswerKeys,
                    'passed' => $expectedAnswerKeys === $actualAnswerKeys,
                ],
                'single_completed_response_usage_event' => [
                    'expected' => 1,
                    'actual' => $usageEvents,
                    'passed' => $usageEvents === 1,
                ],
                'assignment_completed_and_token_revoked' => [
                    'expected' => true,
                    'actual' => $submitAssignment->status === 'completed'
                        && $submitAssignment->completed_at !== null
                        && $submitAssignment->token_revoked_at !== null,
                    'passed' => $submitAssignment->status === 'completed'
                        && $submitAssignment->completed_at !== null
                        && $submitAssignment->token_revoked_at !== null,
                ],
            ];

            $report = [
                'schema_version' => 1,
                'scope' => 'repository_submission_concurrency_rehearsal',
                'generated_at_utc' => now('UTC')->toIso8601String(),
                'release_sha' => $source['sha'],
                'source_clean' => $source['clean'],
                'production_signoff' => false,
                'environment' => [
                    'app_environment' => app()->environment(),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'database_driver' => $driver,
                    'database_version' => (string) (
                        DB::selectOne('SELECT version() AS version')->version ?? 'unknown'
                    ),
                    'processes_per_race' => 2,
                ],
                'profile' => [
                    'autosave_assignment_id' => $autosaveAssignment->id,
                    'submit_assignment_id' => $submitAssignment->id,
                    'source_response_id' => $sourceResponse->id,
                    'survey_version_id' => $sourceResponse->survey_version_id,
                    'autosave_answer_count' => count($autosavePayload),
                    'submission_answer_count' => count($submissionPayload),
                ],
                'autosave_race' => [
                    'elapsed_ms' => $autosaveElapsedMs,
                    'worker_results' => $autosaveWorkers,
                    'final_revision' => (int) $autosaveAssignment->draft_revision,
                    'final_answer_count' => count($autosaveAssignment->draft_answers ?? []),
                    'final_payload_sha256' => $actualDraftHash,
                ],
                'submission_race' => [
                    'elapsed_ms' => $submissionElapsedMs,
                    'worker_results' => $submissionWorkers,
                    'response_count' => $responses->count(),
                    'answer_count' => $answerCount,
                    'usage_event_count' => $usageEvents,
                    'assignment_status' => $submitAssignment->status,
                    'token_revoked' => $submitAssignment->token_revoked_at !== null,
                ],
                'checks' => $checks,
                'passed' => collect($checks)->every(
                    fn (array $check): bool => $check['passed'] === true
                ),
                'limitations' => [
                    'This is an isolated local PostgreSQL process race, not provider staging or production evidence.',
                    'It does not exercise a deployed load balancer, shared session/cache service, mail provider, Stripe, queue-age SLO, worker supervisor, or alert routing.',
                    'It mutates only the two explicitly selected pending assignments and must never be run in production.',
                ],
            ];

            $json = json_encode(
                $report,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ).PHP_EOL;
            $output = trim((string) $this->option('output'));
            if ($output !== '') {
                $path = $this->resolveOutputPath($output);
                if (File::exists($path) && ! (bool) $this->option('force')) {
                    throw new RuntimeException(
                        "Evidence file already exists: {$path}. Use --force to replace it."
                    );
                }

                File::ensureDirectoryExists(dirname($path));
                File::put($path, $json);
                $this->line("Evidence: {$path}");
            } else {
                $this->line($json);
            }

            if (! $report['passed']) {
                $this->error('Submission concurrency rehearsal failed.');

                return self::FAILURE;
            }

            $this->info('Submission concurrency rehearsal passed.');
            $this->warn('This is not provider staging, deployment, or production sign-off.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($tempRoot !== null && File::isDirectory($tempRoot)) {
                File::deleteDirectory($tempRoot);
            }
        }
    }

    protected function assertRehearsalAssignment(
        SurveyAssignment $assignment,
        bool $requireEmptyDraft
    ): void {
        if ($assignment->status !== 'pending' || $assignment->response()->exists()) {
            throw new RuntimeException('Both rehearsal assignments must be pending without responses.');
        }

        if ($requireEmptyDraft
            && ((int) $assignment->draft_revision !== 0 || ($assignment->draft_answers ?? []) !== [])) {
            throw new RuntimeException(
                'The autosave rehearsal assignment must begin at revision zero with no draft.'
            );
        }

        if (! $assignment->privacy_acknowledged_at
            || $assignment->privacy_policy_version !== config('privacy.policy.version')) {
            throw new RuntimeException(
                'Both rehearsal assignments must acknowledge the current privacy policy.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function responsePayload(SurveyResponse $response): array
    {
        $payload = [];

        $answers = SurveyAnswer::query()
            ->where('response_id', $response->id)
            ->get();

        foreach ($answers as $answer) {
            $value = $answer->value;
            if (is_string($value)
                && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }

            $payload[$answer->question_key] = $value;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    protected function runRace(
        string $operation,
        int $assignmentId,
        array $payload,
        string $barrierDirectory
    ): array {
        File::ensureDirectoryExists($barrierDirectory);
        $payloadPath = $barrierDirectory.DIRECTORY_SEPARATOR.'payload.json';
        File::put(
            $payloadPath,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        $processes = [];
        foreach (['one', 'two'] as $workerId) {
            $process = new Process([
                PHP_BINARY,
                base_path('artisan'),
                'readiness:submission-concurrency-worker',
                $operation,
                (string) $assignmentId,
                $payloadPath,
                $workerId,
                $barrierDirectory,
                '--no-interaction',
            ], base_path());
            $process->setTimeout(30);
            $process->start();
            $processes[$workerId] = $process;
        }

        $readyDeadline = microtime(true) + 15;
        do {
            $allReady = collect(array_keys($processes))->every(
                fn (string $workerId): bool => File::exists(
                    $barrierDirectory.DIRECTORY_SEPARATOR."{$workerId}.ready"
                )
            );

            if ($allReady) {
                break;
            }

            foreach ($processes as $workerId => $process) {
                if (! $process->isRunning() && ! File::exists(
                    $barrierDirectory.DIRECTORY_SEPARATOR."{$workerId}.ready"
                )) {
                    throw new RuntimeException(
                        "Concurrency worker {$workerId} stopped before reaching the barrier: "
                        .trim($process->getErrorOutput() ?: $process->getOutput())
                    );
                }
            }

            usleep(10_000);
        } while (microtime(true) < $readyDeadline);

        if (! $allReady) {
            throw new RuntimeException('Concurrency workers did not reach the barrier in time.');
        }

        File::put($barrierDirectory.DIRECTORY_SEPARATOR.'release', now()->toIso8601String());

        $results = [];
        foreach ($processes as $workerId => $process) {
            $process->wait();
            $resultPath = $barrierDirectory.DIRECTORY_SEPARATOR."{$workerId}.result.json";
            if (! File::exists($resultPath)) {
                throw new RuntimeException(
                    "Concurrency worker {$workerId} produced no result: "
                    .trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            $results[] = json_decode(
                File::get($resultPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return $results;
    }

    /**
     * @return array{sha: string, clean: bool}
     */
    protected function sourceTruth(): array
    {
        $shaProcess = new Process(['git', 'rev-parse', 'HEAD'], base_path());
        $shaProcess->run();
        $sha = strtolower(trim($shaProcess->getOutput()));
        $hasGitSha = $shaProcess->isSuccessful()
            && preg_match('/^[a-f0-9]{40}$/', $sha) === 1;

        $statusProcess = new Process(['git', 'status', '--porcelain'], base_path());
        $statusProcess->run();

        return [
            'sha' => $hasGitSha ? $sha : 'unknown',
            'clean' => $hasGitSha
                && $statusProcess->isSuccessful()
                && trim($statusProcess->getOutput()) === '',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function payloadHash(array $payload): string
    {
        ksort($payload);

        return hash(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }

    protected function resolveOutputPath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
