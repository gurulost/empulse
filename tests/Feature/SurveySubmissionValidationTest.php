<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyItem;
use App\Models\SurveyOption;
use App\Models\SurveyPage;
use App\Models\SurveyScalePreset;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurveySubmissionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_rejects_invalid_payload_and_unknown_questions(): void
    {
        config()->set('survey.validation.strict_server_validation', true);

        $assignment = $this->seedSurveyAssignment();

        $payload = [
            'responses' => [
                'Q_SLIDER' => 11,
                'Q_SELECT' => 'Z',
                'Q_MULTI' => ['X', 'INVALID'],
                'Q_NUM' => -1,
                'Q_EMAIL' => 'invalid-email',
                'UNKNOWN_QID' => 'tampered',
            ],
            'duration_ms' => 1234,
        ];

        $response = $this->postJson(route('survey.submit', $assignment->token), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'responses.Q_SLIDER',
            'responses.Q_SELECT',
            'responses.Q_MULTI',
            'responses.Q_NUM',
            'responses.Q_EMAIL',
            'responses.UNKNOWN_QID',
        ]);

        $this->assertDatabaseCount('survey_responses', 0);
        $this->assertDatabaseCount('survey_answers', 0);
        $this->assertDatabaseHas('survey_assignments', [
            'id' => $assignment->id,
            'status' => 'pending',
        ]);
    }

    public function test_submit_requires_visible_conditional_item_but_skips_it_when_hidden(): void
    {
        config()->set('survey.validation.strict_server_validation', true);

        $assignment = $this->seedSurveyAssignment();

        $baseResponses = [
            'Q_SLIDER' => 6,
            'Q_MULTI' => ['X'],
            'Q_NUM' => 2,
            'Q_EMAIL' => 'person@example.com',
        ];

        $missingVisibleResponse = $this->postJson(route('survey.submit', $assignment->token), [
            'responses' => array_merge($baseResponses, [
                'Q_SELECT' => 'B',
            ]),
            'duration_ms' => 900,
        ]);

        $missingVisibleResponse
            ->assertStatus(422)
            ->assertJsonValidationErrors(['responses.Q_DEP']);

        $successfulResponse = $this->postJson(route('survey.submit', $assignment->token), [
            'responses' => array_merge($baseResponses, [
                'Q_SELECT' => 'A',
            ]),
            'duration_ms' => 900,
        ]);

        $successfulResponse->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('survey_assignments', [
            'id' => $assignment->id,
            'status' => 'completed',
        ]);
    }

    public function test_submit_ignores_hidden_answer_values_when_persisting(): void
    {
        config()->set('survey.validation.strict_server_validation', true);

        $assignment = $this->seedSurveyAssignment();

        $response = $this->postJson(route('survey.submit', $assignment->token), [
            'responses' => [
                'Q_SLIDER' => 4,
                'Q_SELECT' => 'A',
                'Q_DEP' => 'DEP_1',
                'Q_MULTI' => ['Y', 'NONE'],
                'Q_NUM' => 3,
                'Q_EMAIL' => 'hidden@example.com',
            ],
            'duration_ms' => 450,
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $assignment = $assignment->fresh();
        $this->assertSame('completed', $assignment->status);
        $this->assertNotNull($assignment->completed_at);

        $persistedQids = SurveyAnswer::query()
            ->whereHas('response', fn ($query) => $query->where('assignment_id', $assignment->id))
            ->pluck('question_key')
            ->all();

        $this->assertContains('Q_SLIDER', $persistedQids);
        $this->assertContains('Q_SELECT', $persistedQids);
        $this->assertContains('Q_MULTI', $persistedQids);
        $this->assertContains('Q_NUM', $persistedQids);
        $this->assertContains('Q_EMAIL', $persistedQids);
        $this->assertNotContains('Q_DEP', $persistedQids);

        $multiAnswer = SurveyAnswer::query()
            ->whereHas('response', fn ($query) => $query->where('assignment_id', $assignment->id))
            ->where('question_key', 'Q_MULTI')
            ->value('value');
        $this->assertSame(['NONE'], json_decode((string) $multiAnswer, true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_submit_in_strict_mode_trims_email_before_persisting(): void
    {
        config()->set('survey.validation.strict_server_validation', true);

        $assignment = $this->seedSurveyAssignment();

        $response = $this->postJson(route('survey.submit', $assignment->token), [
            'responses' => [
                'Q_SLIDER' => 4,
                'Q_SELECT' => 'A',
                'Q_MULTI' => ['Y'],
                'Q_NUM' => 3,
                'Q_EMAIL' => ' person@example.com ',
            ],
            'duration_ms' => 450,
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $emailAnswer = SurveyAnswer::query()
            ->whereHas('response', fn ($query) => $query->where('assignment_id', $assignment->id))
            ->where('question_key', 'Q_EMAIL')
            ->value('value');

        $this->assertSame('person@example.com', $emailAnswer);
    }

    public function test_submit_in_non_strict_mode_keeps_legacy_payload_behavior(): void
    {
        config()->set('survey.validation.strict_server_validation', false);

        $assignment = $this->seedSurveyAssignment();

        $response = $this->postJson(route('survey.submit', $assignment->token), [
            'responses' => [
                'Q_SLIDER' => 11,
                'Q_SELECT' => 'A',
                'Q_DEP' => 'DEP_1',
                'Q_MULTI' => ['X', 'NONE'],
                'Q_NUM' => -1,
                'Q_EMAIL' => ' invalid-email ',
                'UNKNOWN_QID' => 'tampered',
            ],
            'duration_ms' => 1234,
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $answersByQid = SurveyAnswer::query()
            ->whereHas('response', fn ($query) => $query->where('assignment_id', $assignment->id))
            ->pluck('value', 'question_key')
            ->toArray();

        $this->assertArrayHasKey('Q_DEP', $answersByQid);
        $this->assertArrayNotHasKey('UNKNOWN_QID', $answersByQid);
        $this->assertSame('11', $answersByQid['Q_SLIDER']);
        $this->assertSame('invalid-email', $answersByQid['Q_EMAIL']);
    }

    protected function seedSurveyAssignment(): SurveyAssignment
    {
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();
        $user = User::factory()->create();

        SurveyScalePreset::create([
            'survey_version_id' => $version->id,
            'preset_key' => 'SLIDER_1_10',
            'config' => [
                'type' => 'slider',
                'min' => 1,
                'max' => 10,
                'step' => 1,
            ],
            'sort_order' => 1,
        ]);

        $page = SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'validation_page',
            'title' => 'Validation Page',
            'sort_order' => 1,
        ]);

        SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'Q_SLIDER',
            'type' => 'slider',
            'question' => 'Slider Question',
            'scale_config' => ['preset_key' => 'SLIDER_1_10'],
            'sort_order' => 1,
        ]);

        $selectItem = SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'Q_SELECT',
            'type' => 'single_select',
            'question' => 'Select Question',
            'sort_order' => 2,
        ]);

        SurveyOption::insert([
            [
                'survey_item_id' => $selectItem->id,
                'value' => 'A',
                'label' => 'Option A',
                'exclusive' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'survey_item_id' => $selectItem->id,
                'value' => 'B',
                'label' => 'Option B',
                'exclusive' => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $dependentItem = SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'Q_DEP',
            'type' => 'dropdown',
            'question' => 'Dependent Question',
            'display_logic' => [
                'when' => [
                    [
                        'qid' => 'Q_SELECT',
                        'equals_any' => ['B'],
                    ],
                ],
            ],
            'sort_order' => 3,
        ]);

        SurveyOption::create([
            'survey_item_id' => $dependentItem->id,
            'value' => 'DEP_1',
            'label' => 'Dependent Value',
            'exclusive' => false,
            'sort_order' => 1,
        ]);

        $multiItem = SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'Q_MULTI',
            'type' => 'multi_select',
            'question' => 'Multi Select Question',
            'sort_order' => 4,
        ]);

        SurveyOption::insert([
            [
                'survey_item_id' => $multiItem->id,
                'value' => 'X',
                'label' => 'Value X',
                'exclusive' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'survey_item_id' => $multiItem->id,
                'value' => 'Y',
                'label' => 'Value Y',
                'exclusive' => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'survey_item_id' => $multiItem->id,
                'value' => 'NONE',
                'label' => 'None',
                'exclusive' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'Q_NUM',
            'type' => 'number_integer',
            'question' => 'Numeric Question',
            'response_config' => ['min' => 0],
            'sort_order' => 5,
        ]);

        SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'Q_EMAIL',
            'type' => 'text_short',
            'question' => 'Email Question',
            'response_config' => ['format_hint' => 'email'],
            'sort_order' => 6,
        ]);

        return SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $user->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
        ]);
    }
}
