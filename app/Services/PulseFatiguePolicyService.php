<?php

namespace App\Services;

use App\Models\PulseVariantVersion;
use App\Models\SurveyAssignment;
use App\Models\SurveyWave;
use App\Models\User;

class PulseFatiguePolicyService
{
    public function eligibility(User $user, SurveyWave $wave): array
    {
        if (! $wave->pulse_variant_version_id) {
            return ['eligible' => true, 'reason' => null, 'policy' => null];
        }
        $variant = PulseVariantVersion::findOrFail($wave->pulse_variant_version_id);
        $recent = SurveyAssignment::query()
            ->where('user_id', $user->id)
            ->whereHas('surveyWave', fn ($query) => $query->whereNotNull('pulse_variant_version_id'))
            ->where('created_at', '>=', now()->subDays(90))
            ->orderByDesc('created_at')
            ->get();
        $last = $recent->first();
        if ($last && $last->created_at->gt(now()->subDays($variant->minimum_days_between_invites))) {
            return [
                'eligible' => false,
                'reason' => 'minimum_rest_period',
                'policy' => $this->snapshot($variant, $recent->count()),
            ];
        }
        if ($recent->count() >= $variant->maximum_pulses_per_90_days) {
            return [
                'eligible' => false,
                'reason' => 'rolling_pulse_limit',
                'policy' => $this->snapshot($variant, $recent->count()),
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'policy' => $this->snapshot($variant, $recent->count()),
        ];
    }

    protected function snapshot(PulseVariantVersion $variant, int $recentCount): array
    {
        return [
            'pulse_variant_version_id' => $variant->id,
            'minimum_days_between_invites' => $variant->minimum_days_between_invites,
            'maximum_pulses_per_90_days' => $variant->maximum_pulses_per_90_days,
            'recent_pulse_count' => $recentCount,
            'evaluated_at' => now()->toIso8601String(),
        ];
    }
}
