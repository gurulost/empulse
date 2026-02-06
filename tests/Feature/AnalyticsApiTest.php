<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use App\Services\SurveyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_non_admin_cannot_request_other_company(): void
    {
        $companyA = Companies::create([
            'title' => 'Alpha Co',
            'manager' => 'Anna',
            'manager_email' => 'anna@example.com',
        ]);

        $companyB = Companies::create([
            'title' => 'Beta Co',
            'manager' => 'Ben',
            'manager_email' => 'ben@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 1,
            'company_id' => $companyA->id,
            'company_title' => $companyA->title,
        ]);

        $response = $this->actingAs($user)->getJson("/analytics/api/dashboard?company_id={$companyB->id}");

        $response->assertStatus(403)->assertJson([
            'message' => 'Forbidden',
        ]);
    }

    public function test_admin_can_fetch_other_company_filters(): void
    {
        $company = Companies::create([
            'title' => 'Gamma Co',
            'manager' => 'Gina',
            'manager_email' => 'gina@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'company_id' => null,
        ]);

        $survey = Survey::where('is_default', true)->first();
        $version = SurveyVersion::where('is_active', true)->first();

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'active',
            'cadence' => 'once',
            'label' => 'Wave Alpha',
        ]);

        $member = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 3,
            'name' => 'Team Lead',
            'email' => 'teamlead@example.com',
        ]);

        DB::table('company_worker')->insert([
            'company_id' => $company->id,
            'name' => 'Team Lead',
            'email' => 'teamlead@example.com',
            'department' => 'Ops',
            'role' => 3,
            'supervisor' => null,
        ]);

        DB::table('company_department')->insert([
            'company_id' => $company->id,
            'title' => 'Ops',
        ]);

        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'user_id' => $member->id,
            'token' => (string) Str::uuid(),
            'status' => 'completed',
            'wave_label' => 'Wave Alpha',
        ]);

        SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'assignment_id' => $assignment->id,
            'user_id' => $member->id,
            'wave_label' => 'Wave Alpha',
            'submitted_at' => now(),
        ]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('companyDashboardAnalytics')
            ->once()
            ->with([
                'company_id' => $company->id,
                'department' => null,
                'team' => null,
                'wave' => null,
            ])
            ->andReturn(['metrics' => [10, 20]]);
        $mock->shouldReceive('availableWavesForCompany')
            ->once()
            ->with($company->id)
            ->andReturn([
                'wave:' . $wave->id => 'Wave Alpha',
            ]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($admin)->getJson("/analytics/api/dashboard?company_id={$company->id}");

        $response->assertOk()
            ->assertJsonPath('data.metrics', [10, 20])
            ->assertJsonPath("filters.waves.wave:{$wave->id}", 'Wave Alpha')
            ->assertJsonStructure([
                'filters' => ['departments', 'teamleads', 'waves', 'exist_departments'],
            ]);
    }

    public function test_workfit_admin_without_company_can_fetch_selected_company_filters(): void
    {
        $company = Companies::create([
            'title' => 'Delta Co',
            'manager' => 'Dana',
            'manager_email' => 'dana@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 1,
            'is_admin' => 1,
            'company_id' => null,
        ]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('companyDashboardAnalytics')
            ->once()
            ->with([
                'company_id' => $company->id,
                'department' => null,
                'team' => null,
                'wave' => null,
            ])
            ->andReturn(['metrics' => [5, 6]]);
        $mock->shouldReceive('availableWavesForCompany')
            ->once()
            ->with($company->id)
            ->andReturn([]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($admin)->getJson("/analytics/api/dashboard?company_id={$company->id}");

        $response->assertOk()
            ->assertJsonPath('data.metrics', [5, 6]);
    }
}
