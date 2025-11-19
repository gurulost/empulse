<?php

namespace App\Jobs;

use App\Models\SurveyAssignment;
use App\Models\SurveyWave;
use App\Models\SurveyWaveLog;
use App\Models\User;
use App\Services\SurveyService;
use App\Support\SurveyWaveAutomation;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSurveyWave implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(protected int $waveId)
    {
    }

    public function handle(SurveyService $surveyService): void
    {
        $wave = SurveyWave::with('survey')->find($this->waveId);
        if (!$wave || !$wave->survey || !$wave->company_id) {
            return;
        }

        $companyUsers = User::where('company_id', $wave->company_id)->get();
        $stats = [
            'dispatched' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($companyUsers as $user) {
            try {
                $assignment = $surveyService->getOrCreateAssignment($user);
                if (!$assignment) {
                    $stats['skipped']++;
                    $this->logEvent($wave, $user, 'skipped', 'No assignment available.');
                    continue;
                }

                if ($message = $this->shouldSkipAssignment($wave, $assignment)) {
                    $stats['skipped']++;
                    $this->logEvent($wave, $user, 'skipped', $message);
                    continue;
                }

                $assignment->update([
                    'survey_wave_id' => $wave->id,
                    'wave_label' => $wave->label,
                    'last_dispatched_at' => now(),
                    'dispatch_count' => ($assignment->dispatch_count ?? 0) + 1,
                ]);

                $stats['dispatched']++;
                $this->logEvent($wave, $user, 'dispatched');
            } catch (\Throwable $e) {
                Log::error('Wave scheduling failed', [
                    'wave' => $wave->id,
                    'user' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $stats['failed']++;
                $this->logEvent($wave, $user, 'failed', $e->getMessage());
            }
        }

        $this->finalizeWave($wave, $stats);
    }

    protected function shouldSkipAssignment(SurveyWave $wave, SurveyAssignment $assignment): ?string
    {
        if ($assignment->status === 'completed') {
            return 'Assignment already completed.';
        }

        if ($wave->kind !== 'drip') {
            return null;
        }

        if ($wave->cadence === 'manual' && SurveyWaveAutomation::manualIsOneShot()) {
            if ($assignment->last_dispatched_at) {
                return 'Manual cadence already delivered for this user.';
            }

            return null;
        }

        $threshold = SurveyWaveAutomation::cadenceThreshold($wave->cadence);
        if (!$threshold) {
            return null;
        }

        if ($assignment->last_dispatched_at && $assignment->last_dispatched_at->greaterThan($threshold)) {
            return 'Cadence window not elapsed.';
        }

        return null;
    }

    protected function finalizeWave(SurveyWave $wave, array $stats): void
    {
        $nextStatus = $this->determineNextStatus($wave);

        $update = ['status' => $nextStatus];
        if ($stats['dispatched'] > 0) {
            $update['last_dispatched_at'] = now();
        }

        $wave->update($update);

        $this->logEvent(
            $wave,
            null,
            $nextStatus,
            sprintf(
                'Dispatch summary — sent: %d, skipped: %d, failed: %d.',
                $stats['dispatched'],
                $stats['skipped'],
                $stats['failed']
            )
        );
    }

    protected function determineNextStatus(SurveyWave $wave): string
    {
        if ($wave->kind === 'full') {
            return 'completed';
        }

        if ($wave->due_at && $wave->due_at->isPast()) {
            return 'completed';
        }

        if ($wave->cadence === 'manual') {
            $assignmentQuery = $wave->assignments();
            if (!$assignmentQuery->exists()) {
                return 'scheduled';
            }

            if (!$assignmentQuery->whereNull('last_dispatched_at')->exists()) {
                return 'completed';
            }

            return 'scheduled';
        }

        return 'scheduled';
    }

    protected function logEvent(SurveyWave $wave, ?User $user, string $status, ?string $message = null): void
    {
        SurveyWaveLog::create([
            'survey_wave_id' => $wave->id,
            'user_id' => $user?->id,
            'status' => $status,
            'message' => $message,
        ]);
    }
}
