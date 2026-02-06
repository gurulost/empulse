<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteResilienceMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_without_company_gets_explicit_empty_state_on_team_manage(): void
    {
        $manager = User::factory()->create([
            'role' => 1,
            'company_id' => null,
        ]);

        $this->actingAs($manager)
            ->get('/team/manage')
            ->assertOk()
            ->assertSee('No company context found for your account. Team Management is available after assigning a company.');
    }

    public function test_company_scoped_pages_do_not_crash_for_manager_without_company_context(): void
    {
        $manager = User::factory()->create([
            'role' => 1,
            'company_id' => null,
        ]);

        $this->actingAs($manager)->get('/dashboard/analytics')->assertOk();
        $this->actingAs($manager)->get('/reports')->assertOk();
        $this->actingAs($manager)->get('/surveys/manage')->assertOk();
        $this->actingAs($manager)->get('/survey-waves')->assertOk();
    }

    public function test_workfit_admin_without_company_context_can_load_selector_pages(): void
    {
        Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $workfitAdmin = User::factory()->create([
            'role' => 1,
            'is_admin' => 1,
            'company_id' => null,
        ]);

        $this->actingAs($workfitAdmin)
            ->get('/dashboard/analytics')
            ->assertOk()
            ->assertSee('analytics-dashboard', false);

        $this->actingAs($workfitAdmin)
            ->get('/reports')
            ->assertOk()
            ->assertSee('reports-dashboard-root');
    }

    public function test_employee_is_redirected_away_from_admin_dashboard_routes(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
        ]);

        $this->actingAs($employee)->get('/dashboard/analytics')->assertRedirect(route('employee.dashboard'));
        $this->actingAs($employee)->get('/reports')->assertRedirect(route('employee.dashboard'));
        $this->actingAs($employee)->get('/team/manage')->assertRedirect(route('employee.dashboard'));
    }
}

