<?php

namespace Tests\Feature;

use App\Models\AdvisorWorkItem;
use App\Models\AdvisorWorkspaceNote;
use App\Models\AuditEvent;
use App\Models\Companies;
use App\Models\User;
use App\Services\AdvisorAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_workspace_notes_enforce_customer_shared_and_workfit_internal_visibility(): void
    {
        $company = Companies::create([
            'title' => 'Learning Co',
            'manager' => 'Customer Owner',
            'manager_email' => 'owner@learning.test',
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
        app(AdvisorAccessService::class)->grant(
            $company->id,
            $advisor,
            $owner,
            'Support interpretation while keeping note visibility explicit.',
            now()->addMonth()
        );

        $this->actingAs($owner)
            ->post(route('actions.notes.store'), [
                'visibility' => 'customer_shared',
                'body' => 'Customer and advisor can review this decision context.',
            ])
            ->assertRedirect(route('actions.index'));

        $this->actingAs($advisor)
            ->post(route('actions.notes.store'), [
                'company_id' => $company->id,
                'visibility' => 'workfit_internal',
                'body' => 'Internal preparation note that must not reach the customer.',
            ])
            ->assertRedirect(route('actions.index', ['company_id' => $company->id]));

        $this->actingAs($owner)
            ->get(route('actions.index'))
            ->assertOk()
            ->assertSee('Customer and advisor can review this decision context.')
            ->assertDontSee('Internal preparation note that must not reach the customer.');

        $this->actingAs($advisor)
            ->get(route('actions.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertSee('Customer and advisor can review this decision context.')
            ->assertSee('Internal preparation note that must not reach the customer.')
            ->assertSee('WorkFit internal');

        $this->actingAs($owner)
            ->post(route('actions.notes.store'), [
                'visibility' => 'workfit_internal',
                'body' => 'A customer must not create an internal-only note.',
            ])
            ->assertForbidden();
        $this->assertDatabaseMissing('advisor_workspace_notes', [
            'body' => 'A customer must not create an internal-only note.',
        ]);

        $audit = AuditEvent::where('action', 'advisor_note.created')
            ->where('company_id', $company->id)
            ->latest('id')
            ->firstOrFail();
        $this->assertSame('workfit_internal', $audit->metadata['visibility']);
        $this->assertArrayNotHasKey('body', $audit->metadata);
        $this->assertStringNotContainsString(
            'Internal preparation note',
            json_encode([$audit->changes, $audit->metadata], JSON_THROW_ON_ERROR)
        );
    }

    public function test_workspace_notes_are_append_only(): void
    {
        $company = Companies::create([
            'title' => 'Append Only Co',
            'manager' => 'Owner',
            'manager_email' => 'owner@append.test',
        ]);
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'role' => 1,
            'status' => 'active',
        ]);
        $this->actingAs($owner)->post(route('actions.notes.store'), [
            'visibility' => 'customer_shared',
            'body' => 'Original note.',
        ])->assertRedirect();
        $note = AdvisorWorkspaceNote::firstOrFail();

        try {
            $note->update(['body' => 'Changed note.']);
            $this->fail('Workspace note update should have been rejected.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('append-only');
        $note->delete();
    }

    public function test_advisor_queue_is_grant_scoped_sanitized_and_audited(): void
    {
        $company = Companies::create([
            'title' => 'Queue Co',
            'manager' => 'Owner',
            'manager_email' => 'owner@queue.test',
        ]);
        $otherCompany = Companies::create([
            'title' => 'Hidden Queue Co',
            'manager' => 'Other',
            'manager_email' => 'other@queue.test',
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
        $grant = app(AdvisorAccessService::class)->grant(
            $company->id,
            $advisor,
            $owner,
            'Operate the customer-approved advisory work queue.',
            now()->addMonth()
        );
        $visible = AdvisorWorkItem::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'kind' => 'finding_review',
            'priority' => 'high',
            'status' => 'open',
            'context' => ['private_detail' => 'must not enter the API response'],
        ]);
        AdvisorWorkItem::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $otherCompany->id,
            'kind' => 'finding_review',
            'priority' => 'urgent',
            'status' => 'open',
        ]);

        $response = $this->actingAs($advisor)
            ->getJson('/admin/api/advisor-work-items?kind=finding_review')
            ->assertOk()
            ->assertJsonCount(1, 'items.data')
            ->assertJsonPath('items.data.0.company.id', $company->id)
            ->assertJsonPath('items.data.0.id', $visible->id)
            ->assertJsonMissingPath('items.data.0.context');
        $this->assertStringNotContainsString(
            'private_detail',
            $response->getContent()
        );

        $this->actingAs($advisor)
            ->patchJson("/admin/api/advisor-work-items/{$visible->id}", [
                'status' => 'claimed',
            ])
            ->assertOk()
            ->assertJsonPath('data.assigned_to_user_id', $advisor->id);
        $this->actingAs($advisor)
            ->patchJson("/admin/api/advisor-work-items/{$visible->id}", [
                'status' => 'completed',
            ])
            ->assertOk();
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'action' => 'advisor.work_item_status_changed',
            'subject_id' => (string) $visible->id,
        ]);

        $this->actingAs($owner)
            ->getJson('/admin/api/advisor-work-items')
            ->assertForbidden();

        app(AdvisorAccessService::class)->revoke($grant, $owner);
        $this->actingAs($advisor)
            ->getJson('/admin/api/advisor-work-items')
            ->assertOk()
            ->assertJsonCount(0, 'items.data');
    }
}
