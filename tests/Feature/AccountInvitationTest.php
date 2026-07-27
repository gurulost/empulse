<?php

namespace Tests\Feature;

use App\Models\AccountInvitation;
use App\Models\Companies;
use App\Models\User;
use App\Services\AccountInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_user_activates_with_expiring_single_use_invitation(): void
    {
        $company = Companies::create([
            'title' => 'Invitation Co',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);
        $manager = User::factory()->create([
            'role' => 1,
            'company_id' => $company->id,
        ]);
        $user = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'email' => 'invitee@example.com',
            'status' => 'pending',
            'password' => Hash::make('unknown-random-secret'),
        ]);

        $issued = app(AccountInvitationService::class)->issue($user, $manager);
        $plainTextToken = $issued['token'];

        $this->assertNotSame(
            $plainTextToken,
            $issued['invitation']->fresh()->token_hash
        );

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'unknown-random-secret',
        ])->assertSessionHasErrors('email');

        $this->get(route('invitations.show', $plainTextToken))
            ->assertOk()
            ->assertSee('Empulse never sends temporary passwords by email');

        $this->post(route('invitations.accept', $plainTextToken), [
            'password' => 'a-secure-new-password',
            'password_confirmation' => 'a-secure-new-password',
        ])->assertRedirect(route('employee.dashboard'));

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('a-secure-new-password', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('account_invitations', [
            'id' => $issued['invitation']->id,
            'status' => 'accepted',
        ]);

        auth()->logout();
        $this->get(route('invitations.show', $plainTextToken))->assertStatus(410);
    }

    public function test_expired_and_revoked_invitations_fail_closed(): void
    {
        $user = User::factory()->create([
            'role' => 4,
            'status' => 'pending',
        ]);
        $service = app(AccountInvitationService::class);

        $expired = $service->issue($user);
        AccountInvitation::whereKey($expired['invitation']->id)
            ->update(['expires_at' => now()->subMinute()]);
        $this->get(route('invitations.show', $expired['token']))->assertStatus(410);

        $revoked = $service->issue($user);
        AccountInvitation::whereKey($revoked['invitation']->id)
            ->update([
                'status' => 'revoked',
                'revoked_at' => now(),
            ]);
        $this->get(route('invitations.show', $revoked['token']))->assertStatus(410);
    }

    public function test_invitation_email_template_never_renders_a_password_or_survey_assignment(): void
    {
        $html = view('admin-msg', [
            'name' => 'Invitee',
            'status' => 'employee',
            'company' => 'Invitation Co',
            'email' => 'invitee@example.com',
            'password' => 'TOP-SECRET-PASSWORD',
            'setupLink' => 'https://example.com/invitation/token',
            'department' => null,
            'teamlead' => null,
            'surveyLink' => 'https://example.com/survey/token',
        ])->render();

        $this->assertStringNotContainsString('TOP-SECRET-PASSWORD', $html);
        $this->assertStringNotContainsString('/survey/token', $html);
        $this->assertStringContainsString('/invitation/token', $html);
    }
}
