<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use App\Services\OrganizationEntitlementService;
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

    public function test_workfit_admin_cannot_impersonate_an_employee(): void
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

        $response->assertNotFound();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_workfit_admin_without_company_context_lands_on_operator_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
            'company_id' => null,
        ]);

        $this->actingAs($admin)
            ->get('/home')
            ->assertRedirect(route('admin.dashboard'));
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
        app(OrganizationEntitlementService::class)->ensureBillingOwner($company, $manager);

        config([
            'services.stripe.secret' => null,
            'services.stripe.key' => null,
        ]);

        $this->actingAs($manager)
            ->get('/account/billing')
            ->assertOk()
            ->assertSee('Card updates unavailable until Stripe is configured.');
    }
}
