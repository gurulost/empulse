<?php

namespace App\Support;

use App\Models\Companies;
use App\Models\User;
use App\Services\OrganizationEntitlementService;

class CompanyBilling
{
    public static function manager(int $companyId): ?User
    {
        return User::where('company_id', $companyId)
            ->where('role', 1)
            ->first();
    }

    public static function status(User|Companies|int|null $subject): string
    {
        $companyId = self::companyId($subject);
        if (! $companyId) {
            return 'none';
        }

        $entitlement = app(OrganizationEntitlementService::class)->current($companyId);

        return $entitlement ? $entitlement->status : 'none';
    }

    public static function statusForCompany(int $companyId): string
    {
        return self::status(self::manager($companyId));
    }

    public static function allowsScheduling(User|Companies|int|null $subject): bool
    {
        $companyId = self::companyId($subject);

        return $companyId
            ? app(OrganizationEntitlementService::class)->canDispatch($companyId)
            : false;
    }

    public static function hasFeature(User|Companies|int|null $subject, string $feature): bool
    {
        $companyId = self::companyId($subject);

        return $companyId
            ? app(OrganizationEntitlementService::class)->hasFeature($companyId, $feature)
            : false;
    }

    public static function planKey(User|Companies|int|null $subject): string
    {
        $companyId = self::companyId($subject);

        if (! $companyId) {
            return 'none';
        }
        $entitlement = app(OrganizationEntitlementService::class)->current($companyId);

        return $entitlement ? $entitlement->plan_key : 'none';
    }

    protected static function companyId(User|Companies|int|null $subject): ?int
    {
        return match (true) {
            $subject instanceof User => $subject->company_id ? (int) $subject->company_id : null,
            $subject instanceof Companies => (int) $subject->id,
            is_int($subject) => $subject,
            default => null,
        };
    }
}
