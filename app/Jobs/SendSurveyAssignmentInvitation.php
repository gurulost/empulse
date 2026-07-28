<?php

namespace App\Jobs;

use App\Models\EmailDeliveryEvent;
use App\Models\SurveyAssignment;
use App\Services\DeliveryTrustService;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSurveyAssignmentInvitation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $uniqueFor = 900;

    public function __construct(protected int $assignmentId) {}

    public function uniqueId(): string
    {
        return (string) $this->assignmentId;
    }

    public function handle(
        EmailService $emailService,
        ?DeliveryTrustService $delivery = null
    ): void {
        $delivery = $delivery ?: app(DeliveryTrustService::class);
        $assignment = SurveyAssignment::with(['user', 'surveyWave'])->find($this->assignmentId);
        if (! $assignment || ! $assignment->user) {
            return;
        }

        if ($assignment->status !== 'pending'
            || $assignment->token_revoked_at
            || ($assignment->due_at && $assignment->due_at->isPast())
            || ($assignment->surveyWave
                && ! in_array($assignment->surveyWave->status, ['scheduled', 'processing', 'active'], true))) {
            $assignment->update([
                'invite_status' => 'skipped',
                'invite_error' => 'Assignment was no longer eligible when the invitation job ran.',
            ]);

            return;
        }

        if (blank($assignment->user->email)) {
            $assignment->update([
                'invite_status' => 'failed',
                'invite_error' => 'Assignment is missing a recipient email address.',
            ]);

            return;
        }

        $idempotencyKey = sprintf(
            'assignment:%d:invitation:%d',
            $assignment->id,
            max(1, (int) $assignment->dispatch_count)
        );
        $attempt = $delivery->begin(
            $assignment,
            'invitation',
            $idempotencyKey,
            function () use ($assignment): string {
                $plainTextToken = $assignment->rotateAccessToken(
                    $assignment->due_at ?: now()->addDays(14)
                );

                return route('survey.take', ['token' => $plainTextToken]);
            }
        );
        if (! $attempt) {
            $contact = $delivery->contactFor($assignment);
            $baseAttempt = EmailDeliveryEvent::where('idempotency_key', $idempotencyKey)->first();
            $assignment->update([
                'invite_status' => $contact->suppressed_at
                    ? 'suppressed'
                    : ($baseAttempt && $delivery->retryWindowExpired($baseAttempt)
                        ? 'manual_review'
                        : $assignment->invite_status),
                'invite_error' => $contact->suppressed_at
                    ? "Address suppressed after {$contact->suppression_reason}."
                    : ($baseAttempt && $delivery->retryWindowExpired($baseAttempt)
                        ? 'Automatic retry window expired; verify provider activity before resending.'
                        : $assignment->invite_error),
            ]);

            return;
        }

        $response = $emailService->sendSurveyInvitation(
            $assignment->user->email,
            $assignment->user->name,
            $delivery->surveyUrl($attempt),
            $assignment->user->company_title ?: 'your team',
            $assignment->wave_label ?: $assignment->surveyWave?->label,
            $delivery->providerIdempotencyKey($attempt)
        );

        if ((int) ($response['status'] ?? 500) >= 400) {
            $message = (string) ($response['message'] ?? 'Invitation delivery failed.');

            $assignment->update([
                'invite_status' => 'failed',
                'invite_error' => $message,
            ]);
            $delivery->record($attempt, 'failed', null, [
                'status_code' => (int) ($response['status'] ?? 500),
                'message' => mb_substr($message, 0, 500),
            ]);

            Log::warning('Survey invitation failed', [
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'message' => $message,
            ]);

            if (! App::environment('testing') && (int) ($response['status'] ?? 500) >= 500) {
                throw new \RuntimeException('Invitation provider temporarily unavailable.');
            }

            return;
        }

        $delivery->record(
            $attempt,
            'accepted',
            $response['provider_message_id'] ?? null,
            ['idempotent_replay' => (bool) ($response['idempotent_replay'] ?? false)]
        );
        $assignment->update([
            'invite_status' => 'accepted',
            'invite_error' => null,
            'invited_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $assignment = SurveyAssignment::find($this->assignmentId);
        if (! $assignment) {
            return;
        }

        $assignment->update([
            'invite_status' => 'failed',
            'invite_error' => 'Invitation delivery job failed unexpectedly.',
        ]);

        Log::error('Survey invitation job failed', [
            'assignment_id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'exception_class' => $exception::class,
        ]);
    }
}
