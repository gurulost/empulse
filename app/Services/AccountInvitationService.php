<?php

namespace App\Services;

use App\Models\AccountInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountInvitationService
{
    public function __construct(protected OrganizationService $organizations) {}

    public function issue(User $user, ?User $actor = null): array
    {
        return DB::transaction(function () use ($user, $actor) {
            AccountInvitation::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'delivery_token' => null,
                    'delivery_status' => 'revoked',
                ]);

            $plainTextToken = Str::random(64);
            $invitation = AccountInvitation::create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'invited_by' => $actor?->id,
                'email' => $user->email,
                'role' => $user->role,
                'token_hash' => AccountInvitation::hashToken($plainTextToken),
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'delivery_token' => $plainTextToken,
                'delivery_idempotency_key' => (string) Str::uuid(),
                'delivery_status' => 'pending',
            ]);

            return [
                'invitation' => $invitation,
                'token' => $plainTextToken,
            ];
        });
    }

    public function resolve(string $plainTextToken): AccountInvitation
    {
        $invitation = AccountInvitation::query()
            ->where('token_hash', AccountInvitation::hashToken($plainTextToken))
            ->with('user')
            ->first();

        if (! $invitation) {
            abort(404);
        }

        if ($invitation->status !== 'pending' || $invitation->revoked_at) {
            throw new HttpException(410, 'This invitation is no longer active.');
        }

        if ($invitation->expires_at->isPast()) {
            throw new HttpException(410, 'This invitation has expired.');
        }

        if (! $invitation->user || $invitation->user->email !== $invitation->email) {
            throw new HttpException(409, 'This invitation cannot be accepted.');
        }

        return $invitation;
    }

    public function accept(AccountInvitation $invitation, string $password): User
    {
        return DB::transaction(function () use ($invitation, $password) {
            $invitation = AccountInvitation::query()
                ->whereKey($invitation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($invitation->status !== 'pending'
                || $invitation->revoked_at
                || $invitation->expires_at->isPast()) {
                throw new HttpException(410, 'This invitation is no longer active.');
            }

            $user = User::query()->lockForUpdate()->findOrFail($invitation->user_id);
            $user->forceFill([
                'password' => Hash::make($password),
                'status' => 'active',
                'left_at' => null,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();

            DB::table('company_worker')
                ->where('company_id', $invitation->company_id)
                ->where('email', $invitation->email)
                ->update([
                    'status' => 'active',
                    'left_at' => null,
                ]);

            $worker = DB::table('company_worker')
                ->where('company_id', $invitation->company_id)
                ->where('email', $invitation->email)
                ->first();
            $this->organizations->synchronize(
                $user->fresh(),
                null,
                $worker?->department,
                $this->organizations->supervisorEmail(
                    (int) $invitation->company_id,
                    $worker?->supervisor
                ),
                'active'
            );

            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'delivery_token' => null,
            ]);

            return $user;
        });
    }
}
