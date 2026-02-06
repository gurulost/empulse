<?php

namespace Tests\Feature;

use App\Jobs\ProcessSurveyWave;
use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveLog;
use App\Models\User;
use App\Services\SurveyService;
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
            'status' => 'scheduled',
            'cadence' => 'monthly',
        ]);

        $this->assertDatabaseCount('survey_waves', 0);
        $this->assertTrue($response->isRedirect() || $response->getStatusCode() === 403);
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
}
