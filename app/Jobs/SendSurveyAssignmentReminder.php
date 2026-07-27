<?php

namespace App\Jobs;

use App\Models\SurveyAssignment;
use App\Services\DeliveryTrustService;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSurveyAssignmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        protected int $assignmentId,
        protected int $reminderNumber
    ) {}

    public function handle(EmailService $email, DeliveryTrustService $delivery): void
    {
        $assignment = SurveyAssignment::with(['user', 'surveyWave'])->find($this->assignmentId);
        if (! $assignment
            || ! $assignment->user
            || $assignment->status !== 'pending'
            || $assignment->token_revoked_at
            || ($assignment->due_at && $assignment->due_at->isPast())
            || ($assignment->surveyWave
                && ! in_array($assignment->surveyWave->status, ['active', 'scheduled'], true))) {
            return;
        }
        if ($assignment->surveyWave
            && $this->reminderNumber > (int) $assignment->surveyWave->reminder_limit) {
            return;
        }

        $key = "assignment:{$assignment->id}:reminder:{$this->reminderNumber}";
        $attempt = $delivery->begin(
            $assignment,
            'reminder',
            $key,
            function () use ($assignment): string {
                $token = $assignment->rotateAccessToken($assignment->due_at ?: now()->addDays(7));

                return route('survey.take', ['token' => $token]);
            }
        );
        if (! $attempt) {
            return;
        }

        $response = $email->sendSurveyReminder(
            $assignment->user->email,
            $assignment->user->name,
            $delivery->surveyUrl($attempt),
            $assignment->user->company_title ?: 'your team',
            $assignment->wave_label,
            $delivery->providerIdempotencyKey($attempt)
        );
        if ((int) ($response['status'] ?? 500) >= 400) {
            $delivery->record($attempt, 'failed', null, [
                'status_code' => (int) ($response['status'] ?? 500),
            ]);
            throw new \RuntimeException('Survey reminder delivery failed.');
        }

        $delivery->record(
            $attempt,
            'accepted',
            $response['provider_message_id'] ?? null,
            ['idempotent_replay' => (bool) ($response['idempotent_replay'] ?? false)]
        );
        $assignment->update([
            'reminder_count' => max($assignment->reminder_count, $this->reminderNumber),
            'last_reminded_at' => now(),
        ]);
    }
}
