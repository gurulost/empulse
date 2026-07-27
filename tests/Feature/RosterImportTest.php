<?php

namespace Tests\Feature;

use App\Jobs\ParseRosterImport;
use App\Jobs\SendAccountInvitation;
use App\Models\AuditEvent;
use App\Models\Companies;
use App\Models\RosterExternalIdentity;
use App\Models\RosterImport;
use App\Models\User;
use App\Services\AccountInvitationService;
use App\Services\OrganizationService;
use App\Services\RetentionService;
use App\Services\RosterImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RosterImportTest extends TestCase
{
    use RefreshDatabase;

    private Companies $company;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Companies::create([
            'title' => 'Import Company',
            'manager' => 'Import Manager',
            'manager_email' => 'manager@import.test',
        ]);
        $this->manager = User::create([
            'name' => 'Import Manager',
            'email' => 'manager@import.test',
            'password' => bcrypt('password'),
            'role' => 1,
            'company_id' => $this->company->id,
            'company_title' => $this->company->title,
            'tariff' => 1,
            'status' => 'active',
        ]);
        DB::table('company_worker')->insert([
            'company_id' => $this->company->id,
            'name' => $this->manager->name,
            'email' => $this->manager->email,
            'role' => 1,
            'department' => null,
            'status' => 'active',
        ]);
        DB::table('company_department')->insert([
            'company_id' => $this->company->id,
            'title' => 'Operations',
        ]);
        RosterExternalIdentity::create([
            'company_id' => $this->company->id,
            'external_id' => 'MGR-1',
            'external_id_normalized' => 'mgr-1',
            'user_id' => $this->manager->id,
        ]);
        app(OrganizationService::class)->synchronize($this->manager, $this->manager, null, null, 'active');
    }

    public function test_manager_previews_then_atomically_commits_create_update_and_deactivate_actions(): void
    {
        $update = $this->existingEmployee('EMP-1', 'Old Person', 'old@import.test');
        $deactivate = $this->existingEmployee('EMP-2', 'Leaving Person', 'leaving@import.test');
        $csv = <<<'CSV'
        external_id,name,email,role,department,supervisor_external_id,status
        LEAD-1,Operations Lead,lead@import.test,teamlead,Operations,MGR-1,active
        EMP-1,Updated Person,updated@import.test,employee,Operations,LEAD-1,active
        EMP-2,Leaving Person,leaving@import.test,employee,Operations,,inactive
        EMP-3,New Employee,new@import.test,employee,Operations,LEAD-1,active
        CSV;

        $preview = $this->actingAs($this->manager)
            ->post('/team/api/roster-imports', ['file' => $this->csv($csv)]);

        $preview->assertCreated()
            ->assertJsonPath('data.status', 'preview_ready')
            ->assertJsonPath('data.counts.create', 2)
            ->assertJsonPath('data.counts.update', 1)
            ->assertJsonPath('data.counts.deactivate', 1)
            ->assertJsonPath('data.counts.errors', 0);
        $this->assertSame(64, strlen((string) $preview->json('confirmation_token')));
        $this->assertDatabaseMissing('users', ['email' => 'new@import.test']);
        $this->assertSame('Old Person', $update->fresh()->name);
        $this->assertSame('active', $deactivate->fresh()->status);

        $commit = $this->actingAs($this->manager)->postJson(
            '/team/api/roster-imports/'.$preview->json('data.id').'/commit',
            ['confirmation_token' => $preview->json('confirmation_token')]
        );

        $commit->assertOk()->assertJsonPath('data.status', 'committed');
        $this->assertDatabaseHas('users', [
            'email' => 'lead@import.test',
            'company_id' => $this->company->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'new@import.test',
            'company_id' => $this->company->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $update->id,
            'name' => 'Updated Person',
            'email' => 'updated@import.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $deactivate->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('company_worker', [
            'email' => 'updated@import.test',
            'supervisor' => 'Operations Lead',
            'department' => 'Operations',
        ]);
        $this->assertDatabaseCount('account_invitations', 2);
        $this->assertDatabaseHas('account_invitations', [
            'email' => 'new@import.test',
            'delivery_status' => 'accepted',
        ]);
        $this->assertDatabaseHas('roster_external_identities', [
            'company_id' => $this->company->id,
            'external_id' => 'EMP-3',
        ]);
        $this->assertTrue(AuditEvent::where('action', 'roster.import_committed')->exists());

        $duplicate = $this->actingAs($this->manager)
            ->post('/team/api/roster-imports', ['file' => $this->csv($csv)]);
        $duplicate->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('data.status', 'committed')
            ->assertJsonPath('confirmation_token', null);
        $this->assertDatabaseCount('roster_imports', 1);
        $this->assertDatabaseCount('account_invitations', 2);
    }

    public function test_invalid_or_cross_tenant_rows_never_mutate_the_roster(): void
    {
        $otherCompany = Companies::create([
            'title' => 'Other Company',
            'manager' => 'Other',
            'manager_email' => 'other@company.test',
        ]);
        User::create([
            'name' => 'Other Identity',
            'email' => 'shared@identity.test',
            'password' => bcrypt('password'),
            'role' => 4,
            'company_id' => $otherCompany->id,
            'company_title' => $otherCompany->title,
            'status' => 'active',
        ]);
        $stable = $this->existingEmployee(
            'STABLE-1',
            'Stable Identity',
            'stable@import.test'
        );
        $csv = <<<'CSV'
        external_id,name,email,role,department,status
        DUP-1,First Person,shared@identity.test,employee,Missing Department,active
        DUP-1,Second Person,second@import.test,employee,Operations,active
        STABLE-2,Stable Identity,stable@import.test,employee,Operations,active
        MISSING-1,Missing Identity,missing@import.test,employee,Operations,inactive
        CSV;

        $preview = $this->actingAs($this->manager)
            ->post('/team/api/roster-imports', ['file' => $this->csv($csv)]);

        $preview->assertCreated()
            ->assertJsonPath('data.status', 'invalid')
            ->assertJsonPath('data.counts.errors', 4)
            ->assertJsonPath('confirmation_token', null);
        $this->assertStringContainsString(
            'another company',
            implode(' ', $preview->json('data.rows.0.errors'))
        );
        $this->assertDatabaseMissing('users', ['email' => 'second@import.test']);
        $this->assertStringContainsString(
            'stable external_id',
            implode(' ', $preview->json('data.rows.2.errors'))
        );
        $this->assertStringContainsString(
            'already exist',
            implode(' ', $preview->json('data.rows.3.errors'))
        );
        $this->assertSame('Stable Identity', $stable->fresh()->name);

        $this->actingAs($this->manager)->postJson(
            '/team/api/roster-imports/'.$preview->json('data.id').'/commit',
            ['confirmation_token' => str_repeat('x', 64)]
        )->assertConflict();
        $this->assertDatabaseMissing('users', ['email' => 'second@import.test']);
    }

    public function test_commit_rejects_a_stale_preview_before_any_row_is_applied(): void
    {
        $existing = $this->existingEmployee('EMP-10', 'Existing Person', 'existing@import.test');
        $csv = <<<'CSV'
        external_id,name,email,role,department,status
        NEW-10,New Before Conflict,new-before-conflict@import.test,employee,Operations,active
        EMP-10,Proposed Update,existing@import.test,employee,Operations,active
        CSV;
        $preview = $this->actingAs($this->manager)
            ->post('/team/api/roster-imports', ['file' => $this->csv($csv)]);
        $preview->assertCreated()->assertJsonPath('data.status', 'preview_ready');

        $existing->update(['name' => 'Concurrent Change']);

        $this->actingAs($this->manager)->postJson(
            '/team/api/roster-imports/'.$preview->json('data.id').'/commit',
            ['confirmation_token' => $preview->json('confirmation_token')]
        )->assertConflict();

        $this->assertDatabaseMissing('users', ['email' => 'new-before-conflict@import.test']);
        $this->assertSame('Concurrent Change', $existing->fresh()->name);
        $this->assertDatabaseHas('roster_imports', [
            'public_id' => $preview->json('data.id'),
            'status' => 'preview_ready',
        ]);
    }

    public function test_only_company_managers_can_access_roster_imports(): void
    {
        $employee = User::create([
            'name' => 'Ordinary Employee',
            'email' => 'ordinary@import.test',
            'password' => bcrypt('password'),
            'role' => 4,
            'company_id' => $this->company->id,
            'company_title' => $this->company->title,
            'status' => 'active',
        ]);
        $csv = <<<'CSV'
        external_id,name,email,role
        EMP-50,Attempted Person,attempted@import.test,employee
        CSV;

        $this->actingAs($employee)
            ->withHeader('Accept', 'application/json')
            ->post('/team/api/roster-imports', ['file' => $this->csv($csv)])
            ->assertForbidden();
        $this->assertDatabaseCount('roster_imports', 0);
    }

    public function test_larger_files_are_encrypted_and_queued_for_parsing(): void
    {
        Queue::fake();
        $rows = ['external_id,name,email,role,department,status'];
        foreach (range(1, 101) as $index) {
            $rows[] = "EMP-{$index},Employee {$index},employee{$index}@import.test,employee,Operations,active";
        }
        $csv = implode("\n", $rows);

        $response = $this->actingAs($this->manager)
            ->post('/team/api/roster-imports', ['file' => $this->csv($csv)]);

        $response->assertAccepted()
            ->assertJsonPath('queued', true)
            ->assertJsonPath('data.status', 'parsing');
        Queue::assertPushed(ParseRosterImport::class);

        $import = RosterImport::firstOrFail();
        $storedCiphertext = DB::table('roster_imports')->where('id', $import->id)->value('source_csv');
        $this->assertNotSame($csv, $storedCiphertext);
        $this->assertSame($csv."\n", $import->source_csv);
        $this->assertArrayNotHasKey('source_csv', $response->json('data'));

        app(RosterImportService::class)->parse($import);
        $this->actingAs($this->manager)
            ->getJson('/team/api/roster-imports/'.$import->public_id)
            ->assertOk()
            ->assertJsonPath('data.status', 'preview_ready')
            ->assertJsonPath('data.counts.create', 101);
        $this->actingAs($this->manager)
            ->postJson('/team/api/roster-imports/'.$import->public_id.'/confirmation-token')
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->whereType('confirmation_token', 'string')
                ->etc());
        $this->assertNull($import->fresh()->source_csv);
    }

    public function test_review_rows_are_purged_by_the_hash_gated_retention_workflow(): void
    {
        $csv = <<<'CSV'
        external_id,name,email,role,department,status
        RET-1,Retention Person,retention@import.test,employee,Operations,active
        CSV;
        $preview = $this->actingAs($this->manager)
            ->post('/team/api/roster-imports', ['file' => $this->csv($csv)]);
        $preview->assertCreated()->assertJsonPath('data.status', 'preview_ready');

        $import = RosterImport::where('public_id', $preview->json('data.id'))->firstOrFail();
        $import->update(['parsed_at' => now()->subDays(31)]);
        $this->assertDatabaseCount('roster_import_rows', 1);

        $retention = app(RetentionService::class);
        $plan = $retention->plan();
        $this->assertContains($import->id, $plan['targets']['roster_import_ids']);
        $run = $retention->recordPlan($plan, false);
        $result = $retention->execute($run, $run->plan_hash);

        $this->assertSame(1, $result['roster_imports']);
        $this->assertSame(1, $result['roster_import_rows']);
        $this->assertDatabaseCount('roster_imports', 1);
        $this->assertDatabaseCount('roster_import_rows', 0);
        $this->assertSame('[purged]', $import->fresh()->original_filename);
        $this->assertSame('expired', $import->fresh()->status);
        $this->assertNull($import->fresh()->confirmation_token_hash);
        $this->assertNotNull($import->fresh()->rows_purged_at);
        $this->actingAs($this->manager)
            ->postJson('/team/api/roster-imports/'.$import->public_id.'/confirmation-token')
            ->assertStatus(410);
        $this->actingAs($this->manager)
            ->get('/team/api/roster-imports/'.$import->public_id.'/result.csv')
            ->assertStatus(410);
    }

    public function test_failed_account_invitation_delivery_has_report_only_and_execute_recovery_modes(): void
    {
        Queue::fake();
        $user = User::create([
            'name' => 'Recovery Person',
            'email' => 'recovery@import.test',
            'password' => bcrypt('password'),
            'role' => 4,
            'company_id' => $this->company->id,
            'company_title' => $this->company->title,
            'status' => 'pending',
        ]);
        $issued = app(AccountInvitationService::class)->issue($user, $this->manager);
        $issued['invitation']->update([
            'delivery_status' => 'failed',
            'delivery_attempts' => 1,
            'delivery_last_attempt_at' => now()->subMinutes(16),
        ]);

        $this->artisan('account:invitations:recover')
            ->expectsOutputToContain('"eligible": 1')
            ->expectsOutputToContain('Report only')
            ->assertSuccessful();
        Queue::assertNotPushed(SendAccountInvitation::class);

        $this->artisan('account:invitations:recover', ['--execute' => true])
            ->expectsOutputToContain('Queued 1 account invitation')
            ->assertSuccessful();
        Queue::assertPushed(
            SendAccountInvitation::class,
            fn (SendAccountInvitation $job): bool => $job->invitationId === $issued['invitation']->id
        );
    }

    private function existingEmployee(string $externalId, string $name, string $email): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 4,
            'company_id' => $this->company->id,
            'company_title' => $this->company->title,
            'status' => 'active',
        ]);
        DB::table('company_worker')->insert([
            'company_id' => $this->company->id,
            'name' => $name,
            'email' => $email,
            'role' => 4,
            'department' => 'Operations',
            'status' => 'active',
        ]);
        RosterExternalIdentity::create([
            'company_id' => $this->company->id,
            'external_id' => $externalId,
            'external_id_normalized' => mb_strtolower($externalId),
            'user_id' => $user->id,
        ]);
        app(OrganizationService::class)->synchronize(
            $user,
            $this->manager,
            'Operations',
            $this->manager->email,
            'active'
        );

        return $user;
    }

    private function csv(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('roster.csv', $contents."\n");
    }
}
