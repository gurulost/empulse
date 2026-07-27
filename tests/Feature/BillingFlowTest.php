<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Plan;
use App\Models\User;
use App\Services\OrganizationEntitlementService;
use App\Support\CompanyBilling;
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
        app(OrganizationEntitlementService::class)->ensureBillingOwner($company, $manager);

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
        app(OrganizationEntitlementService::class)->ensureBillingOwner($company, $manager);

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

    public function test_stripe_subscription_webhook_syncs_company_owned_entitlement(): void
    {
        config(['billing.catalog.pulse.stripe_price' => 'price_pulse']);
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
            'stripe_id' => 'cus_test_123',
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 0,
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'tariff' => 0,
        ]);

        $payload = [
            'id' => 'evt_123',
            'type' => 'customer.subscription.created',
            'created' => now()->timestamp,
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
            'user_id' => $company->id,
            'type' => 'default',
            'stripe_id' => 'sub_123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pulse',
        ]);

        $this->assertDatabaseHas('organization_entitlements', [
            'company_id' => $company->id,
            'plan_key' => 'pulse',
            'status' => 'active',
            'source' => 'stripe',
            'stripe_subscription_id' => 'sub_123',
        ]);
        $this->assertDatabaseHas('billing_webhook_events', [
            'stripe_event_id' => 'evt_123',
            'company_id' => $company->id,
            'status' => 'processed',
        ]);
        $this->assertSame(0, (int) $manager->fresh()->tariff);
        $this->assertSame(0, (int) $employee->fresh()->tariff);

        $this->postJson(route('stripe.webhook'), $payload)->assertOk();
        $this->assertDatabaseCount('billing_webhook_events', 1);
        $this->assertDatabaseCount('subscriptions', 1);

        $staleDeletion = [
            'id' => 'evt_stale_delete',
            'type' => 'customer.subscription.deleted',
            'created' => now()->subMinute()->timestamp,
            'data' => [
                'object' => [
                    'id' => 'sub_123',
                    'customer' => 'cus_test_123',
                ],
            ],
        ];
        $this->postJson(route('stripe.webhook'), $staleDeletion)->assertOk();
        $this->assertDatabaseHas('billing_webhook_events', [
            'stripe_event_id' => 'evt_stale_delete',
            'status' => 'ignored_stale',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_123',
            'stripe_status' => 'active',
        ]);
    }

    public function test_stripe_webhook_rejects_unsigned_payloads_when_secret_is_configured(): void
    {
        config(['cashier.webhook.secret' => 'whsec_release_test']);

        $this->postJson(route('stripe.webhook'), [
            'id' => 'evt_unsigned',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => []],
        ])->assertForbidden();

        $this->assertDatabaseMissing('billing_webhook_events', [
            'stripe_event_id' => 'evt_unsigned',
        ]);
    }

    public function test_entitlement_survives_manager_changes_and_only_billing_admins_manage_it(): void
    {
        $company = Companies::create([
            'title' => 'Durable Co',
            'manager' => 'First Manager',
            'manager_email' => 'first@example.test',
        ]);
        $first = User::factory()->create([
            'email' => 'first@example.test',
            'company_id' => $company->id,
            'role' => 1,
            'status' => 'active',
        ]);
        $second = User::factory()->create([
            'email' => 'second@example.test',
            'company_id' => $company->id,
            'role' => 1,
            'status' => 'active',
        ]);
        $entitlements = app(OrganizationEntitlementService::class);
        $entitlements->ensureBillingOwner($company, $first);
        $entitlements->grantManual($company, 'pulse', now()->addMonth(), $first);

        $first->update(['role' => 4, 'status' => 'inactive']);
        $company->update([
            'manager' => $second->name,
            'manager_email' => $second->email,
        ]);

        $this->assertTrue(CompanyBilling::allowsScheduling($company->id));
        $this->assertTrue(CompanyBilling::hasFeature($company->id, 'recurring_waves'));
        $this->assertDatabaseHas('organization_entitlements', [
            'company_id' => $company->id,
            'plan_key' => 'pulse',
            'status' => 'manual_grant',
        ]);

        $this->actingAs($second)->get(route('billing.index'))->assertForbidden();
        $entitlements->ensureBillingOwner($company, $second);
        $this->actingAs($second)->get(route('billing.index'))->assertOk();
    }

    public function test_expired_manual_grant_stops_dispatch_and_usage_is_idempotent(): void
    {
        $company = Companies::create([
            'title' => 'Usage Co',
            'manager' => 'Manager',
            'manager_email' => 'manager@usage.test',
        ]);
        $entitlements = app(OrganizationEntitlementService::class);
        $grant = $entitlements->grantManual($company, 'pulse', now()->addDay());
        $grant->update(['ends_at' => now()->subSecond()]);

        $this->assertFalse(CompanyBilling::allowsScheduling($company->id));
        $entitlements->recordUsage(
            $company->id,
            'wave:1:respondents',
            'active_respondents',
            10,
            'respondents',
            ['wave_id' => 1]
        );
        $entitlements->recordUsage(
            $company->id,
            'wave:1:respondents',
            'active_respondents',
            10,
            'respondents',
            ['wave_id' => 1]
        );

        $this->assertDatabaseCount('organization_usage_events', 1);
    }

    public function test_billing_owner_transfer_requires_current_owner_request_and_target_acceptance(): void
    {
        $company = Companies::create([
            'title' => 'Continuity Co',
            'manager' => 'Owner',
            'manager_email' => 'owner@continuity.test',
        ]);
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'role' => 1,
            'status' => 'active',
        ]);
        $target = User::factory()->create([
            'company_id' => $company->id,
            'role' => 2,
            'status' => 'active',
        ]);
        $outsider = User::factory()->create([
            'company_id' => $company->id,
            'role' => 3,
            'status' => 'active',
        ]);
        $service = app(OrganizationEntitlementService::class);
        $service->ensureBillingOwner($company, $owner);

        $transfer = $service->initiateBillingOwnerTransfer(
            $company,
            $owner,
            $target,
            'The finance lead is taking ownership.'
        );
        $this->assertTrue($service->isBillingOwner($owner, $company));
        $this->assertFalse($service->isBillingOwner($target, $company));

        try {
            $service->decideBillingOwnerTransfer($transfer, $outsider, true);
            $this->fail('A third party should not accept a transfer.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('not available', $exception->getMessage());
        }

        $accepted = $service->decideBillingOwnerTransfer($transfer->fresh(), $target, true);
        $this->assertSame('accepted', $accepted->status);
        $this->assertTrue($service->isBillingOwner($target, $company));
        $this->assertFalse($service->isBillingOwner($owner, $company));
        $this->assertTrue($service->isBillingAdmin($owner, $company));
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'action' => 'billing.owner_transfer.accepted',
        ]);
    }

    public function test_active_respondent_limit_is_transparent_and_idempotent(): void
    {
        config(['billing.catalog.starter.limits.active_respondents' => 2]);
        $company = Companies::create([
            'title' => 'Limited Co',
            'manager' => 'Owner',
            'manager_email' => 'owner@limited.test',
        ]);
        $service = app(OrganizationEntitlementService::class);
        $service->grantManual($company, 'starter', now()->addMonth());

        $callbacks = 0;
        $consume = function (int $userId) use ($company, $service, &$callbacks): bool {
            return $service->consumeActiveRespondent(
                $company->id,
                $userId,
                function () use (&$callbacks): void {
                    $callbacks++;
                }
            );
        };

        $this->assertTrue($consume(10));
        $this->assertTrue($consume(10));
        $this->assertTrue($consume(11));
        $this->assertFalse($consume(12));
        $this->assertSame(3, $callbacks);

        $summary = $service->usageSummary($company);
        $this->assertSame(2.0, $summary['metrics']['active_respondents']);
        $this->assertSame(2, $summary['limits']['active_respondents']);
        $this->assertDatabaseCount('organization_usage_events', 2);
    }
}
