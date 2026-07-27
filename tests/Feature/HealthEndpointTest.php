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
