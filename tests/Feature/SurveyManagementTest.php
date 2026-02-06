<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
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
        $response->assertSee('No assignments found.');
    }
}
