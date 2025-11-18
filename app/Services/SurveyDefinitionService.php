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
        protected SurveyOptionSourceResolver $optionSourceResolver
    ) {
    }

    public function definitionForAssignment(SurveyAssignment $assignment): array
    {
        $assignment->loadMissing('user');
        $version = $this->resolveVersion($assignment);

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
                'token' => $assignment->token,
                'status' => $assignment->status,
                'draft_answers' => $assignment->draft_answers ?? new \stdClass(),
                'due_at' => optional($assignment->due_at)->toIso8601String(),
                'completed_at' => optional($assignment->completed_at)->toIso8601String(),
                'user' => [
                    'id' => $assignment->user?->id,
                    'name' => $assignment->user?->name,
                    'email' => $assignment->user?->email,
                ],
            ],
            'version' => [
                'id' => $version->id,
                'instrument_id' => $version->instrument_id,
                'title' => $version->title,
                'version' => $version->version,
                'created_utc' => optional($version->created_utc)->toDateString(),
                'meta' => $version->meta ?? new \stdClass(),
            ],
            'pages' => $this->serializePages($version, $scalePresets),
        ];
    }

    protected function resolveVersion(SurveyAssignment $assignment): SurveyVersion
    {
        if ($assignment->survey_version_id) {
            return $assignment->surveyVersion()->firstOrFail();
        }

        $version = SurveyVersion::where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (!$version) {
            abort(500, 'No active survey version configured.');
        }

        $assignment->survey_version_id = $version->id;
        $assignment->save();

        return $version;
    }

    protected function serializePages(SurveyVersion $version, $scalePresets): array
    {
        return $version->pages
            ->sortBy('sort_order')
            ->map(function (SurveyPage $page) use ($scalePresets) {
                return [
                    'page_id' => $page->page_id,
                    'title' => $page->title,
                    'attribute_label' => $page->attribute_label,
                    'meta' => $page->meta ?? new \stdClass(),
                    'sections' => $page->sections->sortBy('sort_order')->map(function (SurveySection $section) use ($scalePresets) {
                        return [
                            'section_id' => $section->section_id,
                            'title' => $section->title,
                            'meta' => $section->meta ?? new \stdClass(),
                            'items' => $section->items->sortBy('sort_order')->map(function (SurveyItem $item) use ($scalePresets) {
                                return $this->serializeItem($item, $scalePresets);
                            })->values(),
                        ];
                    })->values(),
                    'items' => $page->items->sortBy('sort_order')->map(function (SurveyItem $item) use ($scalePresets) {
                        return $this->serializeItem($item, $scalePresets);
                    })->values(),
                ];
            })
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
                'meta' => $option->meta ?? new \stdClass(),
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
            'response' => $item->response_config ?? new \stdClass(),
            'display_logic' => $item->display_logic ?? [],
            'metadata' => $item->metadata ?? new \stdClass(),
            'options' => $options,
            'option_source' => $sourceMeta,
            'sort_order' => $item->sort_order,
        ];
    }

    protected function resolveScale(?array $scaleConfig, $scalePresets): ?array
    {
        if (!$scaleConfig) {
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
}
