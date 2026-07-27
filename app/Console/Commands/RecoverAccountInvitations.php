<?php

namespace App\Console\Commands;

use App\Jobs\SendAccountInvitation;
use App\Models\AccountInvitation;
use Illuminate\Console\Command;

class RecoverAccountInvitations extends Command
{
    protected $signature = 'account:invitations:recover
        {--execute : Queue eligible invitation deliveries}
        {--limit=500 : Maximum invitations to inspect in one run}';

    protected $description = 'Recover pending account-invitation deliveries (report-only unless --execute is supplied)';

    public function handle(): int
    {
        $limit = max(1, min(2000, (int) $this->option('limit')));
        $invitations = AccountInvitation::query()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->whereIn('delivery_status', ['pending', 'failed', 'sending'])
            ->where('delivery_attempts', '<', 5)
            ->where(function ($query): void {
                $query->whereNull('delivery_last_attempt_at')
                    ->orWhere('delivery_last_attempt_at', '<', now()->subMinutes(15));
            })
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'delivery_status', 'delivery_attempts']);

        $this->line(json_encode([
            'eligible' => $invitations->count(),
            'execute' => (bool) $this->option('execute'),
            'limit' => $limit,
        ], JSON_PRETTY_PRINT));

        if (! $this->option('execute')) {
            $this->info('Report only. Re-run with --execute to queue these invitation deliveries.');

            return self::SUCCESS;
        }

        foreach ($invitations as $invitation) {
            SendAccountInvitation::dispatch($invitation->id);
        }

        $this->info("Queued {$invitations->count()} account invitation deliveries.");

        return self::SUCCESS;
    }
}
