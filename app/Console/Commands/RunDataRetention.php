<?php

namespace App\Console\Commands;

use App\Services\RetentionService;
use Illuminate\Console\Command;

class RunDataRetention extends Command
{
    protected $signature = 'privacy:retention
        {--execute : Create an executable plan instead of a dry run}
        {--confirm= : Execute only when this exact plan hash is supplied}';

    protected $description = 'Plan or execute policy-governed data retention (dry-run by default)';

    public function handle(RetentionService $retention): int
    {
        $plan = $retention->plan();
        $execute = (bool) $this->option('execute');
        $run = $retention->recordPlan($plan, ! $execute);

        $this->line(json_encode([
            'run_id' => $run->public_id,
            'dry_run' => $run->dry_run,
            'plan_hash' => $run->plan_hash,
            'counts' => collect($plan['targets'])->map->count()->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (! $execute) {
            $this->info('Dry run only. Re-run with --execute --confirm=<plan_hash> after review.');

            return self::SUCCESS;
        }

        $confirm = (string) $this->option('confirm');
        if ($confirm === '') {
            $this->error('Execution requires --confirm=<exact plan hash>. No data changed.');

            return self::FAILURE;
        }

        try {
            $result = $retention->execute($run, $confirm);
        } catch (\DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(json_encode($result, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
