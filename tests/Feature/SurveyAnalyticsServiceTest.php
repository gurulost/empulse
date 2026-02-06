<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use App\Services\SurveyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurveyAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_wave_filter_uses_wave_id_and_not_only_latest_unfiltered_response(): void
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
            'status' => 'published',
        ]);

        $version = SurveyVersion::create([
            'instrument_id' => 'eng-v1',
            'version' => '1.0.0',
            'title' => 'Org Survey v1',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 4,
        ]);

        $waveTwo = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Wave Two',
            'status' => 'completed',
            'cadence' => 'manual',
        ]);

        $waveOne = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Wave One',
            'status' => 'completed',
            'cadence' => 'manual',
        ]);

        $assignmentTwo = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $waveTwo->id,
            'user_id' => $user->id,
            'token' => (string) Str::uuid(),
            'status' => 'completed',
            'wave_label' => $waveTwo->label,
        ]);

        $responseTwo = SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $waveTwo->id,
            'assignment_id' => $assignmentTwo->id,
            'user_id' => $user->id,
            'wave_label' => $waveTwo->label,
            'submitted_at' => now()->subDay(),
        ]);

        SurveyAnswer::create([
            'response_id' => $responseTwo->id,
            'question_id' => 1,
            'survey_item_id' => null,
            'question_key' => 'WCA_REL_A',
            'value' => '7',
            'value_numeric' => 7,
            'metadata' => [],
        ]);

        SurveyAnswer::create([
            'response_id' => $responseTwo->id,
            'question_id' => 1,
            'survey_item_id' => null,
            'question_key' => 'WCA_REL_B',
            'value' => '8',
            'value_numeric' => 8,
            'metadata' => [],
        ]);

        $assignmentOne = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $waveOne->id,
            'user_id' => $user->id,
            'token' => (string) Str::uuid(),
            'status' => 'completed',
            'wave_label' => $waveOne->label,
        ]);

        $responseOne = SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $waveOne->id,
            'assignment_id' => $assignmentOne->id,
            'user_id' => $user->id,
            'wave_label' => $waveOne->label,
            'submitted_at' => now(),
        ]);

        SurveyAnswer::create([
            'response_id' => $responseOne->id,
            'question_id' => 1,
            'survey_item_id' => null,
            'question_key' => 'WCA_REL_A',
            'value' => '2',
            'value_numeric' => 2,
            'metadata' => [],
        ]);

        SurveyAnswer::create([
            'response_id' => $responseOne->id,
            'question_id' => 1,
            'survey_item_id' => null,
            'question_key' => 'WCA_REL_B',
            'value' => '9',
            'value_numeric' => 9,
            'metadata' => [],
        ]);

        $service = app(SurveyAnalyticsService::class);
        $data = $service->companyDashboardAnalytics([
            'company_id' => $company->id,
            'wave' => 'wave:' . $waveTwo->id,
        ]);

        $attribute = collect($data['attributes'] ?? [])->firstWhere('key', 'WCA_REL');

        $this->assertNotNull($attribute);
        $this->assertEquals(7.0, $attribute['current']);
        $this->assertEquals(8.0, $attribute['ideal']);
        $this->assertEquals(1.0, $attribute['gap']);
    }

    public function test_available_waves_for_company_uses_wave_catalog_not_recent_response_sampling(): void
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
            'status' => 'published',
        ]);

        $version = SurveyVersion::create([
            'instrument_id' => 'eng-v1',
            'version' => '1.0.0',
            'title' => 'Org Survey v1',
            'is_active' => true,
        ]);

        $waveOne = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Launch Wave',
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $waveTwo = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'drip',
            'label' => 'Pulse Wave',
            'status' => 'scheduled',
            'cadence' => 'monthly',
        ]);

        $service = app(SurveyAnalyticsService::class);
        $waves = $service->availableWavesForCompany($company->id);

        $this->assertArrayHasKey('wave:' . $waveOne->id, $waves);
        $this->assertArrayHasKey('wave:' . $waveTwo->id, $waves);
        $this->assertStringContainsString('Launch Wave', $waves['wave:' . $waveOne->id]);
        $this->assertStringContainsString('Pulse Wave', $waves['wave:' . $waveTwo->id]);
    }

    public function test_get_comparison_data_returns_chart_shape_even_without_responses(): void
    {
        $company = Companies::create([
            'title' => 'No Responses Co',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $survey = Survey::create([
            'company_id' => $company->id,
            'title' => 'Org Survey',
            'is_default' => true,
            'status' => 'published',
        ]);

        $version = SurveyVersion::create([
            'instrument_id' => 'eng-v2',
            'version' => '2.0.0',
            'title' => 'Org Survey v2',
            'is_active' => true,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Wave Empty',
            'status' => 'scheduled',
            'cadence' => 'manual',
        ]);

        $service = app(SurveyAnalyticsService::class);
        $dataset = $service->getComparisonData($company->id, $wave->id, 'department');

        $this->assertIsArray($dataset);
        $this->assertArrayHasKey('labels', $dataset);
        $this->assertArrayHasKey('datasets', $dataset);
        $this->assertCount(2, $dataset['datasets']);
        $this->assertSame([], $dataset['labels']);
        $this->assertSame([], $dataset['datasets'][0]['data']);
        $this->assertSame([], $dataset['datasets'][1]['data']);
    }

    public function test_get_trend_data_falls_back_to_response_backed_waves_without_due_dates(): void
    {
        $company = Companies::create([
            'title' => 'Fallback Trends Co',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $survey = Survey::create([
            'company_id' => $company->id,
            'title' => 'Trend Survey',
            'is_default' => true,
            'status' => 'published',
        ]);

        $version = SurveyVersion::create([
            'instrument_id' => 'trend-v1',
            'version' => '1.0.0',
            'title' => 'Trend Survey v1',
            'is_active' => true,
        ]);

        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'No Due Date Wave',
            'status' => 'completed',
            'cadence' => 'manual',
            'due_at' => null,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 4,
        ]);

        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'user_id' => $user->id,
            'token' => (string) Str::uuid(),
            'status' => 'completed',
            'wave_label' => $wave->label,
        ]);

        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'wave_label' => $wave->label,
            'submitted_at' => now(),
        ]);

        SurveyAnswer::create([
            'response_id' => $response->id,
            'question_id' => 1,
            'survey_item_id' => null,
            'question_key' => 'WCA_REL_A',
            'value' => '6',
            'value_numeric' => 6,
            'metadata' => [],
        ]);

        SurveyAnswer::create([
            'response_id' => $response->id,
            'question_id' => 1,
            'survey_item_id' => null,
            'question_key' => 'WCA_REL_B',
            'value' => '8',
            'value_numeric' => 8,
            'metadata' => [],
        ]);

        $service = app(SurveyAnalyticsService::class);
        $trend = $service->getTrendData($company->id, 'engagement');

        $this->assertNotEmpty($trend['labels']);
        $this->assertSame('No Due Date Wave', $trend['labels'][0]);
        $this->assertNotEmpty($trend['datasets'][0]['data']);
    }
}
