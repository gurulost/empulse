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
}
