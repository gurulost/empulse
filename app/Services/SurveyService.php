<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveAudienceMember;
use App\Models\SurveyWaveCycle;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveyService
{
    public function __construct(
        protected OnboardingTelemetryService $telemetry,
        protected SurveyVersionIntegrityService $versionIntegrity,
        protected OrganizationEntitlementService $entitlements
    ) {}

    public function getOrCreateAssignment(User $user, ?SurveyWave $wave = null): ?SurveyAssignment
    {
        if (! $wave) {
            return SurveyAssignment::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->whereNotNull('survey_version_id')
                ->orderByDesc('id')
                ->first();
        }

        return $this->getOrCreateAssignmentForWave($user, $wave);
    }

    public function getOrCreateAssignmentForWave(
        User $user,
        SurveyWave $wave,
        ?SurveyWaveCycle $cycle = null,
        ?SurveyWaveAudienceMember $audienceMember = null
    ): ?SurveyAssignment {
        $wave->loadMissing('survey', 'surveyVersion');

        $survey = $wave->survey;
        $version = $wave->surveyVersion;

        if (! $survey
            || ! $version
            || ($survey->instrument_id && $survey->instrument_id !== $version->instrument_id)) {
            return null;
        }

        $assignment = SurveyAssignment::query()
            ->where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->where('survey_wave_id', $wave->id)
            ->when(
                $cycle,
                fn ($query) => $query->where('survey_wave_cycle_id', $cycle->id)
            )
            ->where('status', '!=', 'completed')
            ->orderByDesc('id')
            ->first();

        if (! $assignment) {
            $assignment = SurveyAssignment::create([
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $wave->id,
                'survey_wave_cycle_id' => $cycle?->id,
                'survey_wave_audience_member_id' => $audienceMember?->id,
                'user_id' => $user->id,
                'token' => (string) Str::uuid(),
                'status' => 'pending',
                'wave_label' => $wave->label ?: $this->currentWaveLabel($version),
                'due_at' => $wave->due_at,
                'cohort_snapshot' => $audienceMember?->snapshot,
            ]);
        } else {
            $assignment->fill([
                'survey_version_id' => $assignment->survey_version_id ?: $version->id,
                'survey_wave_id' => $assignment->survey_wave_id ?: $wave->id,
                'survey_wave_cycle_id' => $assignment->survey_wave_cycle_id ?: $cycle?->id,
                'survey_wave_audience_member_id' => $assignment->survey_wave_audience_member_id ?: $audienceMember?->id,
                'wave_label' => $assignment->wave_label ?: ($wave->label ?: $this->currentWaveLabel($version)),
                'due_at' => $assignment->due_at ?: $wave->due_at,
                'cohort_snapshot' => $assignment->cohort_snapshot ?: $audienceMember?->snapshot,
            ]);

            if ($assignment->isDirty()) {
                $assignment->save();
            }
        }

        return $assignment->fresh();
    }

    public function markPendingAssignmentsForCompany(int $companyId): void
    {
        // Roster maintenance must not manufacture measurement assignments.
    }

    public function recordResponse(SurveyAssignment $assignment, array $answers, array $context = []): SurveyResponse
    {
        return DB::transaction(function () use ($assignment, $answers, $context) {
            $assignment = SurveyAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($assignment->status !== 'pending' || $assignment->response()->exists()) {
                throw new \DomainException('This survey has already been completed.');
            }

            $assignment->loadMissing('surveyVersion.pages.sections.items', 'surveyVersion.pages.items');

            $version = $assignment->surveyVersion;
            if (! $version) {
                throw new \DomainException('Assignment is not pinned to a survey version.');
            }

            $itemsByQid = $this->collectItems($version);
            $cycle = $assignment->survey_wave_cycle_id
                ? SurveyWaveCycle::find($assignment->survey_wave_cycle_id)
                : null;

            $response = SurveyResponse::create([
                'survey_id' => $assignment->survey_id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $assignment->survey_wave_id,
                'survey_wave_cycle_id' => $assignment->survey_wave_cycle_id,
                'survey_wave_audience_member_id' => $assignment->survey_wave_audience_member_id,
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'wave_label' => $assignment->wave_label,
                'submitted_at' => now(),
                'duration_ms' => $context['duration_ms'] ?? null,
                'cohort_snapshot' => $assignment->cohort_snapshot,
                'privacy_policy_version' => $assignment->privacy_policy_version,
                'metric_registry_version_id' => $cycle?->metric_registry_version_id,
                'metric_definition_hash' => $cycle?->metric_definition_hash,
            ]);

            foreach ($answers as $qid => $value) {
                $item = $itemsByQid->get($qid);
                if (! $item) {
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

            $assignment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'draft_answers' => null,
                'last_autosaved_at' => null,
                'token_revoked_at' => now(),
            ]);

            if ($assignment->survey_wave_id) {
                $wave = SurveyWave::find($assignment->survey_wave_id);
                if ($wave
                    && $wave->kind === 'full'
                    && ! $wave->assignments()->where('status', '!=', 'completed')->exists()) {
                    $wave->update(['status' => 'completed']);
                }
            }

            $response->loadMissing('user');
            if ($response->user?->company_id) {
                $this->entitlements->recordUsage(
                    (int) $response->user->company_id,
                    "survey_response:{$response->id}",
                    'completed_responses',
                    1,
                    'response',
                    [
                        'survey_wave_id' => $response->survey_wave_id,
                        'survey_wave_cycle_id' => $response->survey_wave_cycle_id,
                    ]
                );
            }
            $this->telemetry->recordFirstResponseCompleted($response);

            return $response;
        });
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

    public function cloneVersion(SurveyVersion $source): SurveyVersion
    {
        return DB::transaction(function () use ($source) {
            $source->loadMissing($this->versionIntegrity->relations());
            $sourceHash = $this->versionIntegrity->semanticHash($source);
            $newVersion = $source->replicate();
            $newVersion->version = $this->incrementVersion($source->version);
            $newVersion->is_active = false;
            $newVersion->content_hash = null;
            $newVersion->published_at = null;
            $newVersion->published_by = null;
            $newVersion->created_utc = now();
            $newVersion->push();

            foreach ($source->scalePresets as $preset) {
                $newPreset = $preset->replicate();
                $newPreset->survey_version_id = $newVersion->id;
                $newPreset->push();
            }

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

            $newVersion->load($this->versionIntegrity->relations());
            if (! hash_equals($sourceHash, $this->versionIntegrity->semanticHash($newVersion))) {
                throw new \DomainException('Cloned survey version is not semantically identical.');
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

        if ($sourceItem->optionSource) {
            $newSource = $sourceItem->optionSource->replicate();
            $newSource->survey_item_id = $newItem->id;
            $newSource->push();
        }
    }

    public function publishVersion(SurveyVersion $version): void
    {
        $contentHash = $this->versionIntegrity->assertPublishable($version);

        DB::transaction(function () use ($version, $contentHash) {
            SurveyVersion::where('id', '!=', $version->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $version->update([
                'is_active' => true,
                'content_hash' => $contentHash,
                'published_at' => now(),
                'published_by' => auth()->id(),
            ]);
        });
    }

    protected function incrementVersion($versionStr)
    {
        $parts = explode('.', $versionStr);
        if (count($parts) >= 3) {
            $parts[2] = (int) $parts[2] + 1;
        } else {
            $parts[] = 1;
        }

        return implode('.', $parts);
    }
}
