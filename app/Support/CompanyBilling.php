<?php

namespace App\Support;

use App\Models\User;

class CompanyBilling
{
    public static function manager(int $companyId): ?User
    {
        return User::where('company_id', $companyId)
            ->where('role', 1)
            ->first();
    }

    public static function status(?User $manager): string
    {
        if (!$manager) {
            return 'none';
        }

        $subscription = $manager->subscriptions()
            ->orderByDesc('created_at')
            ->first();

        if ($subscription && $subscription->stripe_status) {
            return $subscription->stripe_status;
        }

        if (SurveyWaveAutomation::dripEnabledForTariff((int) $manager->tariff)) {
            return 'manual-premium';
        }

        return 'none';
    }

    public static function statusForCompany(int $companyId): string
    {
        return self::status(self::manager($companyId));
    }

    public static function allowsScheduling(?User $manager): bool
    {
        $status = self::status($manager);

        if ($status === 'manual-premium') {
            return true;
        }

        return SurveyWaveAutomation::billingStatusAllows($status);
    }
}
