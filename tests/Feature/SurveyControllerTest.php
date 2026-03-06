<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyItem;
use App\Models\SurveyPage;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurveyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_survey_definition_includes_question_count_and_estimated_minutes(): void
    {
        $assignment = $this->seedAssignmentWithQuestions(6);

        $response = $this->getJson(route('survey.definition', $assignment->token));

        $response->assertOk()
            ->assertJsonPath('survey_meta.question_count', 6)
            ->assertJsonPath('survey_meta.estimated_minutes', 4);
    }

    public function test_survey_show_records_employee_entry_view_event(): void
    {
        $assignment = $this->seedAssignmentWithQuestions(10);

        $response = $this->get(route('survey.take', $assignment->token));

        $response->assertOk();

        $event = DB::table('onboarding_events')
            ->where('name', 'employee_survey_entry_viewed')
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('survey.take', $event->context_surface);
        $this->assertSame($assignment->user_id, $event->user_id);
        $this->assertSame($assignment->user->company_id, $event->company_id);
    }

    protected function seedAssignmentWithQuestions(int $questionCount): SurveyAssignment
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();

        $page = SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'intro',
            'title' => 'Intro',
            'sort_order' => 1,
        ]);

        for ($index = 1; $index <= $questionCount; $index++) {
            SurveyItem::create([
                'survey_version_id' => $version->id,
                'survey_page_id' => $page->id,
                'qid' => sprintf('Q_%02d', $index),
                'type' => 'slider',
                'question' => "Question {$index}",
                'sort_order' => $index,
            ]);
        }

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'employee@example.com',
        ]);

        return SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employee->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
            'wave_label' => 'March Pulse',
        ]);
    }
}
