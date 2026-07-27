<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_command_materializes_the_checkout_catalog_idempotently(): void
    {
        config([
            'billing.catalog.starter.stripe_price' => 'price_starter_approved',
            'billing.catalog.starter.price_cents' => 9900,
            'billing.catalog.pulse.stripe_price' => 'price_pulse_approved',
            'billing.catalog.pulse.price_cents' => 19900,
        ]);

        $this->artisan('billing:sync-catalog')->assertSuccessful();
        $this->artisan('billing:sync-catalog')->assertSuccessful();

        $this->assertDatabaseCount('plans', 2);
        $this->assertDatabaseHas('plans', [
            'slug' => 'starter',
            'stripe_plan' => 'price_starter_approved',
            'price' => 9900,
        ]);
        $this->assertDatabaseHas('plans', [
            'slug' => 'pulse',
            'stripe_plan' => 'price_pulse_approved',
            'price' => 19900,
        ]);
        $this->assertDatabaseMissing('plans', ['slug' => 'business-plan']);
    }

    public function test_release_command_fails_closed_when_checkout_catalog_is_incomplete(): void
    {
        config([
            'billing.catalog.starter.stripe_price' => null,
            'billing.catalog.starter.price_cents' => null,
            'billing.catalog.pulse.stripe_price' => 'price_pulse',
            'billing.catalog.pulse.price_cents' => 19900,
        ]);

        $this->artisan('billing:sync-catalog')
            ->expectsOutputToContain('starter')
            ->assertFailed();

        $this->assertDatabaseCount('plans', 0);
    }
}
