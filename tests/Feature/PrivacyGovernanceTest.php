<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\LegalHold;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyItem;
use App\Models\SurveyPage;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\PrivacyGovernanceService;
use App\Services\RetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrivacyGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['runtime.audit_hash_key' => str_repeat('p', 32)]);
    }

    public function test_current_data_promise_is_disclosed_and_required_before_submission(): void
    {
        [$company, $subject, $assignment] = $this->subjectFixture();

        $definition = $this->getJson(route('survey.definition', $assignment->token))
            ->assertOk()
            ->assertJsonPath('privacy.version', config('privacy.policy.version'))
            ->assertJsonPath('privacy.anonymous', false)
            ->assertJsonPath('privacy.customer_raw_answer_access', false)
            ->json();

        $this->assertArrayNotHasKey('email', $definition['assignment']);

        $this->postJson(route('survey.submit', $assignment->token), [
            'responses' => ['UNKNOWN' => 1],
        ])->assertStatus(428);

        $this->postJson(route('survey.privacy.acknowledge', $assignment->token), [
            'accepted' => true,
        ])->assertOk();

        $assignment->refresh();
        $this->assertSame(config('privacy.policy.version'), $assignment->privacy_policy_version);
        $this->assertNotNull($assignment->privacy_acknowledged_at);
        $this->assertDatabaseHas('privacy_acknowledgments', [
            'company_id' => $company->id,
            'user_id' => $subject->id,
            'survey_assignment_id' => $assignment->id,
            'policy_version' => config('privacy.policy.version'),
        ]);
    }

    public function test_verified_access_export_and_erasure_are_audited_and_hold_aware(): void
    {
        [$company, $subject, $assignment] = $this->subjectFixture();
        $operator = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
            'status' => 'active',
            'company_id' => null,
        ]);
        $response = SurveyResponse::create([
            'survey_id' => $assignment->survey_id,
            'survey_version_id' => $assignment->survey_version_id,
            'assignment_id' => $assignment->id,
            'user_id' => $subject->id,
            'submitted_at' => now(),
            'privacy_policy_version' => config('privacy.policy.version'),
        ]);
        $itemId = SurveyQuestion::query()->value('id');
        $page = SurveyPage::create([
            'survey_version_id' => $assignment->survey_version_id,
            'page_id' => 'privacy-evidence',
            'title' => 'Privacy evidence',
            'sort_order' => 99,
        ]);
        SurveyItem::query()->insert([
            'id' => $itemId,
            'survey_version_id' => $assignment->survey_version_id,
            'survey_page_id' => $page->id,
            'qid' => 'PRIVACY_EVIDENCE_ITEM',
            'type' => 'slider',
            'question' => 'Evidence item',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SurveyAnswer::create([
            'response_id' => $response->id,
            'question_id' => $itemId,
            'survey_item_id' => $itemId,
            'question_key' => 'WCA_REL_A',
            'value' => '7',
            'value_numeric' => 7,
        ]);
        SurveyAnswer::create([
            'response_id' => $response->id,
            'question_id' => $itemId,
            'survey_item_id' => $itemId,
            'question_key' => 'DEMOGRAPHIC_NOTE',
            'value' => 'identifying free text',
        ]);

        $privacy = app(PrivacyGovernanceService::class);
        $access = $privacy->createRequest($subject, 'access', $operator);
        $privacy->verifyIdentity($access, $operator);
        $privacy->approve($access->fresh(), $operator);
        $export = $privacy->execute($access->fresh(), $operator);
        $this->assertSame($subject->email, $export['subject']['email']);
        $this->assertCount(1, $export['responses']);

        $erasure = $privacy->createRequest($subject, 'erasure', $operator);
        $privacy->verifyIdentity($erasure, $operator);
        LegalHold::create([
            'company_id' => $company->id,
            'subject_user_id' => $subject->id,
            'created_by_user_id' => $operator->id,
            'reason' => 'Litigation preservation',
            'starts_at' => now(),
        ]);
        $blocked = $privacy->approve($erasure->fresh(), $operator);
        $this->assertSame('blocked', $blocked->status);
        $this->assertSame('active_legal_hold', $blocked->result_summary['reason']);

        LegalHold::query()->update([
            'released_at' => now(),
            'released_by_user_id' => $operator->id,
        ]);
        $second = $privacy->createRequest($subject, 'erasure', $operator);
        $privacy->verifyIdentity($second, $operator);
        $privacy->approve($second->fresh(), $operator);
        $result = $privacy->execute($second->fresh(), $operator);

        $this->assertTrue($result['identity_pseudonymized']);
        $this->assertNotNull($subject->fresh()->privacy_erased_at);
        $this->assertSame('inactive', $subject->fresh()->status);
        $this->assertDatabaseHas('survey_answers', [
            'response_id' => $response->id,
            'question_key' => 'WCA_REL_A',
        ]);
        $this->assertDatabaseMissing('survey_answers', [
            'response_id' => $response->id,
            'question_key' => 'DEMOGRAPHIC_NOTE',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'action' => 'privacy.request.completed',
        ]);
    }

    public function test_retention_is_dry_run_first_exact_hash_gated_and_respects_holds(): void
    {
        [$company, $subject, $assignment] = $this->subjectFixture();
        $assignment->forceFill([
            'draft_answers' => ['Q' => 'draft'],
            'updated_at' => now()->subDays(45),
        ])->save();

        $retention = app(RetentionService::class);
        $plan = $retention->plan();
        $this->assertContains($assignment->id, $plan['targets']['draft_assignment_ids']);

        $dryRun = $retention->recordPlan($plan, true);
        $this->expectException(\DomainException::class);
        $retention->execute($dryRun, $dryRun->plan_hash);
    }

    public function test_retention_execution_clears_only_the_reviewed_transient_targets(): void
    {
        [$company, $subject, $assignment] = $this->subjectFixture();
        $assignment->forceFill([
            'draft_answers' => ['Q' => 'draft'],
            'updated_at' => now()->subDays(45),
        ])->save();

        $retention = app(RetentionService::class);
        $plan = $retention->plan();
        $run = $retention->recordPlan($plan, false);

        try {
            $retention->execute($run, str_repeat('0', 64));
            $this->fail('Mismatched confirmation hash should fail.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('does not match', $exception->getMessage());
        }
        $this->assertNotNull($assignment->fresh()->draft_answers);

        $result = $retention->execute($run->fresh(), $run->plan_hash);
        $this->assertSame(1, $result['drafts']);
        $this->assertNull($assignment->fresh()->draft_answers);
        $this->assertSame('completed', $run->fresh()->status);
    }

    protected function subjectFixture(): array
    {
        $company = Companies::create([
            'title' => 'Privacy Co',
            'manager' => 'Owner',
            'manager_email' => 'owner@privacy.test',
        ]);
        $subject = User::factory()->create([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 4,
            'status' => 'active',
            'email' => 'respondent@privacy.test',
        ]);
        DB::table('company_worker')->insert([
            'company_id' => $company->id,
            'company_title' => $company->title,
            'name' => $subject->name,
            'email' => $subject->email,
            'role' => 4,
            'status' => 'active',
        ]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();
        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $subject->id,
            'token' => 'privacy-token-'.$subject->id,
            'status' => 'pending',
        ]);

        return [$company, $subject, $assignment];
    }
}
