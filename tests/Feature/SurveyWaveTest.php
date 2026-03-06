<?php

namespace Tests\Feature;

use App\Jobs\ProcessSurveyWave;
use App\Jobs\SendSurveyAssignmentInvitation;
use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveLog;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SurveyService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SurveyWaveTest extends TestCase
{
    use RefreshDatabase;

    protected function createSurveyArtifacts(): array
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $survey = Survey::create([
            'company_id' => $company->id,
            'title' => 'Org Survey',
            'is_default' => true,
        ]);

        $version = SurveyVersion::create([
            'instrument_id' => 'test',
            'version' => '1.0.0',
            'title' => 'Org Survey v1',
            'is_active' => true,
        ]);

        return [$company, $survey, $version];
    }

    public function test_non_premium_user_cannot_create_drip_wave(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $user = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('survey-waves.store'), [
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'drip',
            'label' => 'Pulse',
            'target_roles' => [4],
            'status' => 'scheduled',
            'cadence' => 'monthly',
        ]);

        $this->assertDatabaseCount('survey_waves', 0);
        $response->assertSessionHasErrors('cadence');
    }

    public function test_manager_without_company_context_sees_blocked_state_and_cannot_create_wave(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('survey-waves.index'))
            ->assertOk()
            ->assertSee('No company context yet');

        $this->actingAs($manager)
            ->post(route('survey-waves.store'), [
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'kind' => 'full',
                'label' => 'Blocked Wave',
                'target_roles' => [4],
                'status' => 'scheduled',
                'cadence' => 'manual',
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('survey_waves', 0);
    }

    public function test_wave_creation_requires_an_active_survey_version(): void
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $survey = Survey::create([
            'company_id' => $company->id,
            'title' => 'Org Survey',
            'is_default' => true,
        ]);

        $draftVersion = SurveyVersion::create([
            'instrument_id' => 'test',
            'version' => '0.9.0',
            'title' => 'Draft Survey',
            'is_active' => false,
        ]);

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $this->actingAs($manager)
            ->post(route('survey-waves.store'), [
                'survey_id' => $survey->id,
                'survey_version_id' => $draftVersion->id,
                'kind' => 'full',
                'label' => 'Blocked Wave',
                'target_roles' => [4],
                'status' => 'scheduled',
                'cadence' => 'manual',
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('survey_waves', 0);
    }

    public function test_full_wave_requires_manual_cadence(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $this->actingAs($manager)
            ->post(route('survey-waves.store'), [
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'kind' => 'full',
                'label' => 'Invalid Full Wave',
                'target_roles' => [4],
                'status' => 'scheduled',
                'cadence' => 'weekly',
            ])
            ->assertSessionHasErrors('cadence');

        $this->assertDatabaseCount('survey_waves', 0);
    }

    public function test_paused_wave_is_skipped_by_scheduler(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Annual 2025',
            'status' => 'paused',
            'cadence' => 'manual',
        ]);

        Queue::fake();

        Artisan::call('survey:waves:schedule');

        $wave = SurveyWave::first();
        $this->assertEquals('paused', $wave->status);
        $this->assertCount(1, SurveyWaveLog::all());
        $this->assertEquals('skipped', SurveyWaveLog::first()->status);
    }

    public function test_scheduler_enforces_assignment_cadence_windows(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'drip',
            'label' => 'Pulse Week 1',
            'status' => 'scheduled',
            'cadence' => 'weekly',
        ]);

        $job = new ProcessSurveyWave($wave->id);
        $job->handle(app(SurveyService::class));

        $this->assertNotNull(SurveyAssignment::first()->last_dispatched_at);

        Artisan::call('survey:waves:schedule');

        $log = SurveyWaveLog::where('status', 'skipped')->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('cadence', strtolower($log->message));
    }

    public function test_scheduler_pauses_wave_when_billing_inactive(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        DB::table('subscriptions')->insert([
            'user_id' => $manager->id,
            'name' => 'default',
            'stripe_id' => 'sub_fake',
            'stripe_status' => 'past_due',
            'stripe_price' => 'price_fake',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Annual',
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        Queue::fake();

        Artisan::call('survey:waves:schedule');

        $wave->refresh();
        $this->assertSame('paused', $wave->status);
        $this->assertSame('paused', SurveyWaveLog::latest()->first()->status);
        Queue::assertNothingPushed();
    }

    public function test_process_wave_creates_distinct_assignment_per_wave(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $waveOne = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Wave One',
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $waveTwo = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Wave Two',
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        (new ProcessSurveyWave($waveOne->id))->handle(app(SurveyService::class));

        $assignmentOne = SurveyAssignment::where('user_id', $employee->id)
            ->where('survey_wave_id', $waveOne->id)
            ->first();

        $this->assertNotNull($assignmentOne);

        $assignmentOne->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        (new ProcessSurveyWave($waveTwo->id))->handle(app(SurveyService::class));

        $assignmentTwo = SurveyAssignment::where('user_id', $employee->id)
            ->where('survey_wave_id', $waveTwo->id)
            ->first();

        $this->assertNotNull($assignmentTwo);
        $this->assertNotEquals($assignmentOne->id, $assignmentTwo->id);
        $this->assertEquals(
            2,
            SurveyAssignment::where('user_id', $employee->id)
                ->whereIn('survey_wave_id', [$waveOne->id, $waveTwo->id])
                ->count()
        );
    }

    public function test_process_wave_respects_target_roles_and_queues_invitations(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $chief = User::factory()->create([
            'role' => 2,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Employees Only',
            'target_roles' => [4],
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        Queue::fake();

        (new ProcessSurveyWave($wave->id))->handle(app(SurveyService::class));

        $assignment = SurveyAssignment::where('survey_wave_id', $wave->id)
            ->where('user_id', $employee->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertSame('queued', $assignment->invite_status);
        $this->assertNotNull($assignment->last_dispatched_at);

        $this->assertDatabaseMissing('survey_assignments', [
            'survey_wave_id' => $wave->id,
            'user_id' => $manager->id,
        ]);

        $this->assertDatabaseMissing('survey_assignments', [
            'survey_wave_id' => $wave->id,
            'user_id' => $chief->id,
        ]);

        Queue::assertPushed(SendSurveyAssignmentInvitation::class, 1);
    }

    public function test_invitation_job_marks_assignment_as_sent_in_testing(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Pulse',
            'target_roles' => [4],
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'user_id' => $employee->id,
            'token' => 'invite-token',
            'status' => 'pending',
            'wave_label' => $wave->label,
            'invite_status' => 'queued',
        ]);

        (new SendSurveyAssignmentInvitation($assignment->id))->handle(app(EmailService::class));

        $assignment->refresh();
        $this->assertSame('sent', $assignment->invite_status);
        $this->assertNotNull($assignment->invited_at);
        $this->assertNull($assignment->invite_error);
    }

    public function test_invitation_job_marks_assignment_as_failed_when_delivery_is_unavailable(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Pulse',
            'target_roles' => [4],
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'user_id' => $employee->id,
            'token' => 'invite-token',
            'status' => 'pending',
            'wave_label' => $wave->label,
            'invite_status' => 'queued',
        ]);

        $this->app->instance(EmailService::class, new class extends EmailService {
            public function sendSurveyInvitation(string $email, string $name, string $surveyUrl, string $companyName, ?string $waveLabel = null): array
            {
                return [
                    'status' => 503,
                    'message' => 'Email delivery is unavailable because Brevo is not configured for this environment.',
                ];
            }
        });

        (new SendSurveyAssignmentInvitation($assignment->id))->handle(app(EmailService::class));

        $assignment->refresh();
        $this->assertSame('failed', $assignment->invite_status);
        $this->assertNull($assignment->invited_at);
        $this->assertStringContainsString('Brevo is not configured', (string) $assignment->invite_error);
    }

    public function test_scheduler_creates_new_assignment_after_completed_drip_cycle(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'drip',
            'label' => 'Pulse Week 1',
            'status' => 'scheduled',
            'cadence' => 'weekly',
        ]);

        (new ProcessSurveyWave($wave->id))->handle(app(SurveyService::class));

        $firstAssignment = SurveyAssignment::where('survey_wave_id', $wave->id)
            ->where('user_id', $employee->id)
            ->firstOrFail();

        $firstAssignment->update([
            'status' => 'completed',
            'completed_at' => now()->subDays(1),
            'last_dispatched_at' => now()->subDays(8),
        ]);

        Artisan::call('survey:waves:schedule');

        $assignments = SurveyAssignment::where('survey_wave_id', $wave->id)
            ->where('user_id', $employee->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $assignments);
        $this->assertSame('completed', $assignments->first()->status);
        $this->assertSame('pending', $assignments->last()->status);
        $this->assertNotSame($assignments->first()->id, $assignments->last()->id);
    }

    public function test_assignment_link_prefers_latest_pending_assignment(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $first = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employee->id,
            'token' => 'first-token',
            'status' => 'pending',
        ]);

        $latest = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employee->id,
            'token' => 'latest-token',
            'status' => 'pending',
        ]);

        $link = app(SurveyService::class)->assignmentLink($employee);

        $this->assertNotNull($link);
        $this->assertStringEndsWith($latest->token, $link);
        $this->assertNotSame($first->token, $latest->token);
    }

    public function test_manager_can_update_existing_wave(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Old Label',
            'target_roles' => [4],
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $this->actingAs($manager)
            ->put(route('survey-waves.update', $wave), [
                'label' => 'Updated Label',
                'target_roles' => [2, 4],
                'status' => 'paused',
                'cadence' => 'manual',
                'opens_at' => now()->addDay()->toDateTimeString(),
                'due_at' => now()->addDays(10)->toDateTimeString(),
            ])
            ->assertRedirect(route('survey-waves.index'));

        $wave->refresh();
        $this->assertSame('Updated Label', $wave->label);
        $this->assertSame([2, 4], $wave->target_roles);
        $this->assertSame('paused', $wave->status);

        $log = SurveyWaveLog::where('survey_wave_id', $wave->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Wave settings updated', $log->message);
    }

    public function test_cross_company_manager_cannot_update_wave(): void
    {
        [$companyA, $surveyA, $versionA] = $this->createSurveyArtifacts();

        $companyB = Companies::create([
            'title' => 'Beta',
            'manager' => 'Manager B',
            'manager_email' => 'manager-b@example.com',
        ]);

        $managerB = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $companyB->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $companyA->id,
            'survey_id' => $surveyA->id,
            'survey_version_id' => $versionA->id,
            'kind' => 'full',
            'label' => 'Locked Wave',
            'target_roles' => [4],
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $this->actingAs($managerB)
            ->put(route('survey-waves.update', $wave), [
                'label' => 'Nope',
                'target_roles' => [4],
                'status' => 'scheduled',
                'cadence' => 'manual',
            ])
            ->assertForbidden();
    }

    public function test_full_wave_cannot_be_updated_to_drip_cadence(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Annual',
            'target_roles' => [4],
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $this->actingAs($manager)
            ->put(route('survey-waves.update', $wave), [
                'label' => 'Annual',
                'target_roles' => [4],
                'status' => 'scheduled',
                'cadence' => 'weekly',
            ])
            ->assertSessionHasErrors('cadence');

        $wave->refresh();
        $this->assertSame('manual', $wave->cadence);
    }

    public function test_non_premium_user_cannot_resume_paused_drip_wave(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 0,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'drip',
            'label' => 'Pulse',
            'target_roles' => [4],
            'status' => 'paused',
            'cadence' => 'monthly',
        ]);

        $this->actingAs($manager)
            ->post(route('survey-waves.status', $wave), [
                'status' => 'scheduled',
            ])
            ->assertSessionHasErrors('cadence');

        $wave->refresh();
        $this->assertSame('paused', $wave->status);
    }

    public function test_processing_wave_cannot_be_paused_or_redispatched_manually(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Pulse',
            'target_roles' => [4],
            'status' => 'processing',
            'cadence' => 'manual',
        ]);

        $this->actingAs($manager)
            ->post(route('survey-waves.status', $wave), [
                'status' => 'paused',
            ])
            ->assertSessionHasErrors();

        $this->actingAs($manager)
            ->post(route('survey-waves.dispatch', $wave))
            ->assertSessionHasErrors();

        $wave->refresh();
        $this->assertSame('processing', $wave->status);
    }

    public function test_completed_wave_cannot_be_redispatched_or_reactivated(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $manager = User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Pulse',
            'target_roles' => [4],
            'status' => 'completed',
            'cadence' => 'manual',
        ]);

        $this->actingAs($manager)
            ->post(route('survey-waves.status', $wave), [
                'status' => 'scheduled',
            ])
            ->assertSessionHasErrors();

        $this->actingAs($manager)
            ->post(route('survey-waves.dispatch', $wave))
            ->assertSessionHasErrors();

        $wave->refresh();
        $this->assertSame('completed', $wave->status);
    }

    public function test_scheduler_recovers_stale_processing_wave_and_requeues_it(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Annual Recovery',
            'status' => 'processing',
            'cadence' => 'manual',
        ]);

        DB::table('survey_waves')
            ->where('id', $wave->id)
            ->update([
                'updated_at' => now()->subMinutes(config('survey.automation.processing_timeout_minutes', 30) + 5),
            ]);

        Queue::fake();

        Artisan::call('survey:waves:schedule');

        Queue::assertPushed(ProcessSurveyWave::class, 1);
        $recoveryLog = SurveyWaveLog::where('survey_wave_id', $wave->id)
            ->where('status', 'scheduled')
            ->latest()
            ->first();

        $this->assertNotNull($recoveryLog);
        $this->assertStringContainsString('Recovered stale processing wave', $recoveryLog->message);
    }

    public function test_scheduler_does_not_requeue_recent_processing_wave(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        User::factory()->create([
            'role' => 1,
            'company' => 1,
            'company_id' => $company->id,
            'tariff' => 1,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Annual In Flight',
            'status' => 'processing',
            'cadence' => 'manual',
        ]);

        Queue::fake();

        Artisan::call('survey:waves:schedule');

        $wave->refresh();
        $this->assertSame('processing', $wave->status);
        Queue::assertNothingPushed();

        $recoveryLog = SurveyWaveLog::where('survey_wave_id', $wave->id)
            ->where('message', 'like', 'Recovered stale processing wave%')
            ->first();
        $this->assertNull($recoveryLog);
    }

    public function test_failed_wave_job_resets_processing_status_for_recovery(): void
    {
        [$company, $survey, $version] = $this->createSurveyArtifacts();

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Annual Failure',
            'status' => 'processing',
            'cadence' => 'manual',
        ]);

        $job = new ProcessSurveyWave($wave->id);
        $job->failed(new \RuntimeException('Simulated queue failure'));

        $wave->refresh();
        $this->assertSame('scheduled', $wave->status);

        $errorLog = SurveyWaveLog::where('survey_wave_id', $wave->id)->latest()->first();
        $this->assertNotNull($errorLog);
        $this->assertSame('error', $errorLog->status);
        $this->assertStringContainsString('Queue job failed', $errorLog->message);
        $this->assertStringContainsString('Wave reset to scheduled', $errorLog->message);
    }

    public function test_legacy_email_command_is_not_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command);

        $this->assertFalse($commands->contains(fn ($command) => is_string($command) && str_contains($command, 'email:link')));
    }

    public function test_legacy_email_command_is_disabled(): void
    {
        Artisan::call('email:link');

        $this->assertStringContainsString('email:link is deprecated', Artisan::output());
    }
}
