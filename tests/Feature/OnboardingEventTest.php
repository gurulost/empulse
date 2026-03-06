<?php

namespace Tests\Feature;

use App\Jobs\ProcessSurveyWave;
use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use App\Services\SurveyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnboardingEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_store_onboarding_event_for_own_company(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company_id' => $company->id,
            'company_title' => $company->title,
        ]);

        $this->actingAs($manager)
            ->postJson('/onboarding/events', [
                'name' => 'onboarding_checklist_viewed',
                'context_surface' => 'dashboard.analytics',
                'task_id' => 'company_activation',
                'company_id' => $company->id,
                'user_segment' => 'novice',
                'guidance_level' => 'light',
                'session_id' => 'session-1',
                'time_since_session_start_sec' => 5,
                'properties' => [
                    'recipient_count' => 0,
                ],
            ])
            ->assertCreated()
            ->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('onboarding_events', [
            'company_id' => $company->id,
            'user_id' => $manager->id,
            'name' => 'onboarding_checklist_viewed',
            'context_surface' => 'dashboard.analytics',
            'task_id' => 'company_activation',
        ]);
    }

    public function test_non_admin_cannot_store_event_for_other_company(): void
    {
        $companyA = Companies::create([
            'title' => 'Alpha',
            'manager' => 'Alice',
            'manager_email' => 'alice@example.com',
        ]);
        $companyB = Companies::create([
            'title' => 'Beta',
            'manager' => 'Bob',
            'manager_email' => 'bob@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company_id' => $companyA->id,
            'company_title' => $companyA->title,
        ]);

        $this->actingAs($manager)
            ->postJson('/onboarding/events', [
                'name' => 'onboarding_checklist_viewed',
                'context_surface' => 'dashboard.analytics',
                'company_id' => $companyB->id,
            ])
            ->assertStatus(403);
    }

    public function test_manager_and_employee_surface_events_are_accepted(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'manager@acme.test',
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'employee@acme.test',
        ]);

        $this->actingAs($manager)
            ->postJson('/onboarding/events', [
                'name' => 'survey_activation_handoff_clicked',
                'context_surface' => 'dashboard.analytics',
                'task_id' => 'survey_activation',
                'company_id' => $company->id,
                'properties' => [
                    'destination' => '/contact',
                ],
            ])
            ->assertCreated();

        $this->actingAs($employee)
            ->postJson('/onboarding/events', [
                'name' => 'employee_survey_launch_clicked',
                'context_surface' => 'employee.dashboard',
                'task_id' => 'survey_launch',
                'company_id' => $company->id,
                'properties' => [
                    'estimated_minutes' => 6,
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('onboarding_events', [
            'company_id' => $company->id,
            'user_id' => $manager->id,
            'name' => 'survey_activation_handoff_clicked',
            'context_surface' => 'dashboard.analytics',
        ]);

        $this->assertDatabaseHas('onboarding_events', [
            'company_id' => $company->id,
            'user_id' => $employee->id,
            'name' => 'employee_survey_launch_clicked',
            'context_surface' => 'employee.dashboard',
        ]);
    }

    public function test_processing_first_wave_records_first_wave_dispatched_only_once(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'tariff' => 1,
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
        ]);

        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();

        $firstWave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'First Wave',
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $secondWave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Second Wave',
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        Queue::fake();

        (new ProcessSurveyWave($firstWave->id))->handle(app(SurveyService::class), app(\App\Services\OnboardingTelemetryService::class));
        (new ProcessSurveyWave($secondWave->id))->handle(app(SurveyService::class), app(\App\Services\OnboardingTelemetryService::class));

        $this->assertDatabaseCount('onboarding_events', 1);
        $this->assertDatabaseHas('onboarding_events', [
            'company_id' => $company->id,
            'name' => 'first_wave_dispatched',
            'context_surface' => 'survey-waves',
        ]);
    }

    public function test_first_completed_response_records_company_first_response_only_once(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $employeeOne = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'employee1@example.com',
        ]);

        $employeeTwo = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'employee2@example.com',
        ]);

        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();

        $assignmentOne = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employeeOne->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
            'wave_label' => 'Wave One',
        ]);

        $assignmentTwo = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employeeTwo->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
            'wave_label' => 'Wave Two',
        ]);

        $surveyService = app(SurveyService::class);

        $surveyService->recordResponse($assignmentOne, []);
        $surveyService->recordResponse($assignmentTwo, []);

        $this->assertDatabaseCount('onboarding_events', 1);
        $this->assertDatabaseHas('onboarding_events', [
            'company_id' => $company->id,
            'name' => 'first_response_completed',
            'context_surface' => 'survey',
        ]);
    }
}
