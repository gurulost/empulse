<?php

namespace App\Services;

use App\Models\BillingAdminTransferRequest;
use App\Models\Companies;
use App\Models\OrganizationBillingAdmin;
use App\Models\OrganizationEntitlement;
use App\Models\OrganizationUsageEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationEntitlementService
{
    public function current(int|Companies $company): ?OrganizationEntitlement
    {
        $companyId = $company instanceof Companies ? $company->id : $company;

        return OrganizationEntitlement::where('company_id', $companyId)->first();
    }

    public function canDispatch(int|Companies $company): bool
    {
        $entitlement = $this->current($company);
        if (! $entitlement
            || ! in_array($entitlement->status, config('billing.dispatch_statuses', []), true)) {
            return false;
        }

        $expiry = $entitlement->ends_at
            ?? $entitlement->grace_ends_at
            ?? $entitlement->trial_ends_at;

        return ! $expiry || $expiry->isFuture();
    }

    public function canAccessCollectedData(int|Companies $company): bool
    {
        $entitlement = $this->current($company);
        if (! $entitlement
            || ! in_array($entitlement->status, config('billing.data_access_statuses', []), true)) {
            return false;
        }
        $expiry = $entitlement->grace_ends_at ?? $entitlement->ends_at;

        return ! $expiry || $expiry->isFuture();
    }

    public function hasFeature(int|Companies $company, string $feature): bool
    {
        $entitlement = $this->current($company);

        return $entitlement
            && $this->canDispatch($company)
            && in_array($feature, $entitlement->features ?? [], true);
    }

    public function grantManual(
        int|Companies $company,
        string $planKey,
        Carbon $expiresAt,
        ?User $actor = null
    ): OrganizationEntitlement {
        if ($expiresAt->isPast()) {
            throw new \InvalidArgumentException('Manual grants require a future expiry.');
        }

        $companyId = $company instanceof Companies ? $company->id : $company;
        $plan = $this->plan($planKey);
        $currentVersion = (int) (OrganizationEntitlement::where('company_id', $companyId)->value('version') ?? 0);
        $entitlement = OrganizationEntitlement::updateOrCreate(
            ['company_id' => $companyId],
            [
                'plan_key' => $planKey,
                'status' => 'manual_grant',
                'source' => 'manual',
                'features' => $plan['features'],
                'limits' => $plan['limits'],
                'starts_at' => now(),
                'ends_at' => $expiresAt,
                'last_reconciled_at' => now(),
                'version' => $currentVersion + 1,
            ]
        );

        app(AuditTrailService::class)->record(
            'billing.manual_grant',
            $actor,
            $companyId,
            OrganizationEntitlement::class,
            $entitlement->id,
            [
                'plan_key' => $planKey,
                'expires_at' => $expiresAt->toISOString(),
            ]
        );

        return $entitlement->fresh();
    }

    public function syncFromStripe(
        Companies $company,
        ?Carbon $eventCreatedAt = null
    ): OrganizationEntitlement {
        return DB::transaction(function () use ($company, $eventCreatedAt) {
            $entitlement = OrganizationEntitlement::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->first();
            if ($entitlement?->last_stripe_event_at
                && $eventCreatedAt
                && $eventCreatedAt->lt($entitlement->last_stripe_event_at)) {
                return $entitlement;
            }

            $subscription = $company->subscriptions()->orderByDesc('created_at')->first();
            $price = $subscription?->stripe_price;
            $planKey = $this->planKeyForPrice($price);
            $plan = $planKey ? $this->plan($planKey) : ['features' => [], 'limits' => []];
            $status = $subscription?->stripe_status ?: 'none';
            $graceEndsAt = $entitlement?->grace_ends_at;
            $endsAt = $subscription?->ends_at;
            if (in_array($status, ['past_due', 'unpaid'], true)
                && (! $graceEndsAt || $graceEndsAt->isPast())) {
                $graceEndsAt = now()->addDays((int) config('billing.lifecycle.past_due_data_grace_days', 30));
            } elseif ($status === 'canceled' && ! $endsAt) {
                $endsAt = now()->addDays((int) config('billing.lifecycle.canceled_data_grace_days', 30));
            } elseif (in_array($status, ['active', 'trialing'], true)) {
                $graceEndsAt = null;
            }

            return OrganizationEntitlement::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'plan_key' => $planKey ?: 'unknown',
                    'status' => $status,
                    'source' => 'stripe',
                    'stripe_subscription_id' => $subscription?->stripe_id,
                    'stripe_price_id' => $price,
                    'features' => $plan['features'],
                    'limits' => $plan['limits'],
                    'starts_at' => $subscription?->created_at,
                    'trial_ends_at' => $subscription?->trial_ends_at,
                    'grace_ends_at' => $graceEndsAt,
                    'ends_at' => $endsAt,
                    'last_stripe_event_at' => $eventCreatedAt ?: now(),
                    'last_reconciled_at' => now(),
                    'version' => ((int) ($entitlement?->version ?? 0)) + 1,
                ]
            );
        });
    }

    public function ensureBillingOwner(Companies $company, User $user): OrganizationBillingAdmin
    {
        if ((int) $user->company_id !== (int) $company->id) {
            throw new \DomainException('Billing owner must belong to the organization.');
        }

        return OrganizationBillingAdmin::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $user->id],
            [
                'role' => 'owner',
                'status' => 'active',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]
        );
    }

    public function isBillingAdmin(User $user, int|Companies $company): bool
    {
        $companyId = $company instanceof Companies ? $company->id : $company;

        return OrganizationBillingAdmin::query()
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->exists();
    }

    public function recordUsage(
        int $companyId,
        string $idempotencyKey,
        string $metric,
        float $quantity,
        string $unit,
        array $metadata = []
    ): OrganizationUsageEvent {
        return OrganizationUsageEvent::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'company_id' => $companyId,
                'metric' => $metric,
                'quantity' => $quantity,
                'unit' => $unit,
                'occurred_at' => now(),
                'metadata' => $metadata ?: null,
            ]
        );
    }

    public function usageSummary(int|Companies $company, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $companyId = $company instanceof Companies ? $company->id : $company;
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfMonth();
        $rows = OrganizationUsageEvent::where('company_id', $companyId)
            ->whereBetween('occurred_at', [$from, $to])
            ->select('metric')
            ->selectRaw('SUM(quantity) as quantity')
            ->groupBy('metric')
            ->pluck('quantity', 'metric')
            ->map(fn ($quantity) => (float) $quantity)
            ->all();
        $entitlement = $this->current($companyId);

        return [
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'metrics' => $rows,
            'limits' => $entitlement?->limits ?? [],
        ];
    }

    /**
     * Serialize the limit decision and usage write against the company row.
     *
     * The callback runs in the same transaction so assignment dispatch state
     * cannot commit without its corresponding usage reservation.
     */
    public function consumeActiveRespondent(int $companyId, int $userId, callable $callback): bool
    {
        return DB::transaction(function () use ($companyId, $userId, $callback): bool {
            Companies::query()->whereKey($companyId)->lockForUpdate()->firstOrFail();

            $period = now()->format('Y-m');
            $key = "active_respondent:{$companyId}:{$period}:{$userId}";
            $alreadyConsumed = OrganizationUsageEvent::where('idempotency_key', $key)->exists();

            if (! $alreadyConsumed) {
                $entitlement = OrganizationEntitlement::query()
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->first();
                $limit = $entitlement?->limits['active_respondents'] ?? null;
                $used = OrganizationUsageEvent::where('company_id', $companyId)
                    ->where('metric', 'active_respondents')
                    ->where('occurred_at', '>=', now()->startOfMonth())
                    ->sum('quantity');

                if ($limit !== null && (float) $used >= (float) $limit) {
                    return false;
                }

                OrganizationUsageEvent::create([
                    'company_id' => $companyId,
                    'idempotency_key' => $key,
                    'metric' => 'active_respondents',
                    'quantity' => 1,
                    'unit' => 'respondent',
                    'occurred_at' => now(),
                    'metadata' => ['user_id' => $userId, 'period' => $period],
                ]);
            }

            $callback();

            return true;
        }, 3);
    }

    public function initiateBillingOwnerTransfer(
        Companies $company,
        User $currentOwner,
        User $target,
        string $reason
    ): BillingAdminTransferRequest {
        if (! $this->isBillingOwner($currentOwner, $company)) {
            throw new \DomainException('Only the current billing owner can initiate a transfer.');
        }
        if ((int) $target->company_id !== (int) $company->id
            || $target->status !== 'active'
            || $target->id === $currentOwner->id) {
            throw new \DomainException('The new owner must be a different active organization member.');
        }

        BillingAdminTransferRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->update(['status' => 'revoked', 'decided_at' => now()]);
        $transfer = BillingAdminTransferRequest::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'from_user_id' => $currentOwner->id,
            'to_user_id' => $target->id,
            'requested_by_user_id' => $currentOwner->id,
            'status' => 'pending',
            'reason' => $reason,
            'expires_at' => now()->addDays((int) config('billing.lifecycle.transfer_expiry_days', 7)),
        ]);
        app(AuditTrailService::class)->record(
            'billing.owner_transfer.requested',
            $currentOwner,
            $company->id,
            BillingAdminTransferRequest::class,
            $transfer->id,
            ['to_user_id' => $target->id, 'expires_at' => $transfer->expires_at->toISOString()]
        );

        return $transfer;
    }

    public function decideBillingOwnerTransfer(
        BillingAdminTransferRequest $transfer,
        User $target,
        bool $accept
    ): BillingAdminTransferRequest {
        if ($transfer->status !== 'pending'
            || $transfer->expires_at->isPast()
            || $transfer->to_user_id !== $target->id) {
            throw new \DomainException('This billing owner transfer is not available to accept.');
        }

        return DB::transaction(function () use ($transfer, $target, $accept) {
            $locked = BillingAdminTransferRequest::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw new \DomainException('This billing owner transfer has already been decided.');
            }
            if (! $accept) {
                $locked->update([
                    'status' => 'rejected',
                    'decided_by_user_id' => $target->id,
                    'decided_at' => now(),
                ]);

                return $locked->fresh();
            }

            OrganizationBillingAdmin::where('company_id', $locked->company_id)
                ->where('role', 'owner')
                ->where('status', 'active')
                ->update(['role' => 'admin']);
            OrganizationBillingAdmin::updateOrCreate(
                ['company_id' => $locked->company_id, 'user_id' => $target->id],
                [
                    'role' => 'owner',
                    'status' => 'active',
                    'approved_by' => $locked->from_user_id,
                    'approved_at' => now(),
                    'revoked_at' => null,
                ]
            );
            $locked->update([
                'status' => 'accepted',
                'decided_by_user_id' => $target->id,
                'decided_at' => now(),
            ]);
            app(AuditTrailService::class)->record(
                'billing.owner_transfer.accepted',
                $target,
                $locked->company_id,
                BillingAdminTransferRequest::class,
                $locked->id,
                ['from_user_id' => $locked->from_user_id, 'to_user_id' => $target->id]
            );

            return $locked->fresh();
        });
    }

    public function isBillingOwner(User $user, int|Companies $company): bool
    {
        $companyId = $company instanceof Companies ? $company->id : $company;

        return OrganizationBillingAdmin::where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->exists();
    }

    public function plan(string $planKey): array
    {
        $plan = config("billing.catalog.{$planKey}");
        if (! is_array($plan)) {
            throw new \InvalidArgumentException("Unknown plan {$planKey}.");
        }

        return $plan;
    }

    public function planKeyForPrice(?string $priceId): ?string
    {
        if (! $priceId) {
            return null;
        }

        foreach (config('billing.catalog', []) as $key => $plan) {
            if (($plan['stripe_price'] ?? null) === $priceId) {
                return (string) $key;
            }
        }

        return null;
    }
}
