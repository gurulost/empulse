<?php

namespace App\Services;

use App\Models\AdvisorCompanyGrant;
use App\Models\User;
use Illuminate\Support\Collection;

class AdvisorAccessService
{
    public function grant(
        int $companyId,
        User $advisor,
        User $customerActor,
        string $purpose,
        ?\DateTimeInterface $validUntil = null
    ): AdvisorCompanyGrant {
        $this->assertCustomerAdministrator($companyId, $customerActor);
        if (! $advisor->hasCapability('actions.advisor') || $advisor->status !== 'active') {
            throw new \DomainException('Advisor access can only be granted to an active WorkFit advisor.');
        }
        if ($validUntil && $validUntil <= now()) {
            throw new \DomainException('Advisor access expiry must be in the future.');
        }

        $grant = AdvisorCompanyGrant::updateOrCreate(
            [
                'company_id' => $companyId,
                'advisor_user_id' => $advisor->id,
            ],
            [
                'approved_by_user_id' => $customerActor->id,
                'status' => 'active',
                'purpose' => $purpose,
                'valid_from' => now(),
                'valid_until' => $validUntil,
                'revoked_at' => null,
                'revoked_by_user_id' => null,
            ]
        );

        app(AuditTrailService::class)->record(
            'advisor_access.granted',
            $customerActor,
            $companyId,
            $grant::class,
            $grant->id,
            [],
            ['advisor_user_id' => $advisor->id, 'valid_until' => $validUntil?->format(DATE_ATOM)]
        );

        return $grant;
    }

    public function revoke(AdvisorCompanyGrant $grant, User $customerActor): AdvisorCompanyGrant
    {
        $this->assertCustomerAdministrator((int) $grant->company_id, $customerActor);
        $grant->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by_user_id' => $customerActor->id,
        ]);

        app(AuditTrailService::class)->record(
            'advisor_access.revoked',
            $customerActor,
            (int) $grant->company_id,
            $grant::class,
            $grant->id,
            [],
            ['advisor_user_id' => $grant->advisor_user_id]
        );

        return $grant;
    }

    public function canAdvise(User $advisor, int $companyId): bool
    {
        if (! $advisor->hasCapability('actions.advisor')) {
            return false;
        }

        return AdvisorCompanyGrant::query()
            ->where('company_id', $companyId)
            ->where('advisor_user_id', $advisor->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where('valid_from', '<=', now())
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->exists();
    }

    public function activeGrantForAdvisor(User $advisor, int $companyId): ?AdvisorCompanyGrant
    {
        if (! $advisor->hasCapability('actions.advisor')) {
            return null;
        }

        return AdvisorCompanyGrant::query()
            ->where('company_id', $companyId)
            ->where('advisor_user_id', $advisor->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where('valid_from', '<=', now())
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->first();
    }

    public function activeForAdvisor(User $advisor): Collection
    {
        return AdvisorCompanyGrant::query()
            ->with('advisor')
            ->where('advisor_user_id', $advisor->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where('valid_from', '<=', now())
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->orderBy('company_id')
            ->get();
    }

    public function assertActorCanAccess(User $actor, int $companyId): void
    {
        if ((int) $actor->company_id === $companyId) {
            return;
        }
        if ($this->canAdvise($actor, $companyId)) {
            return;
        }

        throw new \DomainException('Customer-approved advisor access is required for this organization.');
    }

    public function companyIdForActor(User $actor, ?int $requestedCompanyId = null): int
    {
        $companyId = (int) $actor->company_id;
        if ($actor->hasCapability('actions.advisor')) {
            $companyId = $requestedCompanyId
                ?: (int) $this->activeForAdvisor($actor)->first()?->company_id;
        }
        if ($companyId <= 0) {
            throw new \DomainException('Company context is required.');
        }

        $this->assertActorCanAccess($actor, $companyId);

        return $companyId;
    }

    protected function assertCustomerAdministrator(int $companyId, User $actor): void
    {
        if ((int) $actor->company_id !== $companyId || ! $actor->hasCapability('advisor-access.manage')) {
            throw new \DomainException('Only an authorized customer administrator can manage advisor access.');
        }
    }
}
