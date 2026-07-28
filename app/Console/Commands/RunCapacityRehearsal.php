<?php

namespace App\Console\Commands;

use App\Services\CapacityRehearsalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class RunCapacityRehearsal extends Command
{
    protected $signature = 'readiness:capacity-rehearsal
        {company_id : Company id containing the frozen test cohort}
        {--wave= : Required immutable wave selector in wave:<id> form}
        {--iterations=10 : Recorded analytics runs after one warm-up}
        {--minimum-invited=500 : Minimum distinct assigned users}
        {--analytics-p95-ms=3000 : Maximum allowed analytics p95 in milliseconds}
        {--output= : Optional repository-relative or absolute JSON evidence path}
        {--force : Replace an existing output file}';

    protected $description = 'Run the bounded PostgreSQL 500-respondent analytics and integrity rehearsal.';

    public function handle(CapacityRehearsalService $rehearsal): int
    {
        $companyId = (int) $this->argument('company_id');
        $waveSelector = trim((string) $this->option('wave'));
        $iterations = (int) $this->option('iterations');
        $minimumInvited = (int) $this->option('minimum-invited');
        $p95BudgetMs = (float) $this->option('analytics-p95-ms');

        if ($companyId <= 0) {
            $this->error('company_id must be a positive integer.');

            return self::FAILURE;
        }

        if (! preg_match('/^wave:([1-9][0-9]*)$/', $waveSelector, $matches)) {
            $this->error('--wave must be an explicit immutable selector in wave:<id> form.');

            return self::FAILURE;
        }

        if ($iterations < 1 || $iterations > 50) {
            $this->error('--iterations must be between 1 and 50.');

            return self::FAILURE;
        }

        if ($minimumInvited < 1 || $p95BudgetMs <= 0) {
            $this->error('Capacity thresholds must be positive.');

            return self::FAILURE;
        }

        try {
            $report = $rehearsal->run(
                $companyId,
                (int) $matches[1],
                $iterations,
                $minimumInvited,
                $p95BudgetMs
            );

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

            $this->line(sprintf(
                'Cohort: %d invited, %d submitted, %d answers.',
                $report['counts']['invited_users'],
                $report['counts']['submitted_responses'],
                $report['counts']['answers']
            ));
            $this->line(sprintf(
                'Analytics p95: %.2f ms (budget %.2f ms).',
                $report['analytics']['p95_ms'],
                $report['profile']['analytics_p95_budget_ms']
            ));

            if ($report['passed'] !== true) {
                $this->error('Capacity rehearsal failed one or more bounded checks.');
                foreach ($report['checks'] as $name => $check) {
                    if ($check['passed'] !== true) {
                        $this->line(sprintf(
                            ' - %s expected %s; got %s.',
                            $name,
                            json_encode($check['expected']),
                            json_encode($check['actual'])
                        ));
                    }
                }

                return self::FAILURE;
            }

            $this->info('Capacity rehearsal passed its bounded PostgreSQL checks.');
            $this->warn('This is not provider staging, deployment, or production sign-off.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function resolveOutputPath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
