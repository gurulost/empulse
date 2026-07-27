<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use App\Services\AdvisorAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvisorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_controls_time_bounded_advisor_access_to_action_workspace(): void
    {
        $company = Companies::create([
            'title' => 'Customer Co',
            'manager' => 'Customer Owner',
            'manager_email' => 'owner@customer.test',
        ]);
        $otherCompany = Companies::create([
            'title' => 'Other Co',
            'manager' => 'Other Owner',
            'manager_email' => 'owner@other.test',
        ]);
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'role' => 1,
            'status' => 'active',
        ]);
        $advisor = User::factory()->create([
            'company_id' => null,
            'role' => 0,
            'is_admin' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($advisor)
            ->get(route('actions.index', ['company_id' => $company->id]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('actions.advisors.store'), [
                'advisor_user_id' => $advisor->id,
                'purpose' => 'Support leadership interpretation and action planning.',
                'valid_until' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect(route('actions.index'));

        $grant = app(AdvisorAccessService::class)->activeForAdvisor($advisor)->firstOrFail();
        $this->assertSame($company->id, $grant->company_id);
        $this->actingAs($advisor)
            ->get(route('actions.index', ['company_id' => $company->id]))
            ->assertOk();
        $this->actingAs($advisor)
            ->get(route('actions.index', ['company_id' => $otherCompany->id]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('actions.advisors.destroy', $grant))
            ->assertRedirect(route('actions.index'));
        $this->actingAs($advisor)
            ->get(route('actions.index', ['company_id' => $company->id]))
            ->assertForbidden();
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'action' => 'advisor_access.revoked',
        ]);
    }

    public function test_advisor_cannot_self_grant_or_extend_access(): void
    {
        $company = Companies::create([
            'title' => 'Protected Co',
            'manager' => 'Owner',
            'manager_email' => 'owner@protected.test',
        ]);
        $advisor = User::factory()->create([
            'company_id' => null,
            'role' => 0,
            'is_admin' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($advisor)
            ->post(route('actions.advisors.store'), [
                'company_id' => $company->id,
                'advisor_user_id' => $advisor->id,
                'purpose' => 'Attempt to grant access without customer approval.',
            ])
            ->assertForbidden();
        $this->assertDatabaseCount('advisor_company_grants', 0);
    }
}
