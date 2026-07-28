<?php

namespace Tests\Feature;

use App\Jobs\RecordWorkerHeartbeat;
use App\Models\OperationalHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_does_not_depend_on_backing_services(): void
    {
        $this->getJson('/api/healthz')
            ->assertOk()
            ->assertExactJson(['status' => 'live']);
    }

    public function test_health_surfaces_and_headers_expose_configured_release_identity(): void
    {
        $sha = str_repeat('a', 40);
        config([
            'runtime.release_sha' => $sha,
            'runtime.deployment_environment_id' => 'empulse-staging-us-east',
        ]);

        $this->getJson('/api/healthz')
            ->assertOk()
            ->assertHeader('X-Empulse-Release', $sha)
            ->assertJsonPath('release_sha', $sha)
            ->assertJsonPath('environment_id', 'empulse-staging-us-east');

        $this->getJson('/api/readyz')
            ->assertOk()
            ->assertHeader('X-Empulse-Release', $sha)
            ->assertJsonPath('release_sha', $sha)
            ->assertJsonPath('environment_id', 'empulse-staging-us-east');
    }

    public function test_readiness_requires_database_runtime_tables(): void
    {
        $this->getJson('/api/readyz')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('checks.database', 'connected')
            ->assertJsonPath('checks.runtime_tables', 'available');

        config(['runtime.required_tables' => ['migrations', 'jobs', 'missing_runtime_table']]);

        $this->getJson('/api/readyz')
            ->assertStatus(503)
            ->assertJsonPath('status', 'not_ready')
            ->assertJsonPath('checks.runtime_tables', 'missing');
    }

    public function test_production_readiness_fails_when_process_heartbeats_are_stale(): void
    {
        config([
            'runtime.require_process_heartbeats' => true,
            'runtime.heartbeat_max_age_seconds' => 180,
        ]);

        $this->getJson('/api/readyz')
            ->assertStatus(503)
            ->assertJsonPath('checks.scheduler', 'stale')
            ->assertJsonPath('checks.worker', 'stale');

        OperationalHeartbeat::create([
            'process' => 'scheduler',
            'last_seen_at' => now(),
        ]);
        app(RecordWorkerHeartbeat::class)->handle();

        $this->getJson('/api/readyz')
            ->assertOk()
            ->assertJsonPath('checks.scheduler', 'fresh')
            ->assertJsonPath('checks.worker', 'fresh');
    }
}
