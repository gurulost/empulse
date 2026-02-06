<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyItem;
use App\Models\SurveyPage;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurveySubmissionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_rejects_invalid_slider_values(): void
    {
        [$assignment, $page, $version] = $this->createAssignmentWithVersion();

        SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'WCA_REL_A',
            'type' => 'slider',
            'question' => 'Current relationships',
            'scale_config' => ['min' => 1, 'max' => 10, 'step' => 1],
            'response_config' => ['required' => true],
            'sort_order' => 1,
        ]);

        $response = $this->postJson('/survey/' . $assignment->token, [
            'responses' => [
                'WCA_REL_A' => 11,
            ],
            'duration_ms' => 2500,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['WCA_REL_A']);

        $this->assertDatabaseCount('survey_responses', 0);
        $this->assertDatabaseHas('survey_assignments', [
            'id' => $assignment->id,
            'status' => 'pending',
        ]);
    }

    public function test_submit_ignores_hidden_answers_and_skips_hidden_validation(): void
    {
        [$assignment, $page, $version] = $this->createAssignmentWithVersion();

        SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'WCA_REL_A',
            'type' => 'slider',
            'question' => 'Current relationships',
            'scale_config' => ['min' => 1, 'max' => 10, 'step' => 1],
            'response_config' => ['required' => true],
            'sort_order' => 1,
        ]);

        SurveyItem::create([
            'survey_version_id' => $version->id,
            'survey_page_id' => $page->id,
            'qid' => 'HIDDEN_INT',
            'type' => 'number_integer',
            'question' => 'Hidden number',
            'response_config' => ['required' => true, 'min' => 0],
            'display_logic' => [
                'operator' => 'and',
                'when' => [
                    ['qid' => 'WCA_REL_A', 'equals_any' => ['10']],
                ],
            ],
            'sort_order' => 2,
        ]);

        $response = $this->postJson('/survey/' . $assignment->token, [
            'responses' => [
                'WCA_REL_A' => 5,
                'HIDDEN_INT' => 'not-a-number',
            ],
            'duration_ms' => 1500,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        $storedResponse = SurveyResponse::first();
        $this->assertNotNull($storedResponse);

        $this->assertDatabaseHas('survey_answers', [
            'response_id' => $storedResponse->id,
            'question_key' => 'WCA_REL_A',
        ]);
        $this->assertDatabaseMissing('survey_answers', [
            'response_id' => $storedResponse->id,
            'question_key' => 'HIDDEN_INT',
        ]);
    }

    protected function createAssignmentWithVersion(): array
    {
        $survey = Survey::create([
            'title' => 'Org Survey',
            'status' => 'published',
        ]);

        $version = SurveyVersion::create([
            'instrument_id' => 'eng-v1',
            'version' => '1.0.0',
            'title' => 'Org Survey v1',
            'is_active' => true,
        ]);

        $page = SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'page_1',
            'title' => 'Page 1',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create();

        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $user->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
        ]);

        return [$assignment, $page, $version];
    }
}
