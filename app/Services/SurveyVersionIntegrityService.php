<?php

namespace App\Services;

use App\Models\SurveyVersion;
use Illuminate\Support\Collection;

class SurveyVersionIntegrityService
{
    public function __construct(protected MetricRegistryService $metricRegistry) {}

    private const SUPPORTED_TYPES = [
        'slider',
        'text',
        'text_short',
        'text_long',
        'number_integer',
        'dropdown',
        'single_select',
        'single_select_text',
        'multi_select',
    ];

    private const OPTION_TYPES = [
        'dropdown',
        'single_select',
        'single_select_text',
        'multi_select',
    ];

    public function lint(SurveyVersion $version): array
    {
        $version->loadMissing($this->relations());
        $errors = [];
        $items = $this->items($version);
        $presetKeys = $version->scalePresets->pluck('preset_key')->filter()->all();
        $pageIds = $version->pages->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sectionPageIds = $version->pages
            ->flatMap->sections
            ->pluck('survey_page_id', 'id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (trim((string) $version->instrument_id) === '') {
            $errors[] = 'Instrument ID is required.';
        }
        if ($version->pages->isEmpty()) {
            $errors[] = 'At least one page is required.';
        }
        if ($items->isEmpty()) {
            $errors[] = 'At least one survey item is required.';
        }

        $duplicateQids = $items->pluck('qid')->filter()->duplicates()->unique();
        foreach ($duplicateQids as $qid) {
            $errors[] = "Question ID {$qid} is duplicated.";
        }

        $knownQids = $items->pluck('qid')->filter()->all();
        foreach ($items as $item) {
            $prefix = $item->qid ?: "item:{$item->id}";
            if (trim((string) $item->qid) === '') {
                $errors[] = "{$prefix}: stable question ID is required.";
            }
            if (! in_array($item->type, self::SUPPORTED_TYPES, true)) {
                $errors[] = "{$prefix}: unsupported item type {$item->type}.";
            }
            if ((int) $item->survey_version_id !== (int) $version->id) {
                $errors[] = "{$prefix}: item belongs to another version.";
            }
            if (! in_array((int) $item->survey_page_id, $pageIds, true)) {
                $errors[] = "{$prefix}: item page relationship is inconsistent.";
            }
            if ($item->survey_section_id
                && (int) ($sectionPageIds[$item->survey_section_id] ?? 0) !== (int) $item->survey_page_id) {
                $errors[] = "{$prefix}: section belongs to another page.";
            }
            if (in_array($item->type, self::OPTION_TYPES, true)
                && $item->options->isEmpty()
                && ! $item->optionSource) {
                $errors[] = "{$prefix}: selectable item requires options or an option source.";
            }
            if ($item->type === 'slider') {
                $scale = $item->scale_config ?? [];
                $presetKey = $scale['preset_key'] ?? $scale['preset'] ?? null;
                if ($presetKey && ! in_array($presetKey, $presetKeys, true)) {
                    $errors[] = "{$prefix}: slider references unknown scale preset {$presetKey}.";
                }
                if (! $presetKey
                    && (! isset($scale['min'], $scale['max'])
                        || ! is_numeric($scale['min'])
                        || ! is_numeric($scale['max'])
                        || (float) $scale['min'] >= (float) $scale['max'])) {
                    $errors[] = "{$prefix}: slider requires a valid preset or min/max range.";
                }
            }

            foreach (($item->display_logic['when'] ?? []) as $condition) {
                $dependency = $condition['qid'] ?? null;
                if (! $dependency || ! in_array($dependency, $knownQids, true)) {
                    $errors[] = "{$prefix}: display logic references unknown question {$dependency}.";
                }
                if (! isset($condition['equals_any']) || ! is_array($condition['equals_any'])) {
                    $errors[] = "{$prefix}: display logic requires equals_any values.";
                }
            }
        }

        return array_values(array_unique($errors));
    }

    public function assertPublishable(SurveyVersion $version): string
    {
        $errors = $this->lint($version);
        if ($errors !== []) {
            throw new \DomainException(implode(' ', $errors));
        }

        $this->metricRegistry->assertInstrumentCompatible($version);

        return $this->semanticHash($version);
    }

    public function semanticHash(SurveyVersion $version): string
    {
        $version->loadMissing($this->relations());
        $payload = [
            'instrument_id' => $version->instrument_id,
            'title' => $version->title,
            'meta' => $version->meta,
            'presets' => $version->scalePresets->sortBy('sort_order')->map(fn ($preset) => [
                'key' => $preset->preset_key,
                'config' => $preset->config,
                'sort_order' => $preset->sort_order,
            ])->values()->all(),
            'pages' => $version->pages->sortBy('sort_order')->map(function ($page) {
                return [
                    'title' => $page->title,
                    'sort_order' => $page->sort_order,
                    'attribute_label' => $page->attribute_label,
                    'metadata' => $page->meta,
                    'items' => $page->items->sortBy('sort_order')->map(fn ($item) => $this->itemPayload($item))->values()->all(),
                    'sections' => $page->sections->sortBy('sort_order')->map(fn ($section) => [
                        'title' => $section->title,
                        'sort_order' => $section->sort_order,
                        'metadata' => $section->meta,
                        'items' => $section->items->sortBy('sort_order')->map(fn ($item) => $this->itemPayload($item))->values()->all(),
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];

        return hash('sha256', $this->canonicalJson($payload));
    }

    public function relations(): array
    {
        return [
            'scalePresets',
            'pages.items.options',
            'pages.items.optionSource',
            'pages.sections.items.options',
            'pages.sections.items.optionSource',
        ];
    }

    protected function items(SurveyVersion $version): Collection
    {
        return $version->pages->flatMap(
            fn ($page) => $page->items->concat($page->sections->flatMap->items)
        );
    }

    protected function itemPayload($item): array
    {
        return [
            'qid' => $item->qid,
            'type' => $item->type,
            'question' => $item->question,
            'scale_config' => $item->scale_config,
            'response_config' => $item->response_config,
            'display_logic' => $item->display_logic,
            'metadata' => $item->metadata,
            'sort_order' => $item->sort_order,
            'options' => $item->options->sortBy('sort_order')->map(fn ($option) => [
                'value' => $option->value,
                'label' => $option->label,
                'exclusive' => $option->exclusive,
                'meta' => $option->meta,
                'sort_order' => $option->sort_order,
            ])->values()->all(),
            'option_source' => $item->optionSource ? [
                'kind' => $item->optionSource->kind,
                'config' => $item->optionSource->config,
            ] : null,
        ];
    }

    protected function canonicalJson(array $payload): string
    {
        $normalize = function ($value) use (&$normalize) {
            if (! is_array($value)) {
                return $value;
            }
            if (array_is_list($value)) {
                return array_map($normalize, $value);
            }
            ksort($value);

            return array_map($normalize, $value);
        };

        return json_encode(
            $normalize($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
