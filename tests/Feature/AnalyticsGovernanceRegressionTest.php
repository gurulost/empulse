<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\MetricRegistryVersion;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveCycle;
use App\Models\User;
use App\Services\MetricRegistryService;
use App\Services\OrganizationScopeService;
use App\Services\OrganizationService;
use App\Services\SurveyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsGovernanceRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_gap_requires_the_same_respondents_to_have_current_and_ideal_values(): void
    {
        config([
            'privacy.reporting.minimum_company_n' => 2,
            'privacy.reporting.minimum_completion_rate' => 0,
        ]);
        [$company, $survey, $version, $wave, $cycle] = $this->fixture('Paired N', 2);

        $this->response($company, $survey, $version, $wave, $cycle, 1, [
            'WCA_REL_A' => 4,
        ]);
        $this->response($company, $survey, $version, $wave, $cycle, 2, [
            'WCA_REL_B' => 9,
        ]);

        $analytics = app(SurveyAnalyticsService::class)->companyDashboardAnalytics([
            'company_id' => $company->id,
            'cycle_id' => $cycle->id,
        ]);
        $attribute = collect($analytics['attributes'])->firstWhere('key', 'WCA_REL');

        $this->assertSame('eligible', $analytics['availability']);
        $this->assertSame(0, $attribute['valid_n']);
        $this->assertSame('suppressed', $attribute['availability']);
        $this->assertNull($attribute['gap']);
        $this->assertNull($attribute['current']);
        $this->assertNull($attribute['ideal']);
    }

    public function test_default_and_wave_dashboard_select_only_the_latest_immutable_cycle(): void
    {
        config([
            'privacy.reporting.minimum_company_n' => 2,
            'privacy.reporting.minimum_completion_rate' => 0,
        ]);
        [$company, $survey, $version, $wave, $firstCycle, $registry] = $this->fixture('Recurring', 2);
        $secondCycle = $this->cycle($wave, $registry, 2, 2);

        foreach (range(1, 2) as $index) {
            $this->response($company, $survey, $version, $wave, $firstCycle, $index, [
                'WCA_REL_A' => 2,
                'WCA_REL_B' => 8,
            ], now()->subDay());
            $this->response($company, $survey, $version, $wave, $secondCycle, $index + 10, [
                'WCA_REL_A' => 7,
                'WCA_REL_B' => 8,
            ], now());
        }

        foreach ([['company_id' => $company->id], [
            'company_id' => $company->id,
            'wave' => "wave:{$wave->id}",
        ]] as $filters) {
            $analytics = app(SurveyAnalyticsService::class)->companyDashboardAnalytics($filters);
            $attribute = collect($analytics['attributes'])->firstWhere('key', 'WCA_REL');

            $this->assertSame(2, $analytics['sample']['submitted_n']);
            $this->assertSame(7.0, $attribute['current']);
            $this->assertSame(1.0, $attribute['gap']);
        }
    }

    public function test_frozen_registry_definition_drives_historical_calculation_after_config_changes(): void
    {
        config([
            'privacy.reporting.minimum_company_n' => 2,
            'privacy.reporting.minimum_completion_rate' => 0,
            'survey.indicators' => [
                'relationships' => [
                    'label' => 'Relationships',
                    'attributes' => ['WCA_REL'],
                    'weight' => 1,
                ],
            ],
        ]);
        [$company, $survey, $version, $wave, $cycle] = $this->fixture('Frozen Definition', 2);

        config([
            'survey.indicators' => [
                'achievement' => [
                    'label' => 'Achievement',
                    'attributes' => ['WCA_ACH'],
                    'weight' => 1,
                ],
            ],
        ]);

        foreach (range(1, 2) as $index) {
            $this->response($company, $survey, $version, $wave, $cycle, $index, [
                'WCA_REL_A' => 6,
                'WCA_REL_B' => 8,
            ]);
        }

        $analytics = app(SurveyAnalyticsService::class)->companyDashboardAnalytics([
            'company_id' => $company->id,
            'cycle_id' => $cycle->id,
        ]);

        $this->assertSame('eligible', $analytics['availability']);
        $this->assertSame('relationships', $analytics['indicators'][0]['key']);
        $this->assertSame(7.5, $analytics['weighted_indicator']);
        $this->assertSame($cycle->metric_definition_hash, $analytics['metric_registry']['hash']);
    }

    public function test_chief_analytics_scope_cannot_be_overridden_by_request_filters(): void
    {
        config([
            'privacy.reporting.minimum_company_n' => 1,
            'privacy.reporting.minimum_subgroup_n' => 1,
            'privacy.reporting.minimum_completion_rate' => 0,
        ]);
        [$company, $survey, $version, $wave, $cycle] = $this->fixture('Hierarchy', 2);
        $chief = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 2,
            'status' => 'active',
        ]);
        $chiefMembership = app(OrganizationService::class)->synchronize(
            $chief,
            null,
            'Operations'
        );
        $operationsUnitId = $chiefMembership->currentAssignment->organization_unit_id;

        $this->response($company, $survey, $version, $wave, $cycle, 1, [
            'WCA_REL_A' => 6,
            'WCA_REL_B' => 8,
        ], now(), [
            'department' => 'Operations',
            'organization_unit_id' => $operationsUnitId,
        ]);
        $this->response($company, $survey, $version, $wave, $cycle, 2, [
            'WCA_REL_A' => 1,
            'WCA_REL_B' => 10,
        ], now(), [
            'department' => 'Finance',
            'organization_unit_id' => $operationsUnitId + 1000,
        ]);

        $scope = app(OrganizationScopeService::class)->analyticsFilters($chief);
        $this->assertSame(['organization_unit_id' => $operationsUnitId], $scope);

        $response = $this->actingAs($chief)->getJson('/analytics/api/dashboard');
        $response->assertOk()
            ->assertJsonPath('data.sample.submitted_n', 1)
            ->assertJsonPath('data.attributes.0.current', 6)
            ->assertJsonCount(0, 'filters.departments')
            ->assertJsonCount(0, 'filters.teamleads');

        $override = $this->actingAs($chief)
            ->getJson('/analytics/api/dashboard?department=Finance');
        $override->assertOk()
            ->assertJsonPath('data.availability', 'collecting');
    }

    public function test_hierarchy_scoped_trend_uses_the_subgroup_confidentiality_threshold(): void
    {
        config([
            'privacy.reporting.minimum_company_n' => 5,
            'privacy.reporting.minimum_subgroup_n' => 7,
            'privacy.reporting.minimum_completion_rate' => 0,
        ]);
        [$company, $survey, $version, $wave, $cycle] = $this->fixture('Scoped Trend', 5);

        foreach (range(1, 5) as $index) {
            $this->response($company, $survey, $version, $wave, $cycle, $index, [
                'WCA_REL_A' => 6,
                'WCA_REL_B' => 8,
            ], now(), [
                'organization_unit_id' => 42,
            ]);
        }

        $trend = app(SurveyAnalyticsService::class)->getTrendData(
            $company->id,
            'workfit_indicator',
            ['organization_unit_id' => 42]
        );

        $this->assertSame([], $trend['labels']);
        $this->assertNull($trend['points'][0]['value']);
        $this->assertSame(5, $trend['points'][0]['sample']['valid_n']);
        $this->assertSame(7, $trend['points'][0]['sample']['minimum_n']);
        $this->assertTrue($trend['points'][0]['sample']['is_subgroup']);
    }

    private function fixture(string $label, int $audienceCount): array
    {
        $company = Companies::create([
            'title' => $label,
            'manager' => 'Owner',
            'manager_email' => Str::slug($label).'@example.test',
        ]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();
        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'drip',
            'status' => 'completed',
            'cadence' => 'monthly',
            'label' => $label,
            'due_at' => now()->subMinute(),
        ]);
        $registry = app(MetricRegistryService::class)->publishedVersion();
        $cycle = $this->cycle($wave, $registry, 1, $audienceCount);

        return [$company, $survey, $version, $wave, $cycle, $registry];
    }

    private function cycle(
        SurveyWave $wave,
        MetricRegistryVersion $registry,
        int $sequence,
        int $audienceCount
    ): SurveyWaveCycle {
        return SurveyWaveCycle::create([
            'survey_wave_id' => $wave->id,
            'sequence' => $sequence,
            'status' => 'completed',
            'instrument_hash' => str_repeat('i', 64),
            'metric_definition_hash' => $registry->definition_hash,
            'metric_registry_version_id' => $registry->id,
            'audience_hash' => hash('sha256', "{$wave->id}:{$sequence}"),
            'audience_count' => $audienceCount,
            'frozen_at' => now(),
        ]);
    }

    private function response(
        Companies $company,
        Survey $survey,
        SurveyVersion $version,
        SurveyWave $wave,
        SurveyWaveCycle $cycle,
        int $index,
        array $answers,
        $submittedAt = null,
        array $cohort = []
    ): SurveyResponse {
        $user = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
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
            'token' => (string) Str::uuid(),
        ]);
        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'survey_wave_cycle_id' => $cycle->id,
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'submitted_at' => $submittedAt ?? now(),
            'cohort_snapshot' => $cohort,
            'metric_registry_version_id' => $cycle->metric_registry_version_id,
            'metric_definition_hash' => $cycle->metric_definition_hash,
        ]);
        $questionId = SurveyQuestion::query()->value('id');
        foreach ($answers as $key => $value) {
            SurveyAnswer::create([
                'response_id' => $response->id,
                'question_id' => $questionId,
                'question_key' => $key,
                'value' => (string) $value,
                'value_numeric' => $value,
            ]);
        }

        return $response;
    }
}
