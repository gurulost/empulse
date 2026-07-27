<?php

namespace App\Jobs;

use App\Enums\Role;
use App\Models\AccountInvitation;
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

class SendAccountInvitation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $uniqueFor = 900;

    public function __construct(public int $invitationId) {}

    public function uniqueId(): string
    {
        return (string) $this->invitationId;
    }

    public function handle(EmailService $emailService): void
    {
        $invitation = AccountInvitation::with(['user'])->find($this->invitationId);
        if (! $invitation || ! $invitation->user || $invitation->status !== 'pending') {
            return;
        }

        if ($invitation->delivery_status === 'accepted') {
            return;
        }

        if (! $invitation->delivery_token) {
            $invitation->update([
                'delivery_status' => 'failed',
                'delivery_error' => 'Invitation delivery token is unavailable.',
            ]);

            return;
        }

        $invitation->increment('delivery_attempts');
        $invitation->update([
            'delivery_status' => 'sending',
            'delivery_error' => null,
            'delivery_last_attempt_at' => now(),
        ]);

        $user = $invitation->user;
        $companyTitle = (string) ($user->company_title ?: 'your organization');
        $roleLabel = match (Role::tryFrom((int) $user->role)) {
            Role::MANAGER => 'company manager',
            Role::CHIEF => 'department chief',
            Role::TEAMLEAD => 'teamlead',
            Role::EMPLOYEE => 'employee',
            default => 'member',
        };
        $setupLink = route('invitations.show', ['token' => $invitation->delivery_token]);
        $response = $emailService->sendLetter(
            $user->email,
            $user->name,
            "{$companyTitle} account invitation",
            view('admin-msg', [
                'name' => $user->name,
                'link' => config('app.login_url'),
                'email' => $user->email,
                'password' => null,
                'setupLink' => $setupLink,
                'company' => $companyTitle,
                'status' => $roleLabel,
                'department' => null,
                'teamlead' => null,
                'surveyLink' => null,
            ])->render(),
            $invitation->delivery_idempotency_key
        );

        if ((int) ($response['status'] ?? 500) >= 400) {
            $message = mb_substr((string) ($response['message'] ?? 'Invitation delivery failed.'), 0, 1000);
            $invitation->update([
                'delivery_status' => 'failed',
                'delivery_error' => $message,
            ]);
            Log::warning('Account invitation delivery failed', [
                'invitation_id' => $invitation->id,
                'user_id' => $user->id,
                'status_code' => (int) ($response['status'] ?? 500),
            ]);

            if (! App::environment('testing') && (int) ($response['status'] ?? 500) >= 500) {
                throw new \RuntimeException('Invitation provider temporarily unavailable.');
            }

            return;
        }

        $invitation->update([
            'delivery_status' => 'accepted',
            'provider_message_id' => $response['provider_message_id'] ?? null,
            'delivery_error' => null,
            'delivered_at' => now(),
            'delivery_token' => null,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        AccountInvitation::whereKey($this->invitationId)->update([
            'delivery_status' => 'failed',
            'delivery_error' => 'Account invitation delivery job failed unexpectedly.',
        ]);
    }
}
