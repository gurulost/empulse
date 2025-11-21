<?php

namespace Tests\Feature;

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
        $user = User::factory()->create(['company_id' => 1]);
        
        // Mock wave finding or provide wave_id
        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('getComparisonData')
            ->once()
            ->andReturn(['labels' => [], 'datasets' => []]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        // We'll pass a wave_id to avoid DB query for latest wave in controller if possible, 
        // but for this test let's just mock the service call which is the critical part.
        // However, the controller does query for wave if not provided. 
        // Let's create a wave to be safe or pass one.
        
        $response = $this->actingAs($user)->getJson('/reports/comparison?wave_id=1&dimension=department');

        $response->assertOk();
    }
}
