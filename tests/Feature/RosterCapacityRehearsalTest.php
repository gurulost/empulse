<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterCapacityRehearsalTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_rehearsal_requires_explicit_mutation_confirmation(): void
    {
        $this->artisan('readiness:roster-rehearsal', [
            'company_id' => 1,
            'actor_id' => 1,
        ])
            ->expectsOutputToContain('Re-run with --execute')
            ->assertFailed();
    }

    public function test_roster_rehearsal_requires_postgresql(): void
    {
        $this->artisan('readiness:roster-rehearsal', [
            'company_id' => 1,
            'actor_id' => 1,
            '--execute' => true,
        ])
            ->expectsOutputToContain('requires PostgreSQL')
            ->assertFailed();
    }
}
