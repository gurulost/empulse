<?php

namespace App\Jobs;

use App\Models\SurveyAssignment;
use App\Models\SurveyWave;
use App\Models\SurveyWaveLog;
use App\Models\User;
use App\Services\OnboardingTelemetryService;
use App\Services\OrganizationEntitlementService;
use App\Services\SurveyCohortService;
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

class ProcessSurveyWave implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor;

    public function __construct(protected int $waveId)
    {
        $this->uniqueFor = max(60, SurveyWaveAutomation::processingTimeoutMinutes() * 60);
    }

    public function uniqueId(): string
    {
        return "survey-wave:{$this->waveId}";
    }

    public function handle(
        SurveyService $surveyService,
        ?OnboardingTelemetryService $telemetry = null,
        ?SurveyCohortService $cohorts = null
    ): void {
        $cohorts = $cohorts ?: app(SurveyCohortService::class);
        $telemetry = $telemetry ?: app(OnboardingTelemetryService::class);
        $wave = SurveyWave::with('survey', 'surveyVersion')->find($this->waveId);
        if (! $wave || ! $wave->survey || ! $wave->company_id) {
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

        if ($wave->kind === 'full'
            && $wave->status === 'active'
            && $wave->assignments()->exists()) {
            $this->logEvent($wave, null, 'skipped', 'Full wave was already dispatched.');

            return;
        }

        $manager = CompanyBilling::manager($wave->company_id);
        if (! CompanyBilling::allowsScheduling((int) $wave->company_id)) {
            $wave->update(['status' => 'paused']);
            $this->logEvent(
                $wave,
                null,
                'paused',
                'Billing became inactive before processing started.'
            );

            return;
        }

        if ($wave->kind === 'drip'
            && ! CompanyBilling::hasFeature((int) $wave->company_id, 'recurring_waves')) {
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

        $cycle = $cohorts->freeze($wave, $targetRoles);
        $audience = $cycle->audienceMembers->keyBy('user_id');
        $companyUsers = User::query()
            ->whereIn('id', $audience->keys())
            ->orderBy('id')
            ->get();
        $stats = [
            'dispatched' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($companyUsers as $user) {
            try {
                $entitlements = app(OrganizationEntitlementService::class);
                $assignment = $surveyService->getOrCreateAssignmentForWave(
                    $user,
                    $wave,
                    $cycle,
                    $audience->get($user->id)
                );
                if (! $assignment) {
                    $stats['skipped']++;
                    $this->logEvent($wave, $user, 'skipped', 'No assignment available.');

                    continue;
                }

                if ($message = $this->shouldSkipAssignment($assignment)) {
                    $stats['skipped']++;
                    $this->logEvent($wave, $user, 'skipped', $message);

                    continue;
                }

                $consumed = $entitlements->consumeActiveRespondent(
                    (int) $wave->company_id,
                    $user->id,
                    function () use ($assignment, $wave, $cycle, $entitlements): void {
                        $assignment->update([
                            'survey_wave_id' => $wave->id,
                            'wave_label' => $wave->label,
                            'last_dispatched_at' => now(),
                            'dispatch_count' => ($assignment->dispatch_count ?? 0) + 1,
                            'invite_status' => 'queued',
                            'invite_error' => null,
                        ]);
                        $entitlements->recordUsage(
                            (int) $wave->company_id,
                            "assignment_dispatch:{$cycle->id}:{$assignment->user_id}",
                            'dispatched_assignments',
                            1,
                            'assignment',
                            [
                                'survey_wave_id' => $wave->id,
                                'survey_wave_cycle_id' => $cycle->id,
                            ]
                        );
                    }
                );
                if (! $consumed) {
                    $stats['skipped']++;
                    $this->logEvent($wave, $user, 'skipped', 'Plan active-respondent limit reached.');

                    continue;
                }

                SendSurveyAssignmentInvitation::dispatch($assignment->id);

                $stats['dispatched']++;
                $this->logEvent($wave, $user, 'dispatched', 'Assignment refreshed and invitation queued.');
            } catch (Throwable $e) {
                Log::error('Wave scheduling failed', [
                    'wave' => $wave->id,
                    'user' => $user->id,
                    'exception_class' => $e::class,
                ]);
                $stats['failed']++;
                $this->logEvent($wave, $user, 'failed', 'Assignment scheduling failed unexpectedly.');
            }
        }

        $cohorts->markDispatched($cycle);
        $this->finalizeWave($wave, $stats, $telemetry);
    }

    public function failed(Throwable $exception): void
    {
        $wave = SurveyWave::find($this->waveId);
        if (! $wave) {
            return;
        }

        $recoveredStatus = null;
        if ($wave->status === 'processing') {
            $recoveredStatus = ($wave->due_at && $wave->due_at->isPast()) ? 'completed' : 'scheduled';
            $wave->update(['status' => $recoveredStatus]);
        }

        $message = 'Queue job failed unexpectedly.';
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
            'exception_class' => $exception::class,
            'recovered_status' => $recoveredStatus,
        ]);
    }

    protected function shouldSkipAssignment(SurveyAssignment $assignment): ?string
    {
        if ($assignment->status === 'completed') {
            return 'Assignment already completed.';
        }

        if ($assignment->last_dispatched_at || (int) $assignment->dispatch_count > 0) {
            return 'Assignment was already queued for this frozen wave occurrence.';
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
        if ($wave->due_at && $wave->due_at->isPast()) {
            return 'completed';
        }

        if ($wave->assignments()->exists()
            && ! $wave->assignments()->where('status', '!=', 'completed')->exists()) {
            return 'completed';
        }

        if ($wave->kind === 'full') {
            return 'active';
        }

        if ($wave->cadence === 'manual') {
            $assignmentQuery = $wave->assignments();
            if (! $assignmentQuery->exists()) {
                return 'scheduled';
            }

            if (! $assignmentQuery->whereNull('last_dispatched_at')->exists()) {
                return 'active';
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
