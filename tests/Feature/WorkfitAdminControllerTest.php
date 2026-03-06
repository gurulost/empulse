<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkfitAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_search_respects_company_filter(): void
    {
        $companyA = Companies::create([
            'title' => 'Alpha',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $companyB = Companies::create([
            'title' => 'Beta',
            'manager' => 'Manager B',
            'manager_email' => 'manager-b@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);

        $alphaChief = User::factory()->create([
            'name' => 'Alice Alpha',
            'email' => 'alice.alpha@example.com',
            'role' => 2,
            'company_id' => $companyA->id,
        ]);

        User::factory()->create([
            'name' => 'Alice Beta',
            'email' => 'alice.beta@example.com',
            'role' => 2,
            'company_id' => $companyB->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/admin/api/users?search=alice&company_id={$companyA->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'email' => $alphaChief->email,
            'company_id' => $companyA->id,
        ]);
        $response->assertJsonMissing([
            'email' => 'alice.beta@example.com',
            'company_id' => $companyB->id,
        ]);
    }

    public function test_admin_can_fetch_onboarding_report_summary(): void
    {
        $company = Companies::create([
            'title' => 'Alpha',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);

        DB::table('onboarding_events')->insert([
            [
                'company_id' => $company->id,
                'name' => 'session_started',
                'context_surface' => 'dashboard.analytics',
                'task_id' => 'company_activation',
                'created_at' => now()->subHours(3),
            ],
            [
                'company_id' => $company->id,
                'name' => 'first_wave_dispatched',
                'context_surface' => 'survey-waves',
                'task_id' => 'wave_dispatch',
                'created_at' => now()->subHours(2),
            ],
            [
                'company_id' => $company->id,
                'name' => 'first_response_completed',
                'context_surface' => 'survey',
                'task_id' => 'first_response',
                'created_at' => now()->subHour(),
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/api/onboarding');

        $response->assertOk()
            ->assertJsonPath('summary.companies_total', 1)
            ->assertJsonPath('system_status.has_live_survey', true)
            ->assertJsonPath('system_status.survey_content_owner', 'workfit_admin')
            ->assertJsonPath('summary.companies_started', 1)
            ->assertJsonPath('summary.companies_dispatched', 1)
            ->assertJsonPath('summary.companies_responded', 1)
            ->assertJsonPath('summary.actionable_alerts', 0)
            ->assertJsonCount(4, 'stage_breakdown')
            ->assertJsonPath('companies.data.0.title', 'Alpha')
            ->assertJsonPath('companies.data.0.stage.label', 'Live Data');
    }

    public function test_onboarding_report_search_filters_companies(): void
    {
        $alpha = Companies::create([
            'title' => 'Alpha Labs',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $beta = Companies::create([
            'title' => 'Beta Ops',
            'manager' => 'Manager B',
            'manager_email' => 'manager-b@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);

        DB::table('onboarding_events')->insert([
            [
                'company_id' => $alpha->id,
                'name' => 'session_started',
                'context_surface' => 'dashboard.analytics',
                'task_id' => 'company_activation',
                'created_at' => now()->subDay(),
            ],
            [
                'company_id' => $beta->id,
                'name' => 'session_started',
                'context_surface' => 'dashboard.analytics',
                'task_id' => 'company_activation',
                'created_at' => now()->subDay(),
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/api/onboarding?search=Alpha');

        $response->assertOk()
            ->assertJsonCount(1, 'companies.data')
            ->assertJsonPath('companies.data.0.title', 'Alpha Labs');
    }

    public function test_onboarding_report_stage_filter_keeps_operational_alerts_and_plan_cohorts(): void
    {
        $startedCompany = Companies::create([
            'title' => 'Alpha Launch',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $liveCompany = Companies::create([
            'title' => 'Beta Live',
            'manager' => 'Manager B',
            'manager_email' => 'manager-b@example.com',
        ]);

        User::factory()->create([
            'name' => 'Manager A',
            'email' => 'manager-a@example.com',
            'role' => 1,
            'company_id' => $startedCompany->id,
            'tariff' => 1,
            'created_at' => now()->subDays(5),
        ]);

        User::factory()->create([
            'name' => 'Manager B',
            'email' => 'manager-b@example.com',
            'role' => 1,
            'company_id' => $liveCompany->id,
            'tariff' => 0,
            'created_at' => now()->subDays(5),
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);

        DB::table('onboarding_events')->insert([
            [
                'company_id' => $startedCompany->id,
                'name' => 'session_started',
                'context_surface' => 'dashboard.analytics',
                'task_id' => 'company_activation',
                'created_at' => now()->subDays(3),
            ],
            [
                'company_id' => $liveCompany->id,
                'name' => 'session_started',
                'context_surface' => 'dashboard.analytics',
                'task_id' => 'company_activation',
                'created_at' => now()->subDays(2),
            ],
            [
                'company_id' => $liveCompany->id,
                'name' => 'first_wave_dispatched',
                'context_surface' => 'survey-waves',
                'task_id' => 'wave_dispatch',
                'created_at' => now()->subHours(36),
            ],
            [
                'company_id' => $liveCompany->id,
                'name' => 'first_response_completed',
                'context_surface' => 'survey',
                'task_id' => 'first_response',
                'created_at' => now()->subHours(24),
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/api/onboarding?stage=started');

        $response->assertOk()
            ->assertJsonPath('filters.stage', 'started')
            ->assertJsonCount(1, 'companies.data')
            ->assertJsonPath('companies.data.0.title', 'Alpha Launch')
            ->assertJsonPath('alerts.0.key', 'first_wave_delayed')
            ->assertJsonFragment([
                'label' => 'Pulse (Drip Enabled)',
                'count' => 1,
            ])
            ->assertJsonFragment([
                'label' => 'Starter',
                'count' => 1,
            ]);
    }

    public function test_onboarding_report_flags_dormant_companies_from_manager_account_age(): void
    {
        $company = Companies::create([
            'title' => 'Gamma Quiet',
            'manager' => 'Manager G',
            'manager_email' => 'manager-g@example.com',
        ]);

        User::factory()->create([
            'name' => 'Manager G',
            'email' => 'manager-g@example.com',
            'role' => 1,
            'company_id' => $company->id,
            'tariff' => 0,
            'created_at' => now()->subDays(4),
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/api/onboarding?stage=dormant');

        $response->assertOk()
            ->assertJsonPath('filters.stage', 'dormant')
            ->assertJsonPath('companies.data.0.title', 'Gamma Quiet')
            ->assertJsonPath('alerts.0.key', 'no_session_started')
            ->assertJsonPath('alerts.0.stage.label', 'Dormant');
    }

    public function test_onboarding_report_exposes_global_survey_content_blocker_when_no_live_survey_exists(): void
    {
        SurveyVersion::query()->update(['is_active' => false]);

        Companies::create([
            'title' => 'Alpha',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        Companies::create([
            'title' => 'Beta',
            'manager' => 'Manager B',
            'manager_email' => 'manager-b@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/api/onboarding');

        $response->assertOk()
            ->assertJsonPath('system_status.has_live_survey', false)
            ->assertJsonPath('system_status.survey_content_owner', 'workfit_admin')
            ->assertJsonPath('system_status.blocking_companies_count', 2)
            ->assertJsonPath('system_status.live_survey', null);
    }
}
