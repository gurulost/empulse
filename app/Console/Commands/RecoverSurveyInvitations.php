<?php

namespace App\Console\Commands;

use App\Jobs\SendSurveyAssignmentInvitation;
use App\Models\SurveyAssignment;
use Illuminate\Console\Command;

class RecoverSurveyInvitations extends Command
{
    protected $signature = 'survey:invitations:recover
        {--execute : Queue eligible survey-invitation deliveries}
        {--limit=500 : Maximum assignments to inspect in one run}
        {--stale-minutes=15 : Minimum age of an interrupted delivery state}';

    protected $description = 'Recover interrupted survey-invitation deliveries (report-only unless --execute is supplied)';

    public function handle(): int
    {
        $limit = max(1, min(2000, (int) $this->option('limit')));
        $staleMinutes = max(15, min(1440, (int) $this->option('stale-minutes')));
        $assignments = SurveyAssignment::query()
            ->from('survey_assignments as assignment')
            ->join('survey_waves as wave', 'wave.id', '=', 'assignment.survey_wave_id')
            ->leftJoin('survey_responses as response', 'response.assignment_id', '=', 'assignment.id')
            ->where('assignment.status', 'pending')
            ->whereNull('assignment.token_revoked_at')
            ->whereNotNull('assignment.last_dispatched_at')
            ->whereIn('assignment.invite_status', ['queued', 'sending', 'failed'])
            ->where('assignment.updated_at', '<=', now()->subMinutes($staleMinutes))
            ->where(function ($query): void {
                $query->whereNull('assignment.due_at')->orWhere('assignment.due_at', '>', now());
            })
            ->whereNull('response.id')
            ->whereIn('wave.status', ['scheduled', 'processing', 'active'])
            ->where(function ($query): void {
                $query->whereNull('wave.due_at')->orWhere('wave.due_at', '>', now());
            })
            ->orderBy('assignment.id')
            ->limit($limit)
            ->get(['assignment.id', 'assignment.invite_status']);

        $this->line(json_encode([
            'eligible' => $assignments->count(),
            'execute' => (bool) $this->option('execute'),
            'limit' => $limit,
            'stale_minutes' => $staleMinutes,
            'states' => $assignments->countBy('invite_status')->sortKeys()->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (! $this->option('execute')) {
            $this->info('Report only. Re-run with --execute to queue these survey invitation deliveries.');

            return self::SUCCESS;
        }

        foreach ($assignments as $assignment) {
            SendSurveyAssignmentInvitation::dispatch($assignment->id);
        }

        $this->info("Queued {$assignments->count()} survey invitation recovery jobs.");

        return self::SUCCESS;
    }
}
