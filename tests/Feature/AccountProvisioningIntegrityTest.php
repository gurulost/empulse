<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountProvisioningIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_atomically_creates_company_owner_roster_and_membership(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Owner',
            'email' => 'owner@example.test',
            'company_title' => 'New Company',
            'password' => 'long-secure-password',
            'password_confirmation' => 'long-secure-password',
        ]);

        $response->assertRedirect('/home');
        $company = Companies::where('manager_email', 'owner@example.test')->sole();
        $user = User::where('email', 'owner@example.test')->sole();

        $this->assertSame($company->id, (int) $user->company_id);
        $this->assertDatabaseHas('company_worker', [
            'company_id' => $company->id,
            'email' => $user->email,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('organization_memberships', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'role' => 1,
            'status' => 'active',
            'valid_to' => null,
        ]);
        $this->assertDatabaseHas('organization_billing_admins', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_registration_rejects_short_password_without_partial_tenant(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'New Owner',
            'email' => 'owner@example.test',
            'company_title' => 'New Company',
            'password' => 'shortpass',
            'password_confirmation' => 'shortpass',
        ])->assertRedirect('/register')->assertSessionHasErrors('password');

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('company_worker', 0);
        $this->assertDatabaseCount('organization_memberships', 0);
    }

    public function test_social_login_does_not_silently_link_an_existing_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.test',
            'status' => 'active',
            'google_id' => null,
        ]);

        $result = app(SocialAuthService::class)->handleGoogleLogin(
            'provider-subject',
            $existing->email,
            $existing->name
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('password', $result['error']);
        $this->assertNull($existing->fresh()->google_id);
        $this->assertGuest();
    }

    public function test_inactive_social_identity_cannot_authenticate(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.test',
            'status' => 'inactive',
            'google_id' => 'provider-subject',
        ]);

        $result = app(SocialAuthService::class)->handleGoogleLogin(
            'provider-subject',
            'inactive@example.test',
            'Inactive'
        );

        $this->assertFalse($result['success']);
        $this->assertGuest();
    }

    public function test_new_social_identity_cannot_create_a_companyless_employee(): void
    {
        $result = app(SocialAuthService::class)->handleGoogleLogin(
            'new-provider-subject',
            'new-social@example.test',
            'New Social User'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('company workspace', $result['error']);
        $this->assertDatabaseMissing('users', [
            'email' => 'new-social@example.test',
        ]);
        $this->assertGuest();
    }
}
