<?php

namespace App\Console\Commands;

use App\Jobs\ParseRosterImport;
use App\Models\RosterImport;
use Illuminate\Console\Command;

class RecoverRosterImports extends Command
{
    protected $signature = 'roster:imports:recover
        {--execute : Queue stale encrypted roster parsing work}
        {--stale-minutes=15 : Minimum parsing age before recovery}
        {--limit=100 : Maximum imports to inspect per run}';

    protected $description = 'Report or requeue stale encrypted roster imports without duplicating parse jobs.';

    public function handle(): int
    {
        $staleMinutes = max(15, (int) $this->option('stale-minutes'));
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $imports = RosterImport::query()
            ->where('status', 'parsing')
            ->whereNotNull('source_csv')
            ->where('updated_at', '<=', now()->subMinutes($staleMinutes))
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        $this->line(json_encode([
            'mode' => $this->option('execute') ? 'execute' : 'report',
            'eligible' => $imports->count(),
            'stale_minutes' => $staleMinutes,
            'limit' => $limit,
        ], JSON_THROW_ON_ERROR));

        if (! $this->option('execute')) {
            $this->warn('Report only. Re-run with --execute to queue eligible imports.');

            return self::SUCCESS;
        }

        foreach ($imports as $import) {
            ParseRosterImport::dispatch($import->id);
            $import->touch();
        }

        $this->info("Queued {$imports->count()} roster import parse job(s).");

        return self::SUCCESS;
    }
}
