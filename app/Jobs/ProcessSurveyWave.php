<?php

namespace App\Jobs;

use App\Jobs\SendSurveyAssignmentInvitation;
use App\Models\SurveyAssignment;
use App\Models\SurveyWave;
use App\Models\SurveyWaveLog;
use App\Services\OnboardingTelemetryService;
use App\Models\User;
use App\Services\SurveyService;
use App\Support\CompanyBilling;
use App\Support\SurveyWaveAutomation;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessSurveyWave implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $uniqueFor;

    public function __construct(protected int $waveId)
    {
        $this->uniqueFor = max(60, SurveyWaveAutomation::processingTimeoutMinutes() * 60);
    }

    public function uniqueId(): string
    {
        return "survey-wave:{$this->waveId}";
    }

    public function handle(SurveyService $surveyService, ?OnboardingTelemetryService $telemetry = null): void
    {
        $telemetry = $telemetry ?: app(OnboardingTelemetryService::class);
        $wave = SurveyWave::with('survey', 'surveyVersion')->find($this->waveId);
        if (!$wave || !$wave->survey || !$wave->company_id) {
            return;
        }

        if ($wave->status === 'paused') {
            $this->logEvent($wave, null, 'skipped', 'Wave was paused before processing started.');
            return;
        }

        if ($wave->due_at && $wave->due_at->isPast()) {
            $wave->update(['status' => 'completed']);
            $this->logEvent($wave, null, 'completed', 'Wave passed its due date before processing started.');
            return;
        }

        $manager = CompanyBilling::manager($wave->company_id);
        if (!CompanyBilling::allowsScheduling($manager)) {
            $wave->update(['status' => 'paused']);
            $this->logEvent(
                $wave,
                null,
                'paused',
                'Billing became inactive before processing started.'
            );
            return;
        }

        if ($wave->kind === 'drip' && !SurveyWaveAutomation::dripEnabledForTariff((int) $manager?->tariff)) {
            $wave->update(['status' => 'paused']);
            $this->logEvent($wave, null, 'paused', 'Current plan does not allow drip cadences.');
            return;
        }

        $targetRoles = collect($wave->target_roles ?: config('billing.default_wave_roles', [1, 2, 3, 4]))
            ->map(fn ($role) => (int) $role)
            ->filter(fn ($role) => in_array($role, [1, 2, 3, 4], true))
            ->unique()
            ->values()
            ->all();

        if (empty($targetRoles)) {
            $wave->update(['status' => 'scheduled']);
            $this->logEvent($wave, null, 'skipped', 'Wave has no eligible target roles.');
            return;
        }

        $companyUsers = User::where('company_id', $wave->company_id)
            ->whereIn('role', $targetRoles)
            ->get();
        $stats = [
            'dispatched' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($companyUsers as $user) {
            try {
                $assignment = $surveyService->getOrCreateAssignmentForWave($user, $wave);
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
                    'invite_status' => 'queued',
                    'invite_error' => null,
                ]);

                SendSurveyAssignmentInvitation::dispatch($assignment->id);

                $stats['dispatched']++;
                $this->logEvent($wave, $user, 'dispatched', 'Assignment refreshed and invitation queued.');
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

        $this->finalizeWave($wave, $stats, $telemetry);
    }

    public function failed(Throwable $exception): void
    {
        $wave = SurveyWave::find($this->waveId);
        if (!$wave) {
            return;
        }

        $recoveredStatus = null;
        if ($wave->status === 'processing') {
            $recoveredStatus = ($wave->due_at && $wave->due_at->isPast()) ? 'completed' : 'scheduled';
            $wave->update(['status' => $recoveredStatus]);
        }

        $message = 'Queue job failed: ' . $exception->getMessage();
        if ($recoveredStatus) {
            $message .= " Wave reset to {$recoveredStatus}.";
        }

        SurveyWaveLog::create([
            'survey_wave_id' => $wave->id,
            'status' => 'error',
            'message' => $message,
        ]);

        Log::error('Wave processing job failed', [
            'wave' => $wave->id,
            'error' => $exception->getMessage(),
            'recovered_status' => $recoveredStatus,
        ]);
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

    protected function finalizeWave(SurveyWave $wave, array $stats, OnboardingTelemetryService $telemetry): void
    {
        $nextStatus = $this->determineNextStatus($wave);

        $update = ['status' => $nextStatus];
        if ($stats['dispatched'] > 0) {
            $update['last_dispatched_at'] = now();
        }

        $wave->update($update);

        if ($stats['dispatched'] > 0) {
            $telemetry->recordFirstWaveDispatched($wave, CompanyBilling::manager($wave->company_id));
        }

        $this->logEvent(
            $wave,
            null,
            $nextStatus,
            sprintf(
                'Dispatch summary — invitations queued: %d, skipped: %d, failed: %d.',
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
