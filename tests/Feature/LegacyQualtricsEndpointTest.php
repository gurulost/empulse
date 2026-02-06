<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyQualtricsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_qualtrics_endpoint_is_not_available(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/qualtrics');

        $response->assertNotFound();
    }
}

