<?php

namespace App\Services;

use App\Models\SurveyResponse;
use App\Models\SurveyWaveAudienceMember;
use Illuminate\Support\Collection;

class AnalyticsSamplePolicyService
{
    public function assess(int $companyId, Collection $responses, array $filters = []): array
    {
        $isSubgroup = ! empty($filters['department'])
            || ! empty($filters['team'])
            || ! empty($filters['organization_unit_id'])
            || ! empty($filters['reports_to_membership_id']);
        $minimumN = (int) config(
            $isSubgroup
                ? 'privacy.reporting.minimum_subgroup_n'
                : 'privacy.reporting.minimum_company_n',
            $isSubgroup ? 7 : 5
        );
        $submittedN = $responses->count();
        $validN = $responses->filter(function (SurveyResponse $response): bool {
            $loadedAnswers = $response->relationLoaded('answers')
                ? $response->getRelation('answers')
                : null;

            return $loadedAnswers instanceof Collection
                ? $loadedAnswers->isNotEmpty()
                : $response->answers()->exists();
        })->count();
        $invitedN = $this->invitedCount($companyId, $responses, $filters);
        $completionRate = $invitedN > 0 ? round($submittedN / $invitedN, 4) : null;
        $minimumCompletionRate = (float) config('privacy.reporting.minimum_completion_rate', 0.40);

        if ($validN === 0) {
            $status = 'collecting';
            $reason = 'No valid completed responses are available for this cohort.';
        } elseif ($validN < $minimumN) {
            $status = 'suppressed';
            $reason = "Results require at least {$minimumN} valid respondents.";
        } elseif ($completionRate !== null && $completionRate < $minimumCompletionRate) {
            $status = 'insufficient_completion';
            $reason = 'The cohort has not reached the minimum completion rate.';
        } else {
            $status = 'eligible';
            $reason = null;
        }

        return [
            'status' => $status,
            'reason' => $reason,
            'invited_n' => $invitedN,
            'submitted_n' => $submittedN,
            'valid_n' => $validN,
            'minimum_n' => $minimumN,
            'completion_rate' => $completionRate,
            'minimum_completion_rate' => $minimumCompletionRate,
            'is_subgroup' => $isSubgroup,
            'complementary_suppression' => (bool) config('privacy.reporting.complementary_suppression', true),
        ];
    }

    public function visibleGroups(array $groups): array
    {
        $minimumN = (int) config('privacy.reporting.minimum_subgroup_n', 7);
        $visible = [];
        $suppressed = [];

        foreach ($groups as $key => $group) {
            if ($group->count() >= $minimumN) {
                $visible[$key] = $group;
            } else {
                $suppressed[$key] = $group->count();
            }
        }

        // If exactly one group is hidden, the total and visible groups could
        // reveal it by subtraction. Hide the smallest remaining group too.
        if (config('privacy.reporting.complementary_suppression', true)
            && count($suppressed) === 1
            && count($visible) >= 1) {
            $smallestKey = collect($visible)
                ->sortBy(fn (Collection $group) => $group->count())
                ->keys()
                ->first();
            $suppressed[$smallestKey] = $visible[$smallestKey]->count();
            unset($visible[$smallestKey]);
        }

        return [
            'visible' => $visible,
            'suppressed' => $suppressed,
            'minimum_n' => $minimumN,
        ];
    }

    protected function invitedCount(int $companyId, Collection $responses, array $filters): int
    {
        $cycleIds = $responses->pluck('survey_wave_cycle_id')->filter()->unique()->values();
        if ($cycleIds->isEmpty()) {
            return max(
                $responses->count(),
                (int) \DB::table('survey_assignments as sa')
                    ->join('users as u', 'u.id', '=', 'sa.user_id')
                    ->where('u.company_id', $companyId)
                    ->when(! empty($filters['wave']), function ($query) use ($responses) {
                        $waveIds = $responses->pluck('survey_wave_id')->filter()->unique();
                        if ($waveIds->isNotEmpty()) {
                            $query->whereIn('sa.survey_wave_id', $waveIds);
                        }
                    })
                    ->distinct('sa.user_id')
                    ->count('sa.user_id')
            );
        }

        $members = SurveyWaveAudienceMember::whereIn('survey_wave_cycle_id', $cycleIds)->get();
        if (! empty($filters['department'])) {
            $members = $members->filter(
                fn ($member) => (string) ($member->snapshot['department'] ?? '') === (string) $filters['department']
            );
        }
        if (! empty($filters['team'])) {
            $members = $members->filter(
                fn ($member) => (string) ($member->snapshot['team'] ?? '') === (string) $filters['team']
            );
        }
        if (! empty($filters['organization_unit_id'])) {
            $members = $members->filter(
                fn ($member) => (int) ($member->snapshot['organization_unit_id'] ?? 0)
                    === (int) $filters['organization_unit_id']
            );
        }
        if (! empty($filters['reports_to_membership_id'])) {
            $members = $members->filter(
                fn ($member) => (int) ($member->snapshot['reports_to_membership_id'] ?? 0)
                    === (int) $filters['reports_to_membership_id']
            );
        }

        return $members->unique(fn ($member) => "{$member->survey_wave_cycle_id}:{$member->user_id}")->count();
    }
}
