<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_view_returns_correct_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard/analytics');

        $response->assertOk();
        $response->assertViewIs('dashboard.analytics');
    }
}
