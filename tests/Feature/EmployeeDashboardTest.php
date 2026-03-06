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
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_dashboard_shows_reassurance_block_for_active_assignment(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'employee@example.com',
        ]);

        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();

        $page = SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'trust_page',
            'title' => 'Trust Page',
            'sort_order' => 1,
        ]);

        for ($index = 1; $index <= 6; $index++) {
            SurveyItem::create([
                'survey_version_id' => $version->id,
                'survey_page_id' => $page->id,
                'qid' => sprintf('TRUST_%02d', $index),
                'type' => 'slider',
                'question' => "Trust Question {$index}",
                'sort_order' => $index,
            ]);
        }

        SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employee->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
            'wave_label' => 'April Pulse',
        ]);

        $response = $this->actingAs($employee)->get(route('employee.dashboard'));

        $response->assertOk();
        $response->assertSee('Before you start');
        $response->assertSee('Progress autosaves');
        $response->assertSee('Most people finish in about 4 minutes');
    }
}
