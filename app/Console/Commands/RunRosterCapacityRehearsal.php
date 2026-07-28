<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Companies;
use App\Models\RosterExternalIdentity;
use App\Models\RosterImport;
use App\Models\User;
use App\Services\RosterImportService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class RunRosterCapacityRehearsal extends Command
{
    protected $signature = 'readiness:roster-rehearsal
        {company_id : Empty isolated company that will receive the synthetic roster}
        {actor_id : Active manager for the selected isolated company}
        {--rows=500 : Synthetic employee rows to stage and commit}
        {--execute : Confirm mutation of the isolated company}
        {--output= : Optional repository-relative or absolute JSON evidence path}
        {--force : Replace an existing output file}';

    protected $description = 'Run a clean-source PostgreSQL governed-roster capacity and idempotency rehearsal.';

    public function handle(RosterImportService $imports): int
    {
        $tempRoot = null;

        try {
            if (! (bool) $this->option('execute')) {
                throw new RuntimeException(
                    'This command creates synthetic roster records. Re-run with --execute.'
                );
            }
            if (app()->environment('production')) {
                throw new RuntimeException('The roster rehearsal refuses to run in production.');
            }

            $driver = DB::connection()->getDriverName();
            if ($driver !== 'pgsql') {
                throw new RuntimeException('The roster rehearsal requires PostgreSQL.');
            }

            $source = $this->sourceTruth();
            if ($source['sha'] === 'unknown' || ! $source['clean']) {
                throw new RuntimeException(
                    'Run the roster rehearsal only from a clean, recognized Git commit.'
                );
            }

            $rowCount = (int) $this->option('rows');
            if ($rowCount < 100 || $rowCount > 1000) {
                throw new RuntimeException('--rows must be between 100 and 1000.');
            }

            $company = Companies::query()->findOrFail((int) $this->argument('company_id'));
            $actor = User::query()->findOrFail((int) $this->argument('actor_id'));
            if ((int) $actor->company_id !== (int) $company->id
                || (int) $actor->role !== Role::MANAGER->value
                || $actor->status !== 'active') {
                throw new RuntimeException(
                    'actor_id must be an active manager for the selected company.'
                );
            }

            $existingMembers = User::query()
                ->where('company_id', $company->id)
                ->whereKeyNot($actor->id)
                ->count();
            if ($existingMembers !== 0) {
                throw new RuntimeException(
                    'The isolated rehearsal company must contain only the selected manager.'
                );
            }

            $runKey = substr($source['sha'], 0, 12);
            $emailPrefix = "empulse-roster-{$runKey}-";
            if (User::query()->where('email', 'like', "{$emailPrefix}%")->exists()) {
                throw new RuntimeException(
                    'Synthetic rows for this source commit already exist. Use a fresh isolated company.'
                );
            }

            $csv = $this->syntheticCsv($runKey, $rowCount);
            $sourceHash = hash('sha256', $csv);
            $tempRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'empulse-roster-rehearsal-'.bin2hex(random_bytes(8));
            File::ensureDirectoryExists($tempRoot);
            $csvPath = $tempRoot.DIRECTORY_SEPARATOR.'roster.csv';
            File::put($csvPath, $csv);
            $upload = fn (): UploadedFile => new UploadedFile(
                $csvPath,
                'synthetic-roster.csv',
                'text/csv',
                null,
                true
            );

            $jobsBefore = DB::table('jobs')->count();
            $stageStarted = hrtime(true);
            $stage = $imports->stage($upload(), $company, $actor);
            $stageElapsedMs = round((hrtime(true) - $stageStarted) / 1_000_000, 2);
            $import = $stage['import']->fresh();
            $jobsAfterStage = DB::table('jobs')->count();
            $storedCiphertext = (string) DB::table('roster_imports')
                ->where('id', $import->id)
                ->value('source_csv');
            $ciphertextProtected = $storedCiphertext !== ''
                && $storedCiphertext !== $csv
                && ! str_contains($storedCiphertext, $emailPrefix);

            $parseStarted = hrtime(true);
            $imports->parse($import);
            $parseElapsedMs = round((hrtime(true) - $parseStarted) / 1_000_000, 2);
            $import->refresh();
            $preview = $imports->summary($import);
            $previewBytes = strlen(json_encode(
                $preview,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ));
            $sourceClearedAfterParse = DB::table('roster_imports')
                ->where('id', $import->id)
                ->value('source_csv') === null;

            $duplicateStarted = hrtime(true);
            $duplicate = $imports->stage($upload(), $company, $actor);
            $duplicateElapsedMs = round((hrtime(true) - $duplicateStarted) / 1_000_000, 2);
            $confirmationToken = $duplicate['confirmation_token'];
            if (! is_string($confirmationToken) || strlen($confirmationToken) !== 64) {
                throw new RuntimeException('The duplicate preview did not issue a confirmation token.');
            }

            $jobsBeforeCommit = DB::table('jobs')->count();
            $commitStarted = hrtime(true);
            $committed = $imports->commit($import->fresh(), $confirmationToken, $actor);
            $commitElapsedMs = round((hrtime(true) - $commitStarted) / 1_000_000, 2);
            $jobsAfterCommit = DB::table('jobs')->count();
            $invitationsAfterCommit = DB::table('account_invitations')
                ->join('users', 'users.id', '=', 'account_invitations.user_id')
                ->where('users.email', 'like', "{$emailPrefix}%")
                ->count();

            $replayStarted = hrtime(true);
            $imports->commit($committed->fresh(), $confirmationToken, $actor);
            $replayElapsedMs = round((hrtime(true) - $replayStarted) / 1_000_000, 2);
            $jobsAfterReplay = DB::table('jobs')->count();
            $invitationsAfterReplay = DB::table('account_invitations')
                ->join('users', 'users.id', '=', 'account_invitations.user_id')
                ->where('users.email', 'like', "{$emailPrefix}%")
                ->count();

            $restage = $imports->stage($upload(), $company, $actor);
            $createdUsers = User::query()
                ->where('company_id', $company->id)
                ->where('email', 'like', "{$emailPrefix}%")
                ->count();
            $workers = DB::table('company_worker')
                ->where('company_id', $company->id)
                ->where('email', 'like', "{$emailPrefix}%")
                ->count();
            $externalIdentities = RosterExternalIdentity::query()
                ->where('company_id', $company->id)
                ->where('external_id_normalized', 'like', "rr-{$runKey}-%")
                ->count();
            $importCount = RosterImport::query()
                ->where('company_id', $company->id)
                ->where('source_sha256', $sourceHash)
                ->count();
            $crossTenantUsers = User::query()
                ->where('email', 'like', "{$emailPrefix}%")
                ->where('company_id', '!=', $company->id)
                ->count();
            $commitAuditEvents = DB::table('audit_events')
                ->where('company_id', $company->id)
                ->where('action', 'roster.import_committed')
                ->where('subject_id', $import->id)
                ->count();

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
                'initial_stage_queued_once' => [
                    'expected' => ['queued' => true, 'duplicate' => false, 'job_delta' => 1],
                    'actual' => [
                        'queued' => $stage['queued'],
                        'duplicate' => $stage['duplicate'],
                        'job_delta' => $jobsAfterStage - $jobsBefore,
                    ],
                    'passed' => $stage['queued'] === true
                        && $stage['duplicate'] === false
                        && $jobsAfterStage - $jobsBefore === 1,
                ],
                'encrypted_staging_cleared_after_parse' => [
                    'expected' => true,
                    'actual' => $ciphertextProtected && $sourceClearedAfterParse,
                    'passed' => $ciphertextProtected && $sourceClearedAfterParse,
                ],
                'preview_row_contract' => [
                    'expected' => [
                        'status' => 'preview_ready',
                        'rows' => $rowCount,
                        'create' => $rowCount,
                        'errors' => 0,
                    ],
                    'actual' => [
                        'status' => $import->status,
                        'rows' => $import->total_rows,
                        'create' => $import->create_count,
                        'errors' => $import->error_count,
                    ],
                    'passed' => $import->status === 'preview_ready'
                        && $import->total_rows === $rowCount
                        && $import->create_count === $rowCount
                        && $import->error_count === 0,
                ],
                'same_file_preview_idempotency' => [
                    'expected' => ['duplicate' => true, 'same_import' => true, 'import_count' => 1],
                    'actual' => [
                        'duplicate' => $duplicate['duplicate'],
                        'same_import' => $duplicate['import']->is($import),
                        'import_count' => $importCount,
                    ],
                    'passed' => $duplicate['duplicate'] === true
                        && $duplicate['import']->is($import)
                        && $importCount === 1,
                ],
                'atomic_commit_counts' => [
                    'expected' => [
                        'users' => $rowCount,
                        'workers' => $rowCount,
                        'external_identities' => $rowCount,
                        'invitations' => $rowCount,
                        'invitation_job_delta' => $rowCount,
                    ],
                    'actual' => [
                        'users' => $createdUsers,
                        'workers' => $workers,
                        'external_identities' => $externalIdentities,
                        'invitations' => $invitationsAfterCommit,
                        'invitation_job_delta' => $jobsAfterCommit - $jobsBeforeCommit,
                    ],
                    'passed' => $createdUsers === $rowCount
                        && $workers === $rowCount
                        && $externalIdentities === $rowCount
                        && $invitationsAfterCommit === $rowCount
                        && $jobsAfterCommit - $jobsBeforeCommit === $rowCount,
                ],
                'commit_replay_idempotency' => [
                    'expected' => [
                        'jobs_unchanged' => true,
                        'invitations_unchanged' => true,
                        'commit_audit_events' => 1,
                    ],
                    'actual' => [
                        'jobs_unchanged' => $jobsAfterReplay === $jobsAfterCommit,
                        'invitations_unchanged' => $invitationsAfterReplay === $invitationsAfterCommit,
                        'commit_audit_events' => $commitAuditEvents,
                    ],
                    'passed' => $jobsAfterReplay === $jobsAfterCommit
                        && $invitationsAfterReplay === $invitationsAfterCommit
                        && $commitAuditEvents === 1,
                ],
                'same_file_after_commit_is_read_only' => [
                    'expected' => [
                        'duplicate' => true,
                        'status' => 'committed',
                        'confirmation_token' => null,
                    ],
                    'actual' => [
                        'duplicate' => $restage['duplicate'],
                        'status' => $restage['import']->status,
                        'confirmation_token' => $restage['confirmation_token'],
                    ],
                    'passed' => $restage['duplicate'] === true
                        && $restage['import']->status === 'committed'
                        && $restage['confirmation_token'] === null,
                ],
                'tenant_alignment' => [
                    'expected_cross_tenant_users' => 0,
                    'actual_cross_tenant_users' => $crossTenantUsers,
                    'passed' => $crossTenantUsers === 0,
                ],
            ];

            $report = [
                'schema_version' => 1,
                'scope' => 'repository_roster_capacity_rehearsal',
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
                    'queue_driver' => config('queue.default'),
                    'worker_running' => false,
                    'mail_provider_called' => false,
                ],
                'profile' => [
                    'company_id' => $company->id,
                    'actor_id' => $actor->id,
                    'rows' => $rowCount,
                    'run_key' => $runKey,
                ],
                'stage' => [
                    'elapsed_ms' => $stageElapsedMs,
                    'queued' => $stage['queued'],
                    'job_delta' => $jobsAfterStage - $jobsBefore,
                    'ciphertext_bytes' => strlen($storedCiphertext),
                    'plaintext_bytes' => strlen($csv),
                    'ciphertext_protected' => $ciphertextProtected,
                ],
                'parse_and_preview' => [
                    'elapsed_ms' => $parseElapsedMs,
                    'status' => $import->status,
                    'rows' => $import->total_rows,
                    'create' => $import->create_count,
                    'errors' => $import->error_count,
                    'preview_json_bytes' => $previewBytes,
                    'encrypted_source_cleared' => $sourceClearedAfterParse,
                ],
                'same_file_preview' => [
                    'elapsed_ms' => $duplicateElapsedMs,
                    'duplicate' => $duplicate['duplicate'],
                    'same_import' => $duplicate['import']->is($import),
                ],
                'commit' => [
                    'elapsed_ms' => $commitElapsedMs,
                    'users_created' => $createdUsers,
                    'workers_created' => $workers,
                    'external_identities_created' => $externalIdentities,
                    'account_invitations_created' => $invitationsAfterCommit,
                    'invitation_jobs_queued' => $jobsAfterCommit - $jobsBeforeCommit,
                ],
                'commit_replay' => [
                    'elapsed_ms' => $replayElapsedMs,
                    'jobs_unchanged' => $jobsAfterReplay === $jobsAfterCommit,
                    'invitations_unchanged' => $invitationsAfterReplay === $invitationsAfterCommit,
                ],
                'checks' => $checks,
                'passed' => collect($checks)->every(
                    fn (array $check): bool => $check['passed'] === true
                ),
                'limitations' => [
                    'This is an isolated local PostgreSQL/database-queue rehearsal, not provider staging or production evidence.',
                    'The parse job was executed directly after proving it was queued; no worker supervisor was running.',
                    'Account invitation jobs were queued but not processed, and no mail-provider request was made.',
                    'This does not prove queue-age SLOs, shared cache/queue behavior, worker crash recovery under the selected supervisor, provider delivery acceptance, alerting, deployment, or production sign-off.',
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
                $this->error('Roster capacity rehearsal failed.');

                return self::FAILURE;
            }

            $this->info('Roster capacity rehearsal passed.');
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

    protected function syntheticCsv(string $runKey, int $rows): string
    {
        $lines = ['external_id,name,email,role,status'];
        foreach (range(1, $rows) as $index) {
            $suffix = str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $lines[] = implode(',', [
                "RR-{$runKey}-{$suffix}",
                "Roster Rehearsal {$suffix}",
                "empulse-roster-{$runKey}-{$suffix}@example.invalid",
                'employee',
                'active',
            ]);
        }

        return implode("\n", $lines)."\n";
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

    protected function resolveOutputPath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
