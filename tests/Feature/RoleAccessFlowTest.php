<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_redirects_to_employee_dashboard_from_login_root_and_home(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $employee = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 4,
        ]);

        $this->actingAs($employee)->get('/login')->assertRedirect(route('employee.dashboard'));
        $this->actingAs($employee)->get('/')->assertRedirect(route('employee.dashboard'));
        $this->actingAs($employee)->get('/home')->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticatedAs($employee);
    }

    public function test_workfit_admin_impersonating_employee_redirects_to_employee_dashboard(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);

        $employee = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 4,
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/api/users/{$employee->id}/impersonate");

        $response->assertOk();
        $response->assertJsonFragment(['redirect' => route('employee.dashboard')]);
        $this->assertAuthenticatedAs($employee);
    }

    public function test_billing_page_loads_without_stripe_intent_when_stripe_is_not_configured(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 1,
        ]);

        config([
            'services.stripe.secret' => null,
            'services.stripe.key' => null,
        ]);

        $this->actingAs($manager)
            ->get('/account/billing')
            ->assertOk()
            ->assertSee('Card updates are unavailable until Stripe is configured for this environment.');
    }
}
