<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveyService
{
    public function getOrCreateAssignment(User $user): ?SurveyAssignment
    {
        $survey = $this->defaultSurvey();
        $version = SurveyVersion::where('is_active', true)->orderByDesc('id')->first();
        if (!$survey || !$version) {
            return null;
        }

        $wave = $this->resolveCurrentWave($survey, $version, $user);

        $assignment = SurveyAssignment::firstOrCreate(
            [
                'survey_id' => $survey->id,
                'user_id' => $user->id,
            ],
            [
                'token' => (string) Str::uuid(),
                'status' => 'pending',
                'survey_version_id' => $version->id,
                'survey_wave_id' => $wave?->id,
                'wave_label' => $wave->label ?? $this->currentWaveLabel($version),
            ]
        );

        if (!$assignment->survey_version_id || !$assignment->wave_label || !$assignment->survey_wave_id) {
            $assignment->update([
                'survey_version_id' => $assignment->survey_version_id ?: $version->id,
                'survey_wave_id' => $assignment->survey_wave_id ?: $wave?->id,
                'wave_label' => $assignment->wave_label ?: ($wave->label ?? $this->currentWaveLabel($version)),
            ]);
        }

        return $assignment->fresh();
    }

    public function markPendingAssignmentsForCompany(int $companyId): void
    {
        $survey = $this->defaultSurvey();
        if (!$survey) {
            return;
        }

        $users = User::where('company_id', $companyId)->get();
        foreach ($users as $user) {
            $this->getOrCreateAssignment($user);
        }
    }

    public function recordResponse(SurveyAssignment $assignment, array $answers, array $context = []): SurveyResponse
    {
        return DB::transaction(function () use ($assignment, $answers, $context) {
            $assignment->loadMissing('surveyVersion.pages.sections.items', 'surveyVersion.pages.items');

            $version = $assignment->surveyVersion;
            if (!$version) {
                $version = SurveyVersion::where('is_active', true)->orderByDesc('id')->first();
                if (!$version) {
                    throw new \RuntimeException('No active survey version available.');
                }
                $assignment->update(['survey_version_id' => $version->id]);
            }

            $itemsByQid = $this->collectItems($version);

            $assignment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'draft_answers' => null,
                'last_autosaved_at' => null,
            ]);

            $response = SurveyResponse::create([
                'survey_id' => $assignment->survey_id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $assignment->survey_wave_id,
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'wave_label' => $assignment->wave_label,
                'submitted_at' => now(),
                'duration_ms' => $context['duration_ms'] ?? null,
            ]);

            foreach ($answers as $qid => $value) {
                $item = $itemsByQid->get($qid);
                if (!$item) {
                    continue;
                }

                SurveyAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $item->id,
                    'survey_item_id' => $item->id,
                    'question_key' => $qid,
                    'value' => $this->serializeValue($value),
                    'value_numeric' => is_numeric($value) ? (float) $value : null,
                    'metadata' => [
                        'attribute_label' => $item->metadata['attribute_label'] ?? $item->page?->attribute_label,
                        'coding_hint' => $item->metadata['coding_hint'] ?? null,
                        'type' => $item->type,
                        'page_id' => $item->survey_page_id,
                        'section_id' => $item->survey_section_id,
                    ],
                ]);
            }

            return $response;
        });
    }

    public function assignmentLink(User $user): ?string
    {
        $assignment = $this->getOrCreateAssignment($user);
        if (!$assignment) {
            return null;
        }

        return route('survey.take', ['token' => $assignment->token]);
    }

    protected function defaultSurvey(): ?Survey
    {
        return Survey::with('questions')->where('is_default', true)->first();
    }

    protected function collectItems(SurveyVersion $version): Collection
    {
        return $version->pages
            ->sortBy('sort_order')
            ->flatMap(function ($page) {
                $sectionItems = $page->sections->sortBy('sort_order')->flatMap(function ($section) use ($page) {
                    return $section->items->map(function ($item) use ($page, $section) {
                        $item->setRelation('page', $page);
                        $item->setRelation('section', $section);
                        return $item;
                    });
                });

                $pageItems = $page->items->map(function ($item) use ($page) {
                    $item->setRelation('page', $page);
                    return $item;
                });

                return $sectionItems->concat($pageItems);
            })
            ->keyBy('qid');
    }

    protected function serializeValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    protected function currentWaveLabel(?SurveyVersion $version = null): string
    {
        $suffix = $version ? $version->version : null;
        $datePart = now()->format('Y-m');
        return $suffix ? "{$suffix}-{$datePart}" : "wave-{$datePart}";
    }

    protected function resolveCurrentWave(?Survey $survey, ?SurveyVersion $version, User $user): ?SurveyWave
    {
        if (!$survey || !$version || !$user->company_id) {
            return null;
        }

        $label = $this->currentWaveLabel($version);

        return SurveyWave::firstOrCreate(
            [
                'company_id' => $user->company_id,
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'label' => $label,
            ],
            [
                'kind' => 'full',
                'opens_at' => now(),
                'due_at' => now()->copy()->addMonth(),
            ]
        );
    }
}
