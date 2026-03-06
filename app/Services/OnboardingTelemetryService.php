<?php

namespace App\Services;

use App\Models\OnboardingEvent;
use App\Models\SurveyResponse;
use App\Models\SurveyWave;
use App\Models\User;

class OnboardingTelemetryService
{
    public function record(array $attributes, ?User $user = null): OnboardingEvent
    {
        return OnboardingEvent::create([
            'user_id' => $user?->id ?? $attributes['user_id'] ?? null,
            'company_id' => $attributes['company_id'] ?? $user?->company_id,
            'name' => $attributes['name'],
            'context_surface' => $attributes['context_surface'],
            'task_id' => $attributes['task_id'] ?? null,
            'user_segment' => $attributes['user_segment'] ?? null,
            'guidance_level' => $attributes['guidance_level'] ?? null,
            'session_id' => $attributes['session_id'] ?? null,
            'attempt_index' => max(1, (int) ($attributes['attempt_index'] ?? 1)),
            'time_since_session_start_sec' => $attributes['time_since_session_start_sec'] ?? null,
            'properties' => $attributes['properties'] ?? [],
            'created_at' => now(),
        ]);
    }

    public function recordFirstWaveDispatched(SurveyWave $wave, ?User $actor = null): ?OnboardingEvent
    {
        if (!$wave->company_id) {
            return null;
        }

        if (OnboardingEvent::query()
            ->where('company_id', $wave->company_id)
            ->where('name', 'first_wave_dispatched')
            ->exists()) {
            return null;
        }

        return $this->record([
            'company_id' => $wave->company_id,
            'name' => 'first_wave_dispatched',
            'context_surface' => 'survey-waves',
            'task_id' => 'wave_dispatch',
            'user_segment' => $actor && ((int) ($actor->role ?? 0) === 0 || (int) ($actor->is_admin ?? 0) === 1) ? 'expert' : 'novice',
            'guidance_level' => 'none',
            'properties' => [
                'wave_id' => $wave->id,
                'wave_label' => $wave->label,
                'kind' => $wave->kind,
                'cadence' => $wave->cadence,
            ],
        ], $actor);
    }

    public function recordFirstResponseCompleted(SurveyResponse $response): ?OnboardingEvent
    {
        $companyId = (int) ($response->user?->company_id
            ?? User::query()->whereKey($response->user_id)->value('company_id')
            ?? 0);

        if ($companyId <= 0) {
            return null;
        }

        $completedCount = SurveyResponse::query()
            ->from('survey_responses as sr')
            ->join('users as u', function ($join) use ($companyId) {
                $join->on('u.id', '=', 'sr.user_id')
                    ->where('u.company_id', '=', $companyId);
            })
            ->whereNotNull('sr.submitted_at')
            ->count('sr.id');

        if ($completedCount !== 1) {
            return null;
        }

        if (OnboardingEvent::query()
            ->where('company_id', $companyId)
            ->where('name', 'first_response_completed')
            ->exists()) {
            return null;
        }

        return $this->record([
            'company_id' => $companyId,
            'name' => 'first_response_completed',
            'context_surface' => 'survey',
            'task_id' => 'first_response',
            'user_segment' => 'system',
            'guidance_level' => 'none',
            'properties' => [
                'response_id' => $response->id,
                'assignment_id' => $response->assignment_id,
                'survey_wave_id' => $response->survey_wave_id,
                'wave_label' => $response->wave_label,
            ],
        ], $response->user);
    }
}
