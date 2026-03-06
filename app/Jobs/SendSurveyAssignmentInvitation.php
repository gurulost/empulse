<?php

namespace App\Jobs;

use App\Models\SurveyAssignment;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSurveyAssignmentInvitation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $assignmentId)
    {
    }

    public function handle(EmailService $emailService): void
    {
        $assignment = SurveyAssignment::with(['user', 'surveyWave'])->find($this->assignmentId);
        if (!$assignment || !$assignment->user) {
            return;
        }

        if ($assignment->status === 'completed') {
            $assignment->update([
                'invite_status' => 'skipped',
                'invite_error' => 'Assignment was already completed before the invitation was sent.',
            ]);

            return;
        }

        if (blank($assignment->token) || blank($assignment->user->email)) {
            $assignment->update([
                'invite_status' => 'failed',
                'invite_error' => 'Assignment is missing a survey token or recipient email address.',
            ]);

            return;
        }

        $assignment->update([
            'invite_status' => 'sending',
            'invite_error' => null,
        ]);

        $response = $emailService->sendSurveyInvitation(
            $assignment->user->email,
            $assignment->user->name,
            route('survey.take', ['token' => $assignment->token]),
            $assignment->user->company_title ?: 'your team',
            $assignment->wave_label ?: $assignment->surveyWave?->label
        );

        if ((int) ($response['status'] ?? 500) >= 400) {
            $message = (string) ($response['message'] ?? 'Invitation delivery failed.');

            $assignment->update([
                'invite_status' => 'failed',
                'invite_error' => $message,
            ]);

            Log::warning('Survey invitation failed', [
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'message' => $message,
            ]);

            return;
        }

        $assignment->update([
            'invite_status' => 'sent',
            'invite_error' => null,
            'invited_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $assignment = SurveyAssignment::find($this->assignmentId);
        if (!$assignment) {
            return;
        }

        $assignment->update([
            'invite_status' => 'failed',
            'invite_error' => $exception->getMessage(),
        ]);

        Log::error('Survey invitation job failed', [
            'assignment_id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
