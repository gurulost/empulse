<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\SurveyDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionConcurrencyRehearsalTest extends TestCase
{
    use RefreshDatabase;

    public function test_atomic_draft_service_preserves_the_winner_and_rejects_a_stale_revision(): void
    {
        $assignment = $this->pendingAssignment();
        $drafts = app(SurveyDraftService::class);

        $winner = $drafts->save($assignment, ['Q_01' => 4], 0);
        $stale = $drafts->save($assignment, ['Q_01' => 9], 0);

        $this->assertTrue($winner['saved']);
        $this->assertSame(1, $winner['revision']);
        $this->assertFalse($stale['saved']);
        $this->assertSame(1, $stale['revision']);
        $this->assertSame(1, $assignment->fresh()->draft_revision);
        $this->assertSame(['Q_01' => 4], $assignment->fresh()->draft_answers);
    }

    public function test_concurrency_rehearsal_requires_explicit_mutation_confirmation(): void
    {
        $this->artisan('readiness:submission-concurrency', [
            'autosave_assignment_id' => 1,
            'submit_assignment_id' => 2,
            'source_response_id' => 1,
        ])
            ->expectsOutputToContain('Re-run with --execute')
            ->assertFailed();
    }

    public function test_concurrency_rehearsal_requires_postgresql(): void
    {
        $this->artisan('readiness:submission-concurrency', [
            'autosave_assignment_id' => 1,
            'submit_assignment_id' => 2,
            'source_response_id' => 1,
            '--execute' => true,
        ])
            ->expectsOutputToContain('requires PostgreSQL')
            ->assertFailed();
    }

    protected function pendingAssignment(): SurveyAssignment
    {
        return SurveyAssignment::create([
            'survey_id' => Survey::where('is_default', true)->firstOrFail()->id,
            'survey_version_id' => SurveyVersion::where('is_active', true)->firstOrFail()->id,
            'user_id' => User::factory()->create()->id,
            'status' => 'pending',
        ]);
    }
}
