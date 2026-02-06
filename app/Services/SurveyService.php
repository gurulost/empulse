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
    public function getOrCreateAssignment(User $user, ?SurveyWave $wave = null): ?SurveyAssignment
    {
        if ($wave) {
            $wave->loadMissing('survey', 'surveyVersion');
        }

        $survey = $wave?->survey
            ?: ($wave?->survey_id ? Survey::find($wave->survey_id) : null)
            ?: $this->defaultSurvey();

        $version = $wave?->surveyVersion
            ?: ($wave?->survey_version_id ? SurveyVersion::find($wave->survey_version_id) : null)
            ?: SurveyVersion::where('is_active', true)->orderByDesc('id')->first();

        if (!$survey || !$version) {
            return null;
        }

        $wave = $wave ?: $this->resolveCurrentWave($survey, $version, $user);

        if ($wave) {
            $assignment = SurveyAssignment::firstOrCreate(
                [
                    'survey_id' => $survey->id,
                    'user_id' => $user->id,
                    'survey_wave_id' => $wave->id,
                ],
                [
                    'token' => (string) Str::uuid(),
                    'status' => 'pending',
                    'survey_version_id' => $version->id,
                    'wave_label' => $wave->label ?? $this->currentWaveLabel($version),
                    'due_at' => $wave->due_at,
                ]
            );
        } else {
            $assignment = SurveyAssignment::query()
                ->where('survey_id', $survey->id)
                ->where('user_id', $user->id)
                ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
                ->orderByDesc('id')
                ->first();

            if (!$assignment) {
                $assignment = SurveyAssignment::create([
                    'survey_id' => $survey->id,
                    'user_id' => $user->id,
                    'token' => (string) Str::uuid(),
                    'status' => 'pending',
                    'survey_version_id' => $version->id,
                    'wave_label' => $this->currentWaveLabel($version),
                ]);
            }
        }

        $updates = [];
        if (!$assignment->survey_version_id) {
            $updates['survey_version_id'] = $version->id;
        }
        if (!$assignment->wave_label) {
            $updates['wave_label'] = $wave?->label ?? $this->currentWaveLabel($version);
        }
        if ($wave && (int) ($assignment->survey_wave_id ?? 0) !== (int) $wave->id) {
            $updates['survey_wave_id'] = $wave->id;
        }
        if ($wave && !$assignment->due_at && $wave->due_at) {
            $updates['due_at'] = $wave->due_at;
        }

        if (!empty($updates)) {
            $assignment->update($updates);
        }

        return $assignment->fresh();
    }

    public function getOrCreateAssignmentForWave(User $user, SurveyWave $wave): ?SurveyAssignment
    {
        $wave->loadMissing('survey', 'surveyVersion');

        $survey = $wave->survey
            ?: ($wave->survey_id ? Survey::find($wave->survey_id) : null)
            ?: $this->defaultSurvey();

        $version = $wave->surveyVersion
            ?: ($wave->survey_version_id ? SurveyVersion::find($wave->survey_version_id) : null)
            ?: SurveyVersion::where('is_active', true)->orderByDesc('id')->first();

        if (!$survey || !$version) {
            return null;
        }

        $assignment = SurveyAssignment::query()
            ->where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->where('survey_wave_id', $wave->id)
            ->where('status', '!=', 'completed')
            ->orderByDesc('id')
            ->first();

        if (!$assignment) {
            $assignment = SurveyAssignment::create([
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $wave->id,
                'user_id' => $user->id,
                'token' => (string) Str::uuid(),
                'status' => 'pending',
                'wave_label' => $wave->label ?: $this->currentWaveLabel($version),
                'due_at' => $wave->due_at,
            ]);
        } else {
            $assignment->fill([
                'survey_version_id' => $assignment->survey_version_id ?: $version->id,
                'survey_wave_id' => $assignment->survey_wave_id ?: $wave->id,
                'wave_label' => $assignment->wave_label ?: ($wave->label ?: $this->currentWaveLabel($version)),
                'due_at' => $assignment->due_at ?: $wave->due_at,
            ]);

            if ($assignment->isDirty()) {
                $assignment->save();
            }
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
        $assignment = SurveyAssignment::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();

        if (!$assignment) {
            $assignment = $this->getOrCreateAssignment($user);
        }

        if (!$assignment) {
            return null;
        }

        return route('survey.take', ['token' => $assignment->token]);
    }

    public function defaultSurvey(): ?Survey
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
    public function cloneVersion(SurveyVersion $source): SurveyVersion
    {
        return DB::transaction(function () use ($source) {
            $newVersion = $source->replicate();
            $newVersion->version = $this->incrementVersion($source->version);
            $newVersion->is_active = false;
            $newVersion->created_utc = now();
            $newVersion->push();

            foreach ($source->pages as $page) {
                $newPage = $page->replicate();
                $newPage->survey_version_id = $newVersion->id;
                $newPage->push();

                foreach ($page->items as $item) {
                    $this->cloneItem($item, $newVersion->id, $newPage->id, null);
                }

                foreach ($page->sections as $section) {
                    $newSection = $section->replicate();
                    $newSection->survey_page_id = $newPage->id;
                    $newSection->push();

                    foreach ($section->items as $item) {
                        $this->cloneItem($item, $newVersion->id, $newPage->id, $newSection->id);
                    }
                }
            }

            return $newVersion;
        });
    }

    protected function cloneItem($sourceItem, $versionId, $pageId, $sectionId)
    {
        $newItem = $sourceItem->replicate();
        $newItem->survey_version_id = $versionId;
        $newItem->survey_page_id = $pageId;
        $newItem->survey_section_id = $sectionId;
        $newItem->push();

        foreach ($sourceItem->options as $option) {
            $newOption = $option->replicate();
            $newOption->survey_item_id = $newItem->id;
            $newOption->push();
        }
    }

    public function publishVersion(SurveyVersion $version): void
    {
        DB::transaction(function () use ($version) {
            // Deactivate all other versions of this instrument
            SurveyVersion::where('instrument_id', $version->instrument_id)
                ->where('id', '!=', $version->id)
                ->update(['is_active' => false]);

            $version->update(['is_active' => true]);
        });
    }

    protected function incrementVersion($versionStr)
    {
        $parts = explode('.', $versionStr);
        if (count($parts) >= 3) {
            $parts[2] = (int)$parts[2] + 1;
        } else {
            $parts[] = 1;
        }
        return implode('.', $parts);
    }
}
