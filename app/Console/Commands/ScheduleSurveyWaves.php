<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSurveyWave;
use App\Models\SurveyWave;
use App\Models\SurveyWaveLog;
use App\Support\CompanyBilling;
use App\Support\SurveyWaveAutomation;
use Illuminate\Console\Command;

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
            try {
                if (!$wave->survey || !$wave->company_id) {
                    continue;
                }

                $manager = CompanyBilling::manager($wave->company_id);
                $billingStatus = CompanyBilling::status($manager);

                if ($wave->status === 'paused') {
                    $this->logWaveEvent($wave, 'skipped', 'Paused');
                    continue;
                }

                if ($wave->status === 'processing') {
                    $this->logWaveEvent($wave, 'skipped', 'Already processing.');
                    continue;
                }

                if ($wave->due_at && $wave->due_at->isPast()) {
                    $wave->update(['status' => 'completed']);
                    $this->logWaveEvent($wave, 'completed', 'Wave past due date.');
                    continue;
                }

                if (!CompanyBilling::allowsScheduling($manager)) {
                    $wave->update(['status' => 'paused']);
                    $this->logWaveEvent(
                        $wave,
                        'paused',
                        'Billing inactive: ' . SurveyWaveAutomation::billingStatusLabel($billingStatus)
                    );
                    continue;
                }

                if ($wave->kind === 'drip' && !SurveyWaveAutomation::dripEnabledForTariff($manager?->tariff)) {
                    $wave->update(['status' => 'paused']);
                    $this->logWaveEvent($wave, 'paused', 'Current plan does not allow drip cadences.');
                    continue;
                }

                if ($wave->kind === 'drip' && !$this->waveHasDispatchableAssignments($wave)) {
                    $this->logWaveEvent($wave, 'skipped', 'All assignments are still inside cadence window.');
                    continue;
                }

                $wave->update(['status' => 'processing']);
                ProcessSurveyWave::dispatch($wave->id);
                $this->logWaveEvent($wave, 'processing', 'Wave dispatched to queue.');
            } catch (\Throwable $e) {
                \Log::error("Failed to schedule wave {$wave->id}: " . $e->getMessage());
                $this->logWaveEvent($wave, 'error', 'Scheduler error: ' . $e->getMessage());
            }
        }

        $this->info('Wave scheduling pass completed.');
        return Command::SUCCESS;
    }

    protected function waveHasDispatchableAssignments(SurveyWave $wave): bool
    {
        if ($wave->kind !== 'drip') {
            return true;
        }

        $assignmentsQuery = $wave->assignments();

        if (!$assignmentsQuery->exists()) {
            return true;
        }

        if ($wave->cadence === 'manual') {
            return $assignmentsQuery->whereNull('last_dispatched_at')->exists();
        }

        $threshold = SurveyWaveAutomation::cadenceThreshold($wave->cadence);
        if (!$threshold) {
            return true;
        }

        return $assignmentsQuery
            ->where(function ($query) use ($threshold) {
                $query->whereNull('last_dispatched_at')
                    ->orWhere('last_dispatched_at', '<=', $threshold);
            })
            ->exists();
    }

    protected function logWaveEvent(SurveyWave $wave, string $status, string $message): void
    {
        SurveyWaveLog::create([
            'survey_wave_id' => $wave->id,
            'status' => $status,
            'message' => $message,
        ]);
    }
}
