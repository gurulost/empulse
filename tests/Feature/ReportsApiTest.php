<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use App\Services\SurveyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReportsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_trends_endpoint_returns_data()
    {
        $user = User::factory()->create(['company_id' => 1]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('getTrendData')
            ->once()
            ->with(1, 'engagement')
            ->andReturn(['labels' => [], 'datasets' => []]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($user)->getJson('/reports/trends?metric=engagement');

        $response->assertOk();
        $response->assertJsonStructure(['labels', 'datasets']);
    }

    public function test_comparison_endpoint_returns_data()
    {
        $company = Companies::create([
            'title' => 'Acme Corp',
            'manager' => 'Manager One',
            'manager_email' => 'manager@example.com',
        ]);

        $user = User::factory()->create(['company_id' => $company->id]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'active',
            'cadence' => 'manual',
            'label' => 'Wave 1',
            'due_at' => now(),
        ]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('getComparisonData')
            ->once()
            ->with($company->id, $wave->id, 'department')
            ->andReturn(['labels' => [], 'datasets' => []]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($user)->getJson("/reports/comparison?wave_id={$wave->id}&dimension=department");

        $response->assertOk();
    }

    public function test_comparison_endpoint_rejects_wave_from_another_company(): void
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

        $user = User::factory()->create(['company_id' => $companyA->id]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();

        $foreignWave = SurveyWave::create([
            'company_id' => $companyB->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'active',
            'cadence' => 'manual',
            'label' => 'Foreign Wave',
            'due_at' => now(),
        ]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldNotReceive('getComparisonData');
        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($user)->getJson("/reports/comparison?wave_id={$foreignWave->id}&dimension=department");

        $response->assertStatus(404)->assertJson([
            'message' => 'Wave not found',
        ]);
    }

    public function test_workfit_admin_can_request_trends_for_selected_company()
    {
        $company = Companies::create([
            'title' => 'Acme Co',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
            'company_id' => null,
        ]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('getTrendData')
            ->once()
            ->with($company->id, 'culture')
            ->andReturn(['labels' => [], 'datasets' => []]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($admin)->getJson("/reports/trends?metric=culture&company_id={$company->id}");

        $response->assertOk();
        $response->assertJsonStructure(['labels', 'datasets']);
    }

    public function test_workfit_admin_without_company_requires_company_selection_for_reports(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
            'is_admin' => 1,
            'company_id' => null,
        ]);

        $response = $this->actingAs($admin)->getJson('/reports/trends?metric=engagement');

        $response->assertStatus(422)->assertJson([
            'message' => 'Company is required.',
        ]);
    }
}
