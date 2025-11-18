<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSurveyWave;
use App\Models\SurveyWave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduleSurveyWaves extends Command
{
    protected $signature = 'survey:waves:schedule';
    protected $description = 'Create survey assignments for active waves if due.';

    public function handle(): int
    {
        $waves = SurveyWave::with('survey')
            ->whereNotIn('status', ['completed'])
            ->where(function ($query) {
                $query->whereNull('opens_at')->orWhere('opens_at', '<=', now());
            })
            ->get();

        foreach ($waves as $wave) {
            if (!$wave->survey || !$wave->company_id) {
                continue;
            }

            if ($wave->status === 'paused') {
                continue;
            }

            if ($wave->due_at && $wave->due_at->isPast()) {
                $wave->update(['status' => 'completed']);
                continue;
            }

            if ($wave->kind === 'drip' && !$this->shouldDispatchDrip($wave)) {
                continue;
            }

            if (!$this->companyHasActiveSubscription($wave->company_id)) {
                Log::info('Skipping wave scheduling due to inactive subscription.', ['wave' => $wave->id]);
                continue;
            }

            $wave->update(['status' => 'processing']);
            ProcessSurveyWave::dispatch($wave->id);
        }

        $this->info('Wave scheduling pass completed.');
        return Command::SUCCESS;
    }

    protected function companyHasActiveSubscription(int $companyId): bool
    {
        $company = \App\Models\Companies::find($companyId);
        if (!$company || !$company->stripe_status) {
            return false;
        }

        return $company->stripe_status === 'active';
    }

    protected function shouldDispatchDrip(SurveyWave $wave): bool
    {
        if ($wave->kind !== 'drip') {
            return true;
        }

        $interval = match ($wave->cadence) {
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            'quarterly' => now()->subMonths(3),
            default => null,
        };

        if ($wave->cadence === 'manual') {
            return $wave->last_dispatched_at === null;
        }

        if (!$interval) {
            return true;
        }

        if ($wave->last_dispatched_at === null) {
            return true;
        }

        return $wave->last_dispatched_at->lessThanOrEqualTo($interval);
    }
}
