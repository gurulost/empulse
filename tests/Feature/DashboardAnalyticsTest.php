<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use App\Services\SurveyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dashboard_endpoint_passes_filters_to_service(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
        ]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('companyDashboardAnalytics')
            ->once()
            ->with([
                'company_id' => $company->id,
                'department' => 'Operations',
                'team' => 'A-Team',
                'wave' => 'wave-1',
            ])
            ->andReturn(['metrics' => []]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($user)->getJson('/dashboard/analytics?department=Operations&team=A-Team&wave=wave-1');

        $response->assertOk();
        $response->assertJson([
            'data' => ['metrics' => []],
        ]);
    }
}
