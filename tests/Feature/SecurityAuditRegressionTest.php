<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unconfigured_stripe_webhook_fails_before_persisting_an_event(): void
    {
        config(['cashier.webhook.secret' => null]);

        $this->postJson('/stripe/webhook', [
            'id' => 'evt_untrusted_configuration_test',
            'type' => 'unrecognized.event',
            'data' => ['object' => []],
        ])->assertStatus(503);

        $this->assertDatabaseCount('billing_webhook_events', 0);
    }

    public function test_deactivated_account_cannot_keep_using_an_existing_session(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 4]);
        $this->actingAs($user)->getJson('/profile')->assertOk();

        $user->update(['status' => 'inactive']);

        $this->getJson('/profile')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_deactivated_administrator_cannot_keep_using_privileged_routes(): void
    {
        $admin = User::factory()->create(['status' => 'inactive', 'is_admin' => 1, 'role' => 0]);

        $this->actingAs($admin)->getJson('/admin/api/companies')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_pending_account_cannot_access_authenticated_pages(): void
    {
        $pending = User::factory()->create(['status' => 'pending']);

        $this->actingAs($pending)->get('/profile')->assertRedirect('/login');
        $this->assertGuest();
    }
}
