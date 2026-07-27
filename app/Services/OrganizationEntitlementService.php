<?php

namespace App\Services;

use App\Models\BillingAdminTransferRequest;
use App\Models\BillingCatalogVersion;
use App\Models\Companies;
use App\Models\OrganizationBillingAdmin;
use App\Models\OrganizationEntitlement;
use App\Models\OrganizationEntitlementVersion;
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
        $entitlement = DB::transaction(function () use ($companyId, $planKey, $expiresAt, $actor) {
            Companies::query()->whereKey($companyId)->lockForUpdate()->firstOrFail();
            $plan = $this->plan($planKey);
            $catalog = $this->catalogVersion($actor);
            $currentVersion = (int) (OrganizationEntitlement::where('company_id', $companyId)->value('version') ?? 0);
            $entitlement = OrganizationEntitlement::updateOrCreate(
                ['company_id' => $companyId],
                [
                    'billing_catalog_version_id' => $catalog->id,
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
            $this->snapshotEntitlement($entitlement, $catalog);

            return $entitlement;
        });

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
            $nextVersion = $entitlement instanceof OrganizationEntitlement
                ? $entitlement->version + 1
                : 1;
            $catalog = $this->catalogVersion();

            $current = OrganizationEntitlement::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'billing_catalog_version_id' => $catalog->id,
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
                    'version' => $nextVersion,
                ]
            );
            $this->snapshotEntitlement($current, $catalog);

            return $current;
        });
    }

    public function ensureBillingOwner(Companies $company, User $user): OrganizationBillingAdmin
    {
        if ((int) $user->company_id !== (int) $company->id) {
            throw new \DomainException('Billing owner must belong to the organization.');
        }

        return DB::transaction(function () use ($company, $user): OrganizationBillingAdmin {
            Companies::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();
            $owner = OrganizationBillingAdmin::query()
                ->where('company_id', $company->id)
                ->where('role', 'owner')
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();
            if ($owner && (int) $owner->user_id !== (int) $user->id) {
                $ownerUserActive = User::query()
                    ->whereKey($owner->user_id)
                    ->where('status', 'active')
                    ->exists();
                if ($ownerUserActive) {
                    throw new \DomainException(
                        'This organization already has an active billing owner; use the explicit transfer workflow.'
                    );
                }
                $owner->update(['role' => 'admin']);
            }

            return OrganizationBillingAdmin::updateOrCreate(
                ['company_id' => $company->id, 'user_id' => $user->id],
                [
                    'role' => 'owner',
                    'status' => 'active',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'revoked_at' => null,
                ]
            );
        });
    }

    public function isBillingAdmin(User $user, int|Companies $company): bool
    {
        $companyId = $company instanceof Companies ? $company->id : $company;

        return OrganizationBillingAdmin::query()
            ->join('users as billing_admin_user', 'billing_admin_user.id', '=', 'organization_billing_admins.user_id')
            ->where('organization_billing_admins.company_id', $companyId)
            ->where('organization_billing_admins.user_id', $user->id)
            ->where('organization_billing_admins.status', 'active')
            ->whereNull('organization_billing_admins.revoked_at')
            ->where('billing_admin_user.status', 'active')
            ->exists();
    }

    public function grantBillingAdmin(
        Companies $company,
        User $owner,
        User $target
    ): OrganizationBillingAdmin {
        if (! $this->isBillingOwner($owner, $company)) {
            throw new \DomainException('Only the current billing owner can approve billing administrators.');
        }
        if ((int) $target->company_id !== (int) $company->id
            || $target->status !== 'active'
            || $target->id === $owner->id) {
            throw new \DomainException('A billing administrator must be a different active organization member.');
        }

        $admin = OrganizationBillingAdmin::updateOrCreate(
            ['company_id' => $company->id, 'user_id' => $target->id],
            [
                'role' => 'admin',
                'status' => 'active',
                'approved_by' => $owner->id,
                'approved_at' => now(),
                'revoked_at' => null,
            ]
        );
        app(AuditTrailService::class)->record(
            'billing.admin.approved',
            $owner,
            $company->id,
            OrganizationBillingAdmin::class,
            $admin->id,
            ['user_id' => $target->id]
        );

        return $admin;
    }

    public function revokeBillingAdmin(
        Companies $company,
        User $owner,
        OrganizationBillingAdmin $admin
    ): OrganizationBillingAdmin {
        if (! $this->isBillingOwner($owner, $company)) {
            throw new \DomainException('Only the current billing owner can revoke billing administrators.');
        }
        if ((int) $admin->company_id !== (int) $company->id || $admin->role === 'owner') {
            throw new \DomainException('The billing owner cannot be revoked through the administrator workflow.');
        }
        if ($admin->status !== 'active' || $admin->revoked_at !== null) {
            throw new \DomainException('This billing administrator is not active.');
        }
        $admin->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);
        app(AuditTrailService::class)->record(
            'billing.admin.revoked',
            $owner,
            $company->id,
            OrganizationBillingAdmin::class,
            $admin->id,
            ['user_id' => $admin->user_id]
        );

        return $admin->fresh();
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
        $derivation = OrganizationUsageEvent::query()
            ->where('company_id', $companyId)
            ->whereBetween('occurred_at', [$from, $to])
            ->select(['metric', 'unit'])
            ->selectRaw('COUNT(*) as event_count')
            ->selectRaw('SUM(quantity) as quantity')
            ->selectRaw('MIN(occurred_at) as first_event_at')
            ->selectRaw('MAX(occurred_at) as last_event_at')
            ->groupBy(['metric', 'unit'])
            ->orderBy('metric')
            ->get()
            ->map(fn (OrganizationUsageEvent $row): array => [
                'metric' => $row->metric,
                'event_count' => (int) $row->event_count,
                'quantity' => (float) $row->quantity,
                'unit' => $row->unit,
                'first_event_at' => $row->first_event_at,
                'last_event_at' => $row->last_event_at,
            ])
            ->values()
            ->all();
        $entitlement = $this->current($companyId);
        $limits = $entitlement instanceof OrganizationEntitlement
            ? $entitlement->limits
            : [];

        return [
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'metrics' => $rows,
            'limits' => $limits,
            'derivation' => $derivation,
            'definitions' => [
                'active_respondents' => 'One event per unique organization member first reserved for a dispatch in the billing month. Retries and duplicate dispatch attempts reuse the same idempotency key.',
                'dispatched_assignments' => 'Sum of append-only assignment-dispatch usage events accepted for this organization and period.',
                'completed_responses' => 'Sum of append-only completed-response usage events accepted for this organization and period.',
            ],
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
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
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

    public function catalogVersion(?User $actor = null): BillingCatalogVersion
    {
        $definition = [
            'catalog' => config('billing.catalog', []),
            'trial' => config('billing.trial', []),
            'dispatch_statuses' => config('billing.dispatch_statuses', []),
            'data_access_statuses' => config('billing.data_access_statuses', []),
        ];
        $hash = hash('sha256', $this->canonicalJson($definition));

        return BillingCatalogVersion::firstOrCreate(
            ['definition_hash' => $hash],
            [
                'definition' => $definition,
                'status' => 'published',
                'published_by_user_id' => $actor?->id,
                'effective_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    protected function snapshotEntitlement(
        OrganizationEntitlement $entitlement,
        BillingCatalogVersion $catalog
    ): OrganizationEntitlementVersion {
        return OrganizationEntitlementVersion::firstOrCreate(
            [
                'company_id' => $entitlement->company_id,
                'version' => $entitlement->version,
            ],
            [
                'billing_catalog_version_id' => $catalog->id,
                'plan_key' => $entitlement->plan_key,
                'status' => $entitlement->status,
                'source' => $entitlement->source,
                'stripe_subscription_id' => $entitlement->stripe_subscription_id,
                'stripe_price_id' => $entitlement->stripe_price_id,
                'features' => $entitlement->features,
                'limits' => $entitlement->limits,
                'starts_at' => $entitlement->starts_at,
                'trial_ends_at' => $entitlement->trial_ends_at,
                'grace_ends_at' => $entitlement->grace_ends_at,
                'ends_at' => $entitlement->ends_at,
                'recorded_at' => now(),
            ]
        );
    }

    protected function canonicalJson(array $payload): string
    {
        $normalize = function ($value) use (&$normalize) {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($normalize, $value);
        };

        return json_encode(
            $normalize($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
