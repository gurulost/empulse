<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_arbitrary_password_company_and_role_mutation_route_is_removed(): void
    {
        $attacker = User::factory()->create([
            'role' => 4,
            'company_id' => 10,
            'password' => Hash::make('attacker-password'),
        ]);
        $victim = User::factory()->create([
            'role' => 4,
            'company_id' => 20,
            'password' => Hash::make('victim-password'),
        ]);

        $this->actingAs($attacker)
            ->postJson("/home/updatePassword/{$victim->email}", [
                'name' => 'Attacker',
                'company_title' => 'Stolen Company',
                'new_password' => 'new-password',
            ])
            ->assertNotFound();

        $victim->refresh();
        $attacker->refresh();

        $this->assertSame(4, (int) $victim->role);
        $this->assertSame(20, (int) $victim->company_id);
        $this->assertTrue(Hash::check('victim-password', $victim->password));
        $this->assertSame(10, (int) $attacker->company_id);
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_legacy_destructive_and_support_routes_are_removed(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);
        $victim = User::factory()->create(['role' => 4]);

        $this->actingAs($admin)->get("/users/delete/{$victim->email}")->assertNotFound();
        $this->actingAs($admin)->get('/departments/delete/Engineering')->assertNotFound();
        $this->actingAs($admin)->get("/admin/users/delete/{$victim->id}")->assertNotFound();
        $this->actingAs($admin)->deleteJson("/admin/api/users/{$victim->id}")->assertNotFound();
        $this->actingAs($admin)->postJson("/admin/api/users/{$victim->id}/impersonate")->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_legacy_direct_roster_import_is_not_exposed(): void
    {
        $manager = User::factory()->create(['role' => 1, 'status' => 'active']);

        $this->assertContains(
            $this->actingAs($manager)->post('/team/api/members/import')->status(),
            [404, 405]
        );
        $this->actingAs($manager)
            ->post('/users/import')
            ->assertNotFound();
    }

    public function test_self_service_password_change_requires_current_password_and_ignores_privilege_fields(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Original Manager',
            'manager_email' => 'manager@example.com',
        ]);
        $otherCompany = Companies::create([
            'title' => 'Other',
            'manager' => 'Other Manager',
            'manager_email' => 'other@example.com',
        ]);
        $manager = User::factory()->create([
            'name' => 'Original Manager',
            'email' => 'manager@example.com',
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 1,
            'password' => Hash::make('current-password'),
        ]);

        DB::table('company_worker')->insert([
            'company_id' => $company->id,
            'name' => $manager->name,
            'email' => $manager->email,
            'company_title' => $company->title,
            'role' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'name' => 'Updated Manager',
            'email' => 'updated@example.com',
            'current_password' => 'wrong-password',
            'new_pass' => 'new-secure-password',
            'conf_new_pass' => 'new-secure-password',
            'role' => 0,
            'is_admin' => 1,
            'company_id' => $otherCompany->id,
            'company_title' => 'Hijacked',
        ];

        $this->actingAs($manager)
            ->postJson('/profile/edit_password', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The current password is incorrect.');

        $manager->refresh();
        $this->assertSame('Original Manager', $manager->name);
        $this->assertTrue(Hash::check('current-password', $manager->password));

        $payload['current_password'] = 'current-password';

        $this->actingAs($manager)
            ->postJson('/profile/edit_password', $payload)
            ->assertOk()
            ->assertJsonPath('status', 200);

        $manager->refresh();
        $this->assertSame('Updated Manager', $manager->name);
        $this->assertSame('updated@example.com', $manager->email);
        $this->assertSame(1, (int) $manager->role);
        $this->assertSame($company->id, (int) $manager->company_id);
        $this->assertSame('Acme', $manager->company_title);
        $this->assertTrue(Hash::check('new-secure-password', $manager->password));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'title' => 'Acme',
            'manager' => 'Updated Manager',
            'manager_email' => 'updated@example.com',
        ]);
        $this->assertDatabaseHas('companies', [
            'id' => $otherCompany->id,
            'title' => 'Other',
        ]);
        $this->assertDatabaseHas('company_worker', [
            'company_id' => $company->id,
            'name' => 'Updated Manager',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_avatar_removal_is_self_scoped_and_not_addressable_by_user_id(): void
    {
        $actor = User::factory()->create([
            'role' => 4,
            'image' => 'actor.png',
        ]);
        $victim = User::factory()->create([
            'role' => 4,
            'image' => 'victim.png',
        ]);

        $this->actingAs($actor)
            ->get("/delete/avatar/{$victim->id}")
            ->assertNotFound();

        $this->actingAs($actor)
            ->delete('/profile/avatar')
            ->assertRedirect();

        $this->assertNull($actor->fresh()->image);
        $this->assertSame('victim.png', $victim->fresh()->image);
    }

    public function test_avatar_upload_is_image_validated_normalized_and_self_scoped(): void
    {
        Storage::fake('public');
        $actor = User::factory()->create(['role' => 4]);

        $this->actingAs($actor)
            ->post(route('store.avatar'), [
                'image' => UploadedFile::fake()->image('avatar.png', 600, 400),
            ])
            ->assertRedirect(route('profile'));

        $path = $actor->fresh()->image;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('avatars/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($actor)
            ->post(route('store.avatar'), [
                'image' => UploadedFile::fake()->create(
                    'not-an-image.jpg',
                    10,
                    'text/plain'
                ),
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_routes_require_named_capabilities_instead_of_non_employee_status(): void
    {
        $manager = User::factory()->create(['role' => 1, 'is_admin' => 0]);
        $chief = User::factory()->create(['role' => 2, 'is_admin' => 0]);
        $unknownRole = User::factory()->create(['role' => 99, 'is_admin' => 0]);
        $workfitAdmin = User::factory()->create(['role' => 1, 'is_admin' => 1]);

        $this->actingAs($manager)->get('/admin')->assertForbidden();
        $this->actingAs($chief)->get('/account/billing')->assertForbidden();
        $this->actingAs($unknownRole)->get('/reports')->assertForbidden();
        $this->actingAs($workfitAdmin)->get('/team/manage')->assertForbidden();

        $this->assertFalse($manager->hasCapability('billing.manage'));
        $this->assertFalse($manager->hasCapability('workfit.admin'));
        $this->assertTrue($workfitAdmin->hasCapability('workfit.admin'));
        $this->assertTrue($workfitAdmin->hasCapability('analytics.view'));
        $this->assertFalse($workfitAdmin->hasCapability('team.manage'));
        $this->assertFalse($unknownRole->hasCapability('analytics.view'));
    }

    public function test_authentication_and_email_failures_do_not_expose_debug_or_provider_payloads(): void
    {
        $loginSource = file_get_contents(app_path('Http/Controllers/Auth/LoginController.php'));
        $this->assertIsString($loginSource);
        $this->assertStringNotContainsString('login_debug_error', $loginSource);
        $this->assertStringNotContainsString('getTraceAsString', $loginSource);
        $this->assertStringNotContainsString('return false', $loginSource);

        config(['services.brevo.key' => 'test-key']);
        Http::fake([
            'api.brevo.com/*' => Http::response(
                'provider-secret-body with SQLSTATE and token=do-not-expose',
                500,
                ['x-request-id' => 'safe-request-id']
            ),
        ]);
        $result = app(EmailService::class)->sendLetter(
            'respondent@example.test',
            'Respondent',
            'Test',
            '<p>Message</p>'
        );
        $encoded = json_encode($result, JSON_THROW_ON_ERROR);

        $this->assertSame(500, $result['status']);
        $this->assertSame('safe-request-id', $result['provider_request_id']);
        $this->assertStringNotContainsString('provider-secret-body', $encoded);
        $this->assertStringNotContainsString('SQLSTATE', $encoded);
        $this->assertStringNotContainsString('do-not-expose', $encoded);
    }
}
