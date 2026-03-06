<?php

namespace Tests\Feature;

use App\Models\SurveyItem;
use App\Models\SurveyOption;
use App\Models\SurveyOptionSource;
use App\Models\SurveyPage;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function workfitAdmin(): User
    {
        return User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);
    }

    protected function draftItem(array $overrides = []): SurveyItem
    {
        $version = SurveyVersion::create([
            'instrument_id' => 'builder-test',
            'version' => '1.0.1',
            'title' => 'Draft Builder Version',
            'is_active' => false,
        ]);

        $page = SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'builder_page',
            'title' => 'Builder Page',
            'sort_order' => 1,
        ]);

        return SurveyItem::create(array_merge([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'Q_BUILDER',
            'type' => 'text_short',
            'question' => 'Original question',
            'sort_order' => 1,
        ], $overrides));
    }

    public function test_builder_update_item_preserves_display_logic_combinators_and_values(): void
    {
        $admin = $this->workfitAdmin();
        $item = $this->draftItem([
            'display_logic' => [
                'combinator' => 'or',
                'source' => 'imported',
                'when' => [
                    [
                        'qid' => 'Q_PARENT',
                        'equals_any' => ['A', 'B'],
                        'match_type' => 'literal',
                    ],
                    [
                        'qid' => 'Q_SECOND',
                        'equals_any' => ['C'],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->post("/admin/builder/item/{$item->id}", [
            'question' => 'Updated question',
            'type' => 'text_short',
            'display_logic' => [
                'combinator' => 'or',
                'source' => 'imported',
                'when' => [
                    [
                        'qid' => 'Q_PARENT',
                        'equals_any' => ['A', 'B'],
                        'match_type' => 'literal',
                    ],
                    [
                        'qid' => 'Q_SECOND',
                        'equals_any' => ['C'],
                    ],
                ],
            ],
        ]);

        $response->assertOk();

        $item->refresh();
        $this->assertSame('Updated question', $item->question);
        $this->assertSame('or', $item->display_logic['combinator']);
        $this->assertSame('imported', $item->display_logic['source']);
        $this->assertSame(['A', 'B'], $item->display_logic['when'][0]['equals_any']);
        $this->assertSame('literal', $item->display_logic['when'][0]['match_type']);
    }

    public function test_builder_update_item_saves_dropdown_options(): void
    {
        $admin = $this->workfitAdmin();
        $item = $this->draftItem([
            'type' => 'dropdown',
        ]);

        $response = $this->actingAs($admin)->post("/admin/builder/item/{$item->id}", [
            'question' => 'Select a country',
            'type' => 'dropdown',
            'options' => [
                ['value' => 'US', 'label' => 'United States', 'exclusive' => false, 'meta' => []],
                ['value' => 'CA', 'label' => 'Canada', 'exclusive' => false, 'meta' => []],
            ],
        ]);

        $response->assertOk();

        $item->refresh();
        $this->assertSame('dropdown', $item->type);
        $this->assertEqualsCanonicalizing(
            ['US', 'CA'],
            $item->options()->orderBy('sort_order')->pluck('value')->all()
        );
    }

    public function test_builder_update_item_saves_single_select_text_option_metadata(): void
    {
        $admin = $this->workfitAdmin();
        $item = $this->draftItem([
            'type' => 'single_select_text',
        ]);

        $response = $this->actingAs($admin)->post("/admin/builder/item/{$item->id}", [
            'question' => 'Tell us more',
            'type' => 'single_select_text',
            'options' => [
                [
                    'value' => 'OTHER',
                    'label' => 'Other',
                    'exclusive' => false,
                    'meta' => ['freetext_placeholder' => 'Please describe'],
                ],
                [
                    'value' => 'KNOWN',
                    'label' => 'Known',
                    'exclusive' => false,
                    'meta' => [],
                ],
            ],
        ]);

        $response->assertOk();

        $otherOption = SurveyOption::query()
            ->where('survey_item_id', $item->id)
            ->where('value', 'OTHER')
            ->firstOrFail();

        $this->assertSame('Please describe', $otherOption->meta['freetext_placeholder']);
    }

    public function test_builder_structure_includes_option_source_metadata(): void
    {
        $admin = $this->workfitAdmin();
        $item = $this->draftItem([
            'type' => 'dropdown',
        ]);

        SurveyOptionSource::create([
            'survey_item_id' => $item->id,
            'kind' => 'ISO_3166_COUNTRIES_EN',
            'config' => ['source' => 'import'],
        ]);

        $response = $this->actingAs($admin)->getJson("/admin/builder/structure/{$item->survey_version_id}");

        $response->assertOk();
        $response->assertJsonPath('pages.0.items.0.option_source.kind', 'ISO_3166_COUNTRIES_EN');
        $response->assertJsonPath('pages.0.items.0.option_source.config.source', 'import');
    }

    public function test_builder_changing_to_non_option_type_clears_option_source_and_options(): void
    {
        $admin = $this->workfitAdmin();
        $item = $this->draftItem([
            'type' => 'dropdown',
        ]);

        SurveyOption::create([
            'survey_item_id' => $item->id,
            'value' => 'US',
            'label' => 'United States',
            'sort_order' => 0,
        ]);

        SurveyOptionSource::create([
            'survey_item_id' => $item->id,
            'kind' => 'ISO_3166_COUNTRIES_EN',
            'config' => [],
        ]);

        $response = $this->actingAs($admin)->post("/admin/builder/item/{$item->id}", [
            'question' => 'Describe your role',
            'type' => 'text_short',
            'display_logic' => null,
        ]);

        $response->assertOk();

        $item->refresh();
        $this->assertSame('text_short', $item->type);
        $this->assertNull($item->optionSource()->first());
        $this->assertSame(0, $item->options()->count());
    }

    public function test_builder_changing_from_slider_clears_scale_config(): void
    {
        $admin = $this->workfitAdmin();
        $item = $this->draftItem([
            'type' => 'slider',
            'scale_config' => [
                'min' => 1,
                'max' => 10,
                'step' => 1,
            ],
        ]);

        $response = $this->actingAs($admin)->post("/admin/builder/item/{$item->id}", [
            'question' => 'Describe your role',
            'type' => 'text_short',
            'display_logic' => null,
        ]);

        $response->assertOk();

        $item->refresh();
        $this->assertSame('text_short', $item->type);
        $this->assertNull($item->scale_config);
    }
}
