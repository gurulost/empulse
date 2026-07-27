<?php

namespace App\Services;

use App\Models\SurveyAssignment;
use App\Models\SurveyItem;
use App\Models\SurveyPage;
use App\Models\SurveySection;
use App\Models\SurveyVersion;
use Illuminate\Support\Arr;

class SurveyDefinitionService
{
    public function __construct(
        protected SurveyOptionSourceResolver $optionSourceResolver,
        protected PrivacyGovernanceService $privacy
    ) {}

    public function definitionForAssignment(SurveyAssignment $assignment): array
    {
        $version = $this->resolveVersion($assignment);
        $assignment->loadMissing('surveyWave.pulseVariant');
        $pulseVariant = $assignment->surveyWave?->pulseVariant;
        $allowedQids = $pulseVariant?->question_ids;
        $surveyMeta = $allowedQids
            ? [
                'question_count' => count($allowedQids),
                'estimated_minutes' => $pulseVariant->estimated_minutes,
                'variant' => [
                    'key' => $pulseVariant->variant_key,
                    'version' => $pulseVariant->version,
                    'purpose' => $pulseVariant->purpose,
                    'metric_id' => $pulseVariant->metric_id,
                    'claims_limit' => $pulseVariant->claims_limit,
                ],
            ]
            : $this->surveyMetaForVersion($version);

        $version->loadMissing([
            'scalePresets',
            'pages.sections.items.options',
            'pages.sections.items.optionSource',
            'pages.items.options',
            'pages.items.optionSource',
        ]);

        $scalePresets = $version->scalePresets->keyBy('preset_key');

        return [
            'assignment' => [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'draft_answers' => $assignment->draft_answers ?? new \stdClass,
                'draft_revision' => (int) $assignment->draft_revision,
                'due_at' => optional($assignment->due_at)->toIso8601String(),
                'completed_at' => optional($assignment->completed_at)->toIso8601String(),
                'privacy_policy_version' => $assignment->privacy_policy_version,
                'privacy_acknowledged_at' => optional($assignment->privacy_acknowledged_at)->toIso8601String(),
            ],
            'version' => [
                'id' => $version->id,
                'instrument_id' => $version->instrument_id,
                'title' => $version->title,
                'version' => $version->version,
                'created_utc' => optional($version->created_utc)->toDateString(),
                'meta' => $version->meta ?? new \stdClass,
            ],
            'survey_meta' => $surveyMeta,
            'privacy' => $this->privacy->policyPayload(),
            'pages' => $this->serializePages($version, $scalePresets, $allowedQids),
        ];
    }

    public function surveyMetaForAssignment(SurveyAssignment $assignment): array
    {
        $version = $this->resolveVersion($assignment);

        return $this->surveyMetaForVersion($version);
    }

    protected function resolveVersion(SurveyAssignment $assignment): SurveyVersion
    {
        if (! $assignment->survey_version_id) {
            abort(409, 'Assignment is not pinned to a survey version.');
        }

        return $assignment->surveyVersion()->firstOrFail();
    }

    protected function serializePages(SurveyVersion $version, $scalePresets, ?array $allowedQids = null): array
    {
        $allowed = $allowedQids === null ? null : array_fill_keys($allowedQids, true);

        return $version->pages
            ->sortBy('sort_order')
            ->map(function (SurveyPage $page) use ($scalePresets, $allowed) {
                $sections = $page->sections->sortBy('sort_order')->map(function (SurveySection $section) use ($scalePresets, $allowed) {
                    $items = $section->items->sortBy('sort_order')
                        ->filter(fn (SurveyItem $item) => $allowed === null || isset($allowed[$item->qid]))
                        ->map(fn (SurveyItem $item) => $this->serializeItem($item, $scalePresets))
                        ->values();

                    return [
                        'section_id' => $section->section_id,
                        'title' => $section->title,
                        'meta' => $section->meta ?? new \stdClass,
                        'items' => $items,
                    ];
                })->filter(fn ($section) => count($section['items']) > 0)->values();
                $items = $page->items->sortBy('sort_order')
                    ->filter(fn (SurveyItem $item) => $allowed === null || isset($allowed[$item->qid]))
                    ->map(fn (SurveyItem $item) => $this->serializeItem($item, $scalePresets))
                    ->values();

                return [
                    'page_id' => $page->page_id,
                    'title' => $page->title,
                    'attribute_label' => $page->attribute_label,
                    'meta' => $page->meta ?? new \stdClass,
                    'sections' => $sections,
                    'items' => $items,
                ];
            })
            ->filter(fn ($page) => count($page['items']) > 0 || count($page['sections']) > 0)
            ->values()
            ->all();
    }

    protected function serializeItem(SurveyItem $item, $scalePresets): array
    {
        $options = $item->options
            ->sortBy('sort_order')
            ->map(fn ($option) => [
                'value' => $option->value,
                'label' => $option->label,
                'exclusive' => (bool) $option->exclusive,
                'meta' => $option->meta ?? new \stdClass,
            ])->values()->all();

        $sourceMeta = null;
        if ($item->optionSource) {
            $resolved = $this->optionSourceResolver->resolve($item->optionSource);
            $options = array_merge($options, $resolved['options']);
            $sourceMeta = $resolved['meta'];
        }

        return [
            'qid' => $item->qid,
            'type' => $item->type,
            'question' => $item->question,
            'scale' => $this->resolveScale($item->scale_config, $scalePresets),
            'response' => $item->response_config ?? new \stdClass,
            'display_logic' => $item->display_logic ?? [],
            'metadata' => $item->metadata ?? new \stdClass,
            'options' => $options,
            'option_source' => $sourceMeta,
            'sort_order' => $item->sort_order,
        ];
    }

    protected function resolveScale(?array $scaleConfig, $scalePresets): ?array
    {
        if (! $scaleConfig) {
            return null;
        }

        if (isset($scaleConfig['preset_key'])) {
            $presetKey = $scaleConfig['preset_key'];
            $preset = $scalePresets[$presetKey] ?? null;
            $base = $preset?->config ?? [];
            $overrides = Arr::except($scaleConfig, ['preset_key']);

            return array_merge($base, $overrides);
        }

        return $scaleConfig;
    }

    protected function surveyMetaForVersion(SurveyVersion $version): array
    {
        $version->loadMissing([
            'pages.sections.items',
            'pages.items',
        ]);

        $questionCount = $version->pages->sum(function (SurveyPage $page) {
            return $page->items->count()
                + $page->sections->sum(fn (SurveySection $section) => $section->items->count());
        });

        return [
            'question_count' => (int) $questionCount,
            'estimated_minutes' => max(4, (int) ceil(max(0, $questionCount) / 8)),
        ];
    }
}
