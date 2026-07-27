<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveCycle;
use App\Models\User;
use App\Services\AnalyticsSamplePolicyService;
use App\Services\MetricRegistryService;
use App\Services\SurveyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsPrivacyGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_response_is_suppressed_and_no_metric_values_leak(): void
    {
        $company = Companies::create([
            'title' => 'Small Cohort',
            'manager' => 'Owner',
            'manager_email' => 'owner@small.test',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 4,
            'status' => 'active',
        ]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();
        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'active',
            'cadence' => 'manual',
            'label' => 'Protected Wave',
        ]);
        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'token' => 'protected-wave-token',
        ]);
        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
        SurveyAnswer::create([
            'response_id' => $response->id,
            'question_id' => SurveyQuestion::query()->value('id'),
            'question_key' => 'WCA_REL_A',
            'value' => '9',
            'value_numeric' => 9,
        ]);

        $data = app(SurveyAnalyticsService::class)->companyDashboardAnalytics([
            'company_id' => $company->id,
            'wave' => "wave:{$wave->id}",
        ]);

        $this->assertSame('suppressed', $data['availability']);
        $this->assertSame(1, $data['sample']['valid_n']);
        $this->assertSame(5, $data['sample']['minimum_n']);
        $this->assertArrayNotHasKey('attributes', $data);
        $this->assertArrayNotHasKey('gap_chart', $data);
        $this->assertArrayNotHasKey('weighted_indicator', $data);
    }

    public function test_complementary_suppression_hides_a_second_group(): void
    {
        $groups = [
            'Small' => collect(range(1, 4)),
            'Visible A' => collect(range(1, 8)),
            'Visible B' => collect(range(1, 9)),
        ];

        $result = app(AnalyticsSamplePolicyService::class)->visibleGroups($groups);

        $this->assertSame(['Visible B'], array_keys($result['visible']));
        $this->assertSame(4, $result['suppressed']['Small']);
        $this->assertSame(8, $result['suppressed']['Visible A']);
    }

    public function test_comparison_suppresses_a_large_group_when_only_one_person_has_both_metrics(): void
    {
        config([
            'survey.indicators' => [
                'relationships' => [
                    'label' => 'Relationships',
                    'attributes' => ['WCA_REL'],
                    'weight' => 1,
                ],
            ],
            'survey.team_culture.positive' => ['TC_POS'],
            'survey.team_culture.negative' => [],
            'survey.team_culture_evaluation.dimensions' => [],
        ]);
        [$company, $survey, $version, $wave, $cycle] = $this->metricPrivacyFixture(
            'Comparison Privacy',
            7
        );
        $questionId = SurveyQuestion::query()->value('id');

        foreach (range(1, 7) as $index) {
            $response = $this->metricPrivacyResponse(
                $company,
                $survey,
                $version,
                $wave,
                $cycle,
                $index,
                ['department' => 'Engineering']
            );
            foreach ([
                'WCA_REL_A' => 6,
                'TC_POS' => 7,
                ...($index === 1 ? ['WCA_REL_B' => 8] : []),
            ] as $key => $value) {
                SurveyAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $questionId,
                    'question_key' => $key,
                    'value' => (string) $value,
                    'value_numeric' => $value,
                ]);
            }
        }

        $comparison = app(SurveyAnalyticsService::class)
            ->getComparisonData($company->id, $wave->id, 'department');

        $this->assertSame([], $comparison['labels']);
        $this->assertSame([], $comparison['datasets'][0]['data']);
        $this->assertSame([], $comparison['datasets'][1]['data']);
        $this->assertSame(1, $comparison['privacy']['suppressed_group_count']);
    }

    public function test_trend_suppresses_metric_when_nominal_sample_is_large_but_metric_valid_n_is_one(): void
    {
        config([
            'survey.indicators' => [
                'relationships' => [
                    'label' => 'Relationships',
                    'attributes' => ['WCA_REL'],
                    'weight' => 1,
                ],
            ],
        ]);
        [$company, $survey, $version, $wave, $cycle] = $this->metricPrivacyFixture(
            'Trend Privacy',
            5,
            true
        );
        $questionId = SurveyQuestion::query()->value('id');

        foreach (range(1, 5) as $index) {
            $response = $this->metricPrivacyResponse(
                $company,
                $survey,
                $version,
                $wave,
                $cycle,
                $index
            );
            foreach ([
                'WCA_REL_A' => 6,
                ...($index === 1 ? ['WCA_REL_B' => 8] : []),
            ] as $key => $value) {
                SurveyAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $questionId,
                    'question_key' => $key,
                    'value' => (string) $value,
                    'value_numeric' => $value,
                ]);
            }
        }

        $trend = app(SurveyAnalyticsService::class)->getTrendData($company->id, 'workfit_indicator');

        $this->assertSame([], $trend['labels']);
        $this->assertSame([], $trend['datasets'][0]['data']);
        $this->assertNull($trend['points'][0]['value']);
        $this->assertSame(5, $trend['points'][0]['sample']['valid_n']);
        $this->assertSame(1, $trend['points'][0]['sample']['metric_valid_n']);
        $this->assertSame('suppressed', $trend['points'][0]['sample']['metric_status']);
    }

    protected function metricPrivacyFixture(
        string $label,
        int $audienceCount,
        bool $withDueDate = false
    ): array {
        $company = Companies::create([
            'title' => $label,
            'manager' => 'Owner',
            'manager_email' => 'owner@privacy.test',
        ]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();
        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'completed',
            'cadence' => 'manual',
            'label' => $label,
            'due_at' => $withDueDate ? now()->subMinute() : null,
        ]);
        $registry = app(MetricRegistryService::class)->publishedVersion();
        $cycle = SurveyWaveCycle::create([
            'survey_wave_id' => $wave->id,
            'sequence' => 1,
            'status' => 'completed',
            'instrument_hash' => str_repeat('i', 64),
            'metric_definition_hash' => $registry->definition_hash,
            'metric_registry_version_id' => $registry->id,
            'audience_hash' => str_repeat('a', 64),
            'audience_count' => $audienceCount,
            'frozen_at' => now(),
        ]);

        return [$company, $survey, $version, $wave, $cycle];
    }

    protected function metricPrivacyResponse(
        Companies $company,
        Survey $survey,
        SurveyVersion $version,
        SurveyWave $wave,
        SurveyWaveCycle $cycle,
        int $index,
        array $cohort = []
    ): SurveyResponse {
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 4,
            'status' => 'active',
        ]);
        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'survey_wave_cycle_id' => $cycle->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'token' => "privacy-token-{$wave->id}-{$index}",
        ]);

        return SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'survey_wave_cycle_id' => $cycle->id,
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
            'cohort_snapshot' => $cohort,
            'metric_registry_version_id' => $cycle->metric_registry_version_id,
            'metric_definition_hash' => $cycle->metric_definition_hash,
        ]);
    }
}
