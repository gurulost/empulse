<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyPage;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurveyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_survey_management_page(): void
    {
        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
        ]);

        $response = $this->actingAs($manager)->get('/surveys/manage');

        $response->assertOk();
        $response->assertViewIs('surveys.manage');
    }

    public function test_manager_only_sees_assignments_for_their_company(): void
    {
        $companyA = Companies::create([
            'title' => 'Company A',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $companyB = Companies::create([
            'title' => 'Company B',
            'manager' => 'Manager B',
            'manager_email' => 'manager-b@example.com',
        ]);

        $managerA = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $companyA->id,
            'company_title' => $companyA->title,
        ]);

        $employeeA = User::factory()->create([
            'role' => 4,
            'company_id' => $companyA->id,
            'company_title' => $companyA->title,
            'email' => 'employee-a@example.com',
        ]);

        $employeeB = User::factory()->create([
            'role' => 4,
            'company_id' => $companyB->id,
            'company_title' => $companyB->title,
            'email' => 'employee-b@example.com',
        ]);

        $survey = Survey::where('is_default', true)->firstOrFail();

        SurveyAssignment::create([
            'survey_id' => $survey->id,
            'user_id' => $employeeA->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
        ]);

        SurveyAssignment::create([
            'survey_id' => $survey->id,
            'user_id' => $employeeB->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($managerA)->get('/surveys/manage');

        $response->assertOk();
        $response->assertSee('employee-a@example.com');
        $response->assertDontSee('employee-b@example.com');
    }

    public function test_manager_without_company_id_sees_no_assignments(): void
    {
        $company = Companies::create([
            'title' => 'Company A',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => null,
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'employee@example.com',
        ]);

        $survey = Survey::where('is_default', true)->firstOrFail();

        SurveyAssignment::create([
            'survey_id' => $survey->id,
            'user_id' => $employee->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($manager)->get('/surveys/manage');

        $response->assertOk();
        $response->assertDontSee('employee@example.com');
        $response->assertSee('Assign this manager to a company to start managing submissions.');
    }

    public function test_survey_management_reads_from_active_version_structure(): void
    {
        $company = Companies::create([
            'title' => 'Company A',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
        ]);

        Survey::updateOrCreate(
            ['is_default' => true],
            [
                'title' => 'Default Survey',
                'company_id' => $company->id,
            ]
        );

        $version = SurveyVersion::create([
            'instrument_id' => 'demo',
            'version' => '2.0.0',
            'title' => 'Live Demo Survey',
            'is_active' => true,
        ]);

        SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'p1',
            'title' => 'Culture Foundations',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($manager)->get('/surveys/manage');

        $response->assertOk();
        $response->assertSee('Culture Foundations');
        $response->assertDontSee('Preview of the placeholder assessment.');
    }

    public function test_survey_management_shows_admin_owned_handoff_when_no_live_survey_exists(): void
    {
        $company = Companies::create([
            'title' => 'Company A',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'company_title' => $company->title,
        ]);

        SurveyVersion::query()->update(['is_active' => false]);

        $response = $this->actingAs($manager)->get('/surveys/manage');

        $response->assertOk();
        $response->assertSee('Workfit admin still needs to activate the live survey');
        $response->assertSee('Contact Workfit Admin');
        $response->assertSee('/contact', false);
    }
}
