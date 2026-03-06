<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_payment_success_route_does_not_upgrade_company_tariff(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 0,
        ]);

        $coworker = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'tariff' => 0,
        ]);

        $this->actingAs($manager)
            ->get(route('payment-success'))
            ->assertRedirect(route('billing.index'));

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'tariff' => 0,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $coworker->id,
            'tariff' => 0,
        ]);
    }

    public function test_plan_show_page_degrades_gracefully_when_stripe_is_unconfigured(): void
    {
        config()->set('services.stripe.key', null);
        config()->set('services.stripe.secret', null);

        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
        ]);

        $plan = Plan::create([
            'name' => 'Pulse',
            'slug' => 'pulse',
            'stripe_plan' => 'price_pulse_demo',
            'price' => 19900,
            'description' => 'Pulse plan',
        ]);

        $this->actingAs($manager)
            ->get(route('plans.show', $plan))
            ->assertOk()
            ->assertSee('Billing is unavailable in this environment');
    }

    public function test_stripe_subscription_webhook_keeps_cashier_sync_and_updates_company_tariff(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 0,
            'stripe_id' => 'cus_test_123',
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'tariff' => 0,
        ]);

        $payload = [
            'id' => 'evt_123',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_123',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'items' => [
                        'data' => [[
                            'id' => 'si_123',
                            'price' => [
                                'id' => 'price_pulse',
                                'product' => 'prod_pulse',
                            ],
                            'quantity' => 1,
                        ]],
                    ],
                    'metadata' => [
                        'name' => 'default',
                    ],
                ],
            ],
        ];

        $this->postJson(route('stripe.webhook'), $payload)->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $manager->id,
            'type' => 'default',
            'stripe_id' => 'sub_123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pulse',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'tariff' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'tariff' => 1,
        ]);
    }
}
