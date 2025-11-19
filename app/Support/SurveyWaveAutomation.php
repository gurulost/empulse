<?php

namespace App\Support;

use Carbon\CarbonInterface;

class SurveyWaveAutomation
{
    public static function cadenceThreshold(?string $cadence): ?CarbonInterface
    {
        $windows = config('survey.automation.cadence_windows', []);
        $minutes = $windows[$cadence] ?? null;

        if (!$minutes) {
            return null;
        }

        return now()->subMinutes((int) $minutes);
    }

    public static function dripEnabledForTariff(?int $tariff): bool
    {
        if ($tariff === null) {
            return false;
        }

        $tariffs = config('survey.automation.drip_tariffs', [1]);
        return in_array((int) $tariff, array_map('intval', $tariffs), true);
    }

    public static function allowedBillingStatuses(): array
    {
        return config('survey.automation.billing_statuses', ['active', 'trialing']);
    }

    public static function billingStatusAllows(?string $status): bool
    {
        if (!$status) {
            return false;
        }

        return in_array($status, self::allowedBillingStatuses(), true);
    }

    public static function manualIsOneShot(): bool
    {
        return (bool) config('survey.automation.manual_one_shot', true);
    }

    public static function planLabel(?int $tariff): string
    {
        $labels = config('survey.automation.tariff_labels', []);

        if ($tariff === null) {
            return $labels['default'] ?? 'Unknown plan';
        }

        return $labels[$tariff] ?? ($labels['default'] ?? sprintf('Plan %s', $tariff));
    }

    public static function billingStatusLabel(?string $status): string
    {
        $labels = config('survey.automation.billing_labels', []);

        if (!$status) {
            return $labels['none'] ?? 'Not subscribed';
        }

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}
