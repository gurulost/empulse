<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\SurveyAnswer;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyItem;
use App\Models\SurveyPage;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use App\Services\SurveyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_non_admin_cannot_request_other_company(): void
    {
        $companyA = Companies::create([
            'title' => 'Alpha Co',
            'manager' => 'Anna',
            'manager_email' => 'anna@example.com',
        ]);

        $companyB = Companies::create([
            'title' => 'Beta Co',
            'manager' => 'Ben',
            'manager_email' => 'ben@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 1,
            'company_id' => $companyA->id,
            'company_title' => $companyA->title,
        ]);

        $response = $this->actingAs($user)->getJson("/analytics/api/dashboard?company_id={$companyB->id}");

        $response->assertStatus(403)->assertJson([
            'message' => 'Forbidden',
        ]);
    }

    public function test_admin_can_fetch_other_company_filters(): void
    {
        $company = Companies::create([
            'title' => 'Gamma Co',
            'manager' => 'Gina',
            'manager_email' => 'gina@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'company_id' => null,
        ]);

        $survey = Survey::where('is_default', true)->first();
        $version = SurveyVersion::where('is_active', true)->first();

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'active',
            'cadence' => 'once',
            'label' => 'Wave Alpha',
        ]);

        $member = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 3,
            'name' => 'Team Lead',
            'email' => 'teamlead@example.com',
        ]);

        DB::table('company_worker')->insert([
            'company_id' => $company->id,
            'name' => 'Team Lead',
            'email' => 'teamlead@example.com',
            'department' => 'Ops',
            'role' => 3,
            'supervisor' => null,
        ]);

        DB::table('company_department')->insert([
            'company_id' => $company->id,
            'title' => 'Ops',
        ]);

        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'user_id' => $member->id,
            'token' => (string) Str::uuid(),
            'status' => 'completed',
            'wave_label' => 'Wave Alpha',
        ]);

        SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'assignment_id' => $assignment->id,
            'user_id' => $member->id,
            'wave_label' => 'Wave Alpha',
            'submitted_at' => now(),
        ]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('companyDashboardAnalytics')
            ->once()
            ->with([
                'company_id' => $company->id,
                'department' => null,
                'team' => null,
                'wave' => null,
            ])
            ->andReturn(['metrics' => [10, 20]]);
        $mock->shouldReceive('availableWavesForCompany')
            ->once()
            ->with($company->id)
            ->andReturn([
                'wave:' . $wave->id => 'Wave Alpha',
            ]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($admin)->getJson("/analytics/api/dashboard?company_id={$company->id}");

        $response->assertOk()
            ->assertJsonPath('data.metrics', [10, 20])
            ->assertJsonPath("filters.waves.wave:{$wave->id}", 'Wave Alpha')
            ->assertJsonStructure([
                'filters' => ['departments', 'teamleads', 'waves', 'exist_departments'],
            ]);
    }

    public function test_workfit_admin_without_company_can_fetch_selected_company_filters(): void
    {
        $company = Companies::create([
            'title' => 'Delta Co',
            'manager' => 'Dana',
            'manager_email' => 'dana@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 1,
            'is_admin' => 1,
            'company_id' => null,
        ]);

        $mock = Mockery::mock(SurveyAnalyticsService::class);
        $mock->shouldReceive('companyDashboardAnalytics')
            ->once()
            ->with([
                'company_id' => $company->id,
                'department' => null,
                'team' => null,
                'wave' => null,
            ])
            ->andReturn(['metrics' => [5, 6]]);
        $mock->shouldReceive('availableWavesForCompany')
            ->once()
            ->with($company->id)
            ->andReturn([]);

        $this->app->instance(SurveyAnalyticsService::class, $mock);

        $response = $this->actingAs($admin)->getJson("/analytics/api/dashboard?company_id={$company->id}");

        $response->assertOk()
            ->assertJsonPath('data.metrics', [5, 6]);
    }

    public function test_wave_filter_accepts_wave_id_keys_from_filter_options(): void
    {
        $company = Companies::create([
            'title' => 'Delta Co',
            'manager' => 'Dina',
            'manager_email' => 'dina@example.com',
        ]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 1,
        ]);

        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();

        $page = SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'attr_test',
            'title' => 'Attr Test',
            'sort_order' => 1,
        ]);

        $itemsByQid = collect(['WCA_REL_A', 'WCA_REL_B', 'WCA_REL_C'])
            ->mapWithKeys(function (string $qid, int $index) use ($version, $page) {
                $item = SurveyItem::create([
                    'survey_version_id' => $version->id,
                    'survey_page_id' => $page->id,
                    'qid' => $qid,
                    'type' => 'slider',
                    'question' => "Question {$qid}",
                    'scale_config' => ['min' => 1, 'max' => 10, 'step' => 1],
                    'sort_order' => $index + 1,
                ]);

                return [$qid => $item];
            });

        $waveA = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'active',
            'cadence' => 'manual',
            'label' => 'Wave A',
            'due_at' => now()->subDay(),
        ]);
        $waveB = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'active',
            'cadence' => 'manual',
            'label' => 'Wave B',
            'due_at' => now(),
        ]);

        $employeeA = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'wavea@example.com',
        ]);
        $employeeB = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'waveb@example.com',
        ]);

        DB::table('company_worker')->insert([
            [
                'company_id' => $company->id,
                'name' => 'Wave A Employee',
                'email' => 'wavea@example.com',
                'department' => 'Ops',
                'supervisor' => 'Lead A',
                'role' => 4,
            ],
            [
                'company_id' => $company->id,
                'name' => 'Wave B Employee',
                'email' => 'waveb@example.com',
                'department' => 'Ops',
                'supervisor' => 'Lead A',
                'role' => 4,
            ],
        ]);

        $this->createWaveResponse($survey->id, $version->id, $waveA, $employeeA, $itemsByQid, [
            'WCA_REL_A' => 2,
            'WCA_REL_B' => 8,
            'WCA_REL_C' => 5,
        ]);
        $this->createWaveResponse($survey->id, $version->id, $waveB, $employeeB, $itemsByQid, [
            'WCA_REL_A' => 9,
            'WCA_REL_B' => 9,
            'WCA_REL_C' => 9,
        ]);

        $response = $this->actingAs($manager)->getJson("/analytics/api/dashboard?wave=wave:{$waveA->id}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.0.key', 'WCA_REL');
        $response->assertJsonPath('data.attributes.0.current', 2);
        $response->assertJsonPath('data.attributes.0.ideal', 8);
        $response->assertJsonPath('data.attributes.0.desire', 5);
    }

    protected function createWaveResponse(
        int $surveyId,
        int $versionId,
        SurveyWave $wave,
        User $employee,
        Collection $itemsByQid,
        array $values
    ): void {
        $assignment = SurveyAssignment::create([
            'survey_id' => $surveyId,
            'survey_version_id' => $versionId,
            'survey_wave_id' => $wave->id,
            'user_id' => $employee->id,
            'token' => (string) Str::uuid(),
            'status' => 'completed',
            'wave_label' => $wave->label,
        ]);

        $response = SurveyResponse::create([
            'survey_id' => $surveyId,
            'survey_version_id' => $versionId,
            'survey_wave_id' => $wave->id,
            'assignment_id' => $assignment->id,
            'user_id' => $employee->id,
            'wave_label' => $wave->label,
            'submitted_at' => now(),
        ]);

        foreach ($values as $qid => $value) {
            $item = $itemsByQid->get($qid);

            SurveyAnswer::create([
                'response_id' => $response->id,
                'question_id' => $item->id,
                'survey_item_id' => $item->id,
                'question_key' => $qid,
                'value' => (string) $value,
                'value_numeric' => $value,
                'metadata' => ['attribute_label' => 'Relationships'],
            ]);
        }
    }
}
