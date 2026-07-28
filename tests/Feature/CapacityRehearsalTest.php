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
use App\Services\CapacityRehearsalService;
use App\Services\SurveyAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CapacityRehearsalTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_measures_cohort_and_integrity_but_rejects_unqualified_database(): void
    {
        [$company, $wave] = $this->capacityFixture();

        $analytics = Mockery::mock(SurveyAnalyticsService::class);
        $analytics->shouldReceive('companyDashboardAnalytics')
            ->times(3)
            ->with([
                'company_id' => $company->id,
                'wave' => 'wave:'.$wave->id,
            ])
            ->andReturn([
                'availability' => 'eligible',
                'sample' => [
                    'status' => 'eligible',
                    'invited_n' => 2,
                    'submitted_n' => 2,
                    'valid_n' => 2,
                ],
            ]);

        $report = (new CapacityRehearsalService($analytics))->run(
            $company->id,
            $wave->id,
            iterations: 2,
            minimumInvited: 2,
            analyticsP95BudgetMs: 3000
        );

        $this->assertSame('repository_capacity_rehearsal', $report['scope']);
        $this->assertFalse($report['production_signoff']);
        $this->assertSame(2, $report['counts']['invited_users']);
        $this->assertSame(2, $report['counts']['submitted_responses']);
        $this->assertSame(2, $report['counts']['answers']);
        $this->assertSame(['eligible'], $report['analytics']['availability']);
        $this->assertFalse($report['checks']['database_engine']['passed']);
        $this->assertSame('sqlite', $report['checks']['database_engine']['actual']);
        $this->assertTrue($report['checks']['minimum_invited_cohort']['passed']);
        $this->assertTrue($report['checks']['eligible_privacy_result']['passed']);
        $this->assertTrue($report['checks']['analytics_p95_budget_ms']['passed']);
        $this->assertSame([
            'duplicate_assignment_groups' => 0,
            'duplicate_response_groups' => 0,
            'duplicate_answer_groups' => 0,
            'cross_tenant_assignments' => 0,
            'cross_tenant_responses' => 0,
            'response_assignment_mismatches' => 0,
        ], $report['integrity_findings']);
        $this->assertFalse($report['passed']);
    }

    public function test_command_requires_an_explicit_wave_selector(): void
    {
        $this->artisan('readiness:capacity-rehearsal', [
            'company_id' => 1,
            '--wave' => 'July 2026 Pulse',
        ])
            ->expectsOutputToContain('wave:<id>')
            ->assertFailed();
    }

    /**
     * @return array{0: Companies, 1: SurveyWave}
     */
    protected function capacityFixture(): array
    {
        $company = Companies::create([
            'title' => 'Capacity Co',
            'manager' => 'Manager',
            'manager_email' => 'manager@capacity.test',
        ]);
        $survey = Survey::create([
            'company_id' => $company->id,
            'title' => 'Capacity Survey',
            'is_default' => true,
            'status' => 'published',
        ]);
        $version = SurveyVersion::create([
            'instrument_id' => 'capacity-v1',
            'version' => '1.0.0',
            'title' => 'Capacity v1',
            'is_active' => true,
        ]);
        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Capacity Wave',
            'status' => 'completed',
            'cadence' => 'manual',
        ]);

        foreach (range(1, 2) as $index) {
            $user = User::factory()->create([
                'company_id' => $company->id,
                'role' => 4,
            ]);
            $assignment = SurveyAssignment::create([
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $wave->id,
                'user_id' => $user->id,
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
                'question_id' => $index,
                'question_key' => 'WCA_REL_A',
                'value' => '7',
                'value_numeric' => 7,
            ]);
        }

        return [$company, $wave];
    }
}
