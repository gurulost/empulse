<?php

namespace App\Console\Commands;

use App\Models\SurveyItem;
use App\Models\SurveyOption;
use App\Models\SurveyOptionSource;
use App\Models\SurveyPage;
use App\Models\SurveyScalePreset;
use App\Models\SurveySection;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\SurveyVersionIntegrityService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ImportSurvey extends Command
{
    protected $signature = 'survey:import
        {path : Path to the survey JSON file}
        {--activate : Publish this version after governed validation}
        {--approved-by= : Active WorkFit administrator user ID required with --activate}
        {--change-summary= : Publication change summary required with --activate}';

    protected $description = 'Import a survey instrument definition into the database';

    public function handle(SurveyVersionIntegrityService $integrity): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("Survey file not found at {$path}");

            return Command::FAILURE;
        }

        $payload = json_decode(file_get_contents($path), true);
        if (! $payload) {
            $this->error('Unable to parse JSON file.');

            return Command::FAILURE;
        }

        $approver = null;
        $changeSummary = trim((string) $this->option('change-summary'));
        if ($this->option('activate')) {
            $approver = User::find($this->option('approved-by'));
            if (! $approver
                || $approver->status !== 'active'
                || ! $approver->hasCapability('survey-builder.manage')) {
                $this->error('--activate requires --approved-by with an active WorkFit survey administrator user ID.');

                return Command::FAILURE;
            }
            if (mb_strlen($changeSummary) < 10) {
                $this->error('--activate requires a change summary of at least 10 characters.');

                return Command::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($payload, $integrity, $approver, $changeSummary) {
                $version = SurveyVersion::create([
                    'instrument_id' => Arr::get($payload, 'instrument_id', 'unknown'),
                    'version' => Arr::get($payload, 'version', '1.0.0'),
                    'title' => Arr::get($payload, 'title', 'Survey Instrument'),
                    'created_utc' => Arr::get($payload, 'created_utc'),
                    'source_note' => Arr::get($payload, 'source_note'),
                    'meta' => Arr::except($payload, ['scale_presets', 'pages']),
                    'publication_status' => 'draft',
                ]);

                $this->storeScalePresets($version, Arr::get($payload, 'scale_presets', []));
                $this->storePages($version, Arr::get($payload, 'pages', []));

                if ($approver) {
                    $contentHash = $integrity->assertPublishable($version);
                    SurveyVersion::where('instrument_id', $version->instrument_id)
                        ->where('id', '!=', $version->id)
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            'publication_status' => 'retired',
                        ]);
                    $now = now();
                    $version->update([
                        'is_active' => true,
                        'publication_status' => 'published',
                        'change_summary' => $changeSummary,
                        'content_hash' => $contentHash,
                        'reviewed_by' => $approver->id,
                        'reviewed_at' => $now,
                        'approved_by' => $approver->id,
                        'approved_at' => $now,
                        'published_by' => $approver->id,
                        'published_at' => $now,
                    ]);
                    app(AuditTrailService::class)->record(
                        'survey.version_published',
                        $approver,
                        null,
                        SurveyVersion::class,
                        $version->id,
                        [],
                        [
                            'content_hash' => $contentHash,
                            'source' => 'survey:import',
                        ]
                    );
                }

                $this->info("Imported survey version {$version->version} ({$version->instrument_id})");
            });
        } catch (\DomainException $exception) {
            $this->error('Survey import failed governed publication checks: '.$exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function storeScalePresets(SurveyVersion $version, array $presets): void
    {
        $order = 0;
        foreach ($presets as $key => $config) {
            SurveyScalePreset::create([
                'survey_version_id' => $version->id,
                'preset_key' => $key,
                'config' => $config,
                'sort_order' => $order++,
            ]);
        }
    }

    protected function storePages(SurveyVersion $version, array $pages): void
    {
        foreach ($pages as $pageIndex => $pageData) {
            $page = SurveyPage::create([
                'survey_version_id' => $version->id,
                'page_id' => Arr::get($pageData, 'page_id', 'page_'.$pageIndex),
                'title' => Arr::get($pageData, 'title', 'Page '.($pageIndex + 1)),
                'attribute_label' => Arr::get($pageData, 'attribute_label'),
                'sort_order' => $pageIndex,
                'meta' => Arr::only($pageData, ['end_of_survey_message']),
            ]);

            $sections = Arr::get($pageData, 'sections', []);
            foreach ($sections as $sectionIndex => $sectionData) {
                $section = SurveySection::create([
                    'survey_page_id' => $page->id,
                    'section_id' => Arr::get($sectionData, 'section_id', "{$page->page_id}_section_{$sectionIndex}"),
                    'title' => Arr::get($sectionData, 'title'),
                    'sort_order' => $sectionIndex,
                    'meta' => Arr::except($sectionData, ['section_id', 'title', 'items']),
                ]);
                $this->storeItems(
                    Arr::get($sectionData, 'items', []),
                    $version,
                    $page,
                    $section
                );
            }

            if (! empty($pageData['items'])) {
                $this->storeItems($pageData['items'], $version, $page, null);
            }
        }
    }

    protected function storeItems(array $items, SurveyVersion $version, SurveyPage $page, ?SurveySection $section): void
    {
        foreach ($items as $itemIndex => $item) {
            $scaleConfig = null;
            if (isset($item['scale'])) {
                $scaleConfig = is_string($item['scale'])
                    ? ['preset_key' => $item['scale']]
                    : $item['scale'];
            }

            $metadata = [];
            if (isset($item['coding_hint'])) {
                $metadata['coding_hint'] = $item['coding_hint'];
            }
            if (isset($item['attribute_label'])) {
                $metadata['attribute_label'] = $item['attribute_label'];
            }
            if (isset($item['note'])) {
                $metadata['note'] = $item['note'];
            }

            $surveyItem = SurveyItem::create([
                'survey_version_id' => $version->id,
                'survey_page_id' => $page->id,
                'survey_section_id' => $section?->id,
                'qid' => Arr::get($item, 'qid', $this->generateQid($page, $section, $itemIndex)),
                'type' => Arr::get($item, 'type', 'text_short'),
                'question' => Arr::get($item, 'question', ''),
                'scale_config' => $scaleConfig,
                'response_config' => Arr::get($item, 'response'),
                'display_logic' => Arr::get($item, 'display_logic'),
                'metadata' => ! empty($metadata) ? $metadata : null,
                'sort_order' => $itemIndex,
            ]);

            $this->storeOptions($surveyItem, Arr::get($item, 'options', []));
            $this->storeOptionSource($surveyItem, $item);
        }
    }

    protected function storeOptions(SurveyItem $item, array $options): void
    {
        foreach ($options as $index => $option) {
            SurveyOption::create([
                'survey_item_id' => $item->id,
                'value' => array_key_exists('value', $option) ? (string) $option['value'] : null,
                'label' => Arr::get($option, 'label', ''),
                'exclusive' => Arr::get($option, 'exclusive', false),
                'meta' => Arr::only($option, ['freetext_placeholder']),
                'sort_order' => $index,
            ]);
        }
    }

    protected function storeOptionSource(SurveyItem $item, array $rawItem): void
    {
        if (isset($rawItem['options_source'])) {
            $source = $rawItem['options_source'];
            SurveyOptionSource::create([
                'survey_item_id' => $item->id,
                'kind' => Arr::get($source, 'kind', 'custom'),
                'config' => Arr::except($source, ['kind']),
            ]);
        } elseif (isset($rawItem['options_algorithm'])) {
            $source = $rawItem['options_algorithm'];
            SurveyOptionSource::create([
                'survey_item_id' => $item->id,
                'kind' => 'algorithm:'.Arr::get($source, 'kind', 'custom'),
                'config' => $source,
            ]);
        }
    }

    protected function generateQid(SurveyPage $page, ?SurveySection $section, int $index): string
    {
        $parts = [$page->page_id];
        if ($section) {
            $parts[] = $section->section_id;
        }
        $parts[] = 'item_'.$index;

        return strtoupper(implode('_', $parts));
    }
}
