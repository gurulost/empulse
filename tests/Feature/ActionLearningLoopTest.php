<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\PulseVariantVersion;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveCycle;
use App\Models\User;
use App\Services\ActionLoopService;
use App\Services\MetricRegistryService;
use App\Services\OrganizationEntitlementService;
use App\Services\SurveyCohortService;
use App\Services\SurveyDefinitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionLearningLoopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'runtime.audit_hash_key' => str_repeat('a', 32),
            'privacy.reporting.minimum_company_n' => 1,
            'privacy.reporting.minimum_subgroup_n' => 1,
            'privacy.reporting.minimum_completion_rate' => 0,
            'survey.indicators' => [
                'relationships' => [
                    'label' => 'Relationships',
                    'attributes' => ['WCA_REL'],
                    'weight' => 1,
                ],
            ],
        ]);
    }

    public function test_reliable_finding_becomes_owned_action_communication_and_noncausal_outcome(): void
    {
        $company = Companies::create([
            'title' => 'Learning Co',
            'manager' => 'Leader',
            'manager_email' => 'leader@learning.test',
        ]);
        $leader = User::factory()->create([
            'company_id' => $company->id,
            'role' => 1,
            'status' => 'active',
        ]);
        $respondent = User::factory()->create([
            'company_id' => $company->id,
            'role' => 4,
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'company_id' => null,
            'role' => 0,
            'is_admin' => 1,
            'status' => 'active',
        ]);
        $this->artisan('survey:import', [
            'path' => base_path('survey_instrument.json'),
            '--activate' => true,
            '--approved-by' => $admin->id,
            '--change-summary' => 'Publish canonical instrument for action-loop integration verification.',
        ])->assertSuccessful();
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)
            ->where('instrument_id', 'empulse_workfit_baseline')
            ->firstOrFail();
        $registry = app(MetricRegistryService::class)->publishedVersion();

        [$baselineWave, $baselineCycle] = $this->waveCycle(
            $company->id,
            $survey,
            $version,
            $registry->id,
            $registry->definition_hash,
            'Baseline'
        );
        $this->response($respondent, $survey, $version, $baselineWave, $baselineCycle, [
            'WCA_REL_A' => 4,
            'WCA_REL_B' => 8,
            'WCA_REL_C' => 8,
        ]);

        $service = app(ActionLoopService::class);
        $finding = $service->captureFinding(
            $company->id,
            $baselineWave->id,
            'opportunity.WCA_REL',
            $leader
        );
        $sameFinding = $service->captureFinding(
            $company->id,
            $baselineWave->id,
            'opportunity.WCA_REL',
            $leader
        );
        $this->assertSame($finding->id, $sameFinding->id);
        $this->assertDatabaseCount('diagnostic_findings', 1);
        $this->assertSame('proposed', $finding->status);
        $this->assertSame(1, $finding->evidence_snapshot['sample']['valid_n']);
        $this->assertEquals(4.0, $finding->evidence_snapshot['metric']['gap']);
        try {
            $finding->update(['interpretation' => 'Tampered interpretation']);
            $this->fail('Captured finding evidence should be immutable.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }
        $finding->refresh();

        $finding = $service->decideFinding(
            $finding,
            'accepted',
            'Leadership will test a structured relationship-building practice.',
            $leader
        );
        try {
            $service->createAction($finding, $leader, $leader, [
                'title' => 'Undated action',
                'hypothesis' => 'An action without timing should not be governable.',
                'planned_change' => 'Attempt to omit the action dates.',
                'success_criteria' => ['statement' => 'This should be rejected.'],
            ]);
            $this->fail('Every leadership action should require explicit dates.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('start date and target date', $exception->getMessage());
        }
        $action = $service->createAction($finding, $leader, $leader, [
            'title' => 'Weekly peer connection time',
            'hypothesis' => 'Protected peer time may reduce the reported relationship gap.',
            'planned_change' => 'Run a 30-minute facilitated peer session each week for six weeks.',
            'success_criteria' => ['statement' => 'Gap decreases by at least 0.5 without a culture decline.'],
            'starts_on' => now()->toDateString(),
            'target_date' => now()->addWeeks(6)->toDateString(),
        ]);
        try {
            $action->update(['title' => 'Rewrite the plan after evidence is known']);
            $this->fail('The leadership action plan should be immutable.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        try {
            $service->transitionAction($action, 'committed', $leader);
            $this->fail('Commitment without a measurement plan should fail.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('measurement plan', $exception->getMessage());
        }

        $plan = $service->createMeasurementPlan($action, $leader, [
            'target_direction' => 'decrease',
            'minimum_meaningful_change' => 0.5,
        ]);
        try {
            $plan->update(['metric_id' => 'opportunity.WCA_AUT']);
            $this->fail('The predeclared measurement definition should be immutable.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }
        $plan->refresh();
        app(OrganizationEntitlementService::class)
            ->grantManual($company, 'pulse', now()->addMonth(), $leader);
        $followupWave = $service->createFollowupWave(
            $plan,
            $leader,
            'Governed relationship follow-up',
            now()->subDay(),
            now()->addMonth()
        );
        $this->assertSame('action_followup', $followupWave->measurement_purpose);
        $this->assertNotNull($followupWave->pulse_variant_version_id);
        $this->assertSame(2, $followupWave->reminder_limit);

        $action = $service->transitionAction($action->fresh(), 'committed', $leader);
        $communication = $service->publishCommunication(
            $action,
            $leader,
            'All employees',
            'We heard a need for stronger peer connection. We will test protected weekly peer time and report what we learn after the next eligible wave.'
        );
        $this->assertSame('published', $communication->status);
        try {
            $communication->update(['message' => 'Rewrite employee communication.']);
            $this->fail('Published employee communication should be immutable.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $followupCycle = SurveyWaveCycle::create([
            'survey_wave_id' => $followupWave->id,
            'sequence' => 1,
            'status' => 'completed',
            'instrument_hash' => str_repeat('i', 64),
            'metric_definition_hash' => $registry->definition_hash,
            'metric_registry_version_id' => $registry->id,
            'audience_hash' => hash('sha256', 'Governed relationship follow-up'),
            'audience_count' => 1,
            'frozen_at' => now(),
            'dispatched_at' => now(),
        ]);
        $followupAssignment = $this->response($respondent, $survey, $version, $followupWave, $followupCycle, [
            'WCA_REL_A' => 6,
            'WCA_REL_B' => 8,
            'WCA_REL_C' => 6,
        ]);
        $definition = app(SurveyDefinitionService::class)->definitionForAssignment($followupAssignment);
        $qids = collect($definition['pages'])->flatMap(
            fn (array $page) => collect($page['items'])->pluck('qid')
                ->merge(collect($page['sections'])->flatMap(fn (array $section) => collect($section['items'])->pluck('qid')))
        )->values()->all();
        $this->assertEqualsCanonicalizing(['WCA_REL_A', 'WCA_REL_B', 'WCA_REL_C'], $qids);
        $this->assertSame(3, $definition['survey_meta']['question_count']);
        $outcome = $service->evaluate($plan, $followupWave, $leader);
        $publicId = $outcome->public_id;
        $sameOutcome = $service->evaluate($plan->fresh(), $followupWave, $leader);

        $this->assertSame('movement_observed', $outcome->result);
        $this->assertSame($publicId, $sameOutcome->public_id);
        $this->assertDatabaseCount('action_outcomes', 1);
        $this->assertSame(
            1,
            \DB::table('action_loop_events')->where('name', 'action_outcome_evaluated')->count()
        );
        $this->assertEquals(-2.0, $outcome->evaluation_snapshot['change']);
        $this->assertStringContainsString('does not establish', $outcome->causality_limit);
        try {
            $outcome->update(['result' => 'declined']);
            $this->fail('A recorded outcome should be immutable.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }
        $this->assertDatabaseHas('action_loop_events', [
            'company_id' => $company->id,
            'name' => 'action_outcome_evaluated',
        ]);
        $properties = \DB::table('action_loop_events')
            ->where('name', 'action_outcome_evaluated')
            ->value('properties');
        $this->assertStringNotContainsString('WCA_REL_A', (string) $properties);
        $this->assertStringNotContainsString('responses', (string) $properties);

        $this->actingAs($leader)
            ->get(route('actions.index'))
            ->assertOk()
            ->assertSee('Recorded outcome')
            ->assertSee('movement observed')
            ->assertSee('Comparable definition')
            ->assertSee('does not establish');

        $valueReport = $this->actingAs($admin)
            ->getJson('/admin/api/action-loop-value?company_id='.$company->id)
            ->assertOk()
            ->assertJsonPath('schema_version', 1)
            ->assertJsonPath('scope.type', 'organization')
            ->assertJsonPath('counts.reliable_findings', 1)
            ->assertJsonPath('counts.findings_with_action', 1)
            ->assertJsonPath('counts.findings_with_measured_outcome', 1)
            ->assertJsonPath('rates.finding_to_action_pct', 100)
            ->assertJsonPath('rates.finding_to_measured_outcome_pct', 100)
            ->assertJsonPath('privacy.contains_survey_answers', false)
            ->assertJsonPath('privacy.contains_employee_identity', false);
        $this->assertStringNotContainsString('WCA_REL_A', $valueReport->getContent());
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'action' => 'action.value_report_viewed',
        ]);
        foreach ([
            'action.finding.decision_recorded',
            'action.measurement_plan.created',
            'action.plan.status_changed',
            'action.communication.published',
            'action.followup_wave.created',
            'action.outcome.recorded',
        ] as $auditAction) {
            $this->assertDatabaseHas('audit_events', [
                'company_id' => $company->id,
                'action' => $auditAction,
            ]);
        }
    }

    public function test_incompatible_followup_is_never_presented_as_success(): void
    {
        $company = Companies::create([
            'title' => 'Compatibility Co',
            'manager' => 'Leader',
            'manager_email' => 'leader@compat.test',
        ]);
        $leader = User::factory()->create([
            'company_id' => $company->id,
            'role' => 1,
            'status' => 'active',
        ]);
        $respondent = User::factory()->create([
            'company_id' => $company->id,
            'role' => 4,
            'status' => 'active',
        ]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();
        $registry = app(MetricRegistryService::class)->publishedVersion();
        [$baselineWave, $baselineCycle] = $this->waveCycle(
            $company->id, $survey, $version, $registry->id, $registry->definition_hash, 'Baseline'
        );
        $this->response($respondent, $survey, $version, $baselineWave, $baselineCycle, [
            'WCA_REL_A' => 4, 'WCA_REL_B' => 8, 'WCA_REL_C' => 8,
        ]);

        $service = app(ActionLoopService::class);
        $finding = $service->captureFinding($company->id, $baselineWave->id, 'opportunity.WCA_REL', $leader);
        $finding = $service->decideFinding($finding, 'accepted', 'Test it.', $leader);
        $action = $service->createAction($finding, $leader, $leader, [
            'title' => 'Test action',
            'hypothesis' => 'A transparent hypothesis.',
            'planned_change' => 'A bounded change.',
            'success_criteria' => ['statement' => 'A predeclared criterion.'],
            'starts_on' => now()->toDateString(),
            'target_date' => now()->addMonth()->toDateString(),
        ]);
        $plan = $service->createMeasurementPlan($action, $leader, [
            'target_direction' => 'decrease',
            'minimum_meaningful_change' => 0.5,
        ]);
        [$followupWave, $followupCycle] = $this->waveCycle(
            $company->id, $survey, $version, $registry->id, str_repeat('f', 64), 'Incompatible'
        );
        $this->response($respondent, $survey, $version, $followupWave, $followupCycle, [
            'WCA_REL_A' => 7, 'WCA_REL_B' => 8, 'WCA_REL_C' => 5,
        ]);

        $outcome = $service->evaluate($plan, $followupWave, $leader);

        $this->assertSame('incompatible', $outcome->result);
        $this->assertSame('incompatible', $plan->fresh()->status);
    }

    public function test_governed_pulse_fatigue_excludes_recently_invited_respondent_with_evidence(): void
    {
        $company = Companies::create([
            'title' => 'Fatigue Co',
            'manager' => 'Leader',
            'manager_email' => 'leader@fatigue.test',
        ]);
        $respondent = User::factory()->create([
            'company_id' => $company->id,
            'role' => 4,
            'status' => 'active',
        ]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();
        $registry = app(MetricRegistryService::class)->publishedVersion();
        $variant = PulseVariantVersion::create([
            'variant_key' => 'fatigue-test',
            'version' => '1.0.0',
            'title' => 'Fatigue test',
            'purpose' => 'action_followup',
            'metric_registry_version_id' => $registry->id,
            'metric_id' => 'opportunity.WCA_REL',
            'question_ids' => ['WCA_REL_A', 'WCA_REL_B', 'WCA_REL_C'],
            'estimated_minutes' => 1,
            'minimum_days_between_invites' => 30,
            'maximum_pulses_per_90_days' => 3,
            'claims_limit' => 'Descriptive only.',
            'definition_hash' => hash('sha256', 'fatigue-test'),
            'status' => 'published',
            'published_at' => now(),
        ]);
        $priorWave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'drip',
            'status' => 'completed',
            'cadence' => 'manual',
            'label' => 'Prior pulse',
            'pulse_variant_version_id' => $variant->id,
        ]);
        SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $priorWave->id,
            'user_id' => $respondent->id,
            'status' => 'completed',
        ]);
        $nextWave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'drip',
            'status' => 'scheduled',
            'cadence' => 'manual',
            'label' => 'Too-soon pulse',
            'pulse_variant_version_id' => $variant->id,
        ]);

        $cycle = app(SurveyCohortService::class)->freeze($nextWave, [4]);

        $this->assertSame(0, $cycle->audienceMembers->count());
        $this->assertDatabaseHas('survey_wave_audience_exclusions', [
            'survey_wave_cycle_id' => $cycle->id,
            'user_id' => $respondent->id,
            'reason' => 'minimum_rest_period',
        ]);
    }

    protected function waveCycle(
        int $companyId,
        Survey $survey,
        SurveyVersion $version,
        int $registryId,
        string $metricHash,
        string $label
    ): array {
        $wave = SurveyWave::create([
            'company_id' => $companyId,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'completed',
            'cadence' => 'manual',
            'label' => $label,
        ]);
        $cycle = SurveyWaveCycle::create([
            'survey_wave_id' => $wave->id,
            'sequence' => 1,
            'status' => 'completed',
            'instrument_hash' => str_repeat('i', 64),
            'metric_definition_hash' => $metricHash,
            'metric_registry_version_id' => $registryId,
            'audience_hash' => hash('sha256', $label),
            'audience_count' => 1,
            'frozen_at' => now(),
            'dispatched_at' => now(),
        ]);

        return [$wave, $cycle];
    }

    protected function response(
        User $user,
        Survey $survey,
        SurveyVersion $version,
        SurveyWave $wave,
        SurveyWaveCycle $cycle,
        array $answers
    ): SurveyAssignment {
        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'survey_wave_cycle_id' => $cycle->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'token' => 'token-'.$wave->id,
        ]);
        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'survey_wave_cycle_id' => $cycle->id,
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
            'metric_registry_version_id' => $cycle->metric_registry_version_id,
            'metric_definition_hash' => $cycle->metric_definition_hash,
        ]);
        $questionId = SurveyQuestion::query()->value('id');
        foreach ($answers as $qid => $value) {
            SurveyAnswer::create([
                'response_id' => $response->id,
                'question_id' => $questionId,
                'question_key' => $qid,
                'value' => (string) $value,
                'value_numeric' => $value,
            ]);
        }

        return $assignment;
    }
}
