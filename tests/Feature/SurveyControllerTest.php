<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyItem;
use App\Models\SurveyPage;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurveyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_survey_definition_includes_question_count_and_estimated_minutes(): void
    {
        $assignment = $this->seedAssignmentWithQuestions(6);

        $response = $this->getJson(route('survey.definition', $assignment->token));

        $response->assertOk()
            ->assertJsonPath('survey_meta.question_count', 6)
            ->assertJsonPath('survey_meta.estimated_minutes', 4);
    }

    public function test_survey_show_records_employee_entry_view_event(): void
    {
        $assignment = $this->seedAssignmentWithQuestions(10);

        $response = $this->get(route('survey.take', $assignment->token));

        $response->assertOk();

        $event = DB::table('onboarding_events')
            ->where('name', 'employee_survey_entry_viewed')
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('survey.take', $event->context_surface);
        $this->assertSame($assignment->user_id, $event->user_id);
        $this->assertSame($assignment->user->company_id, $event->company_id);
    }

    public function test_canonical_instrument_definition_contains_62_purpose_bound_unique_questions_once(): void
    {
        $payload = json_decode(
            file_get_contents(base_path('survey_instrument.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
            'status' => 'active',
        ]);

        $this->assertSame(0, Artisan::call('survey:import', [
            'path' => base_path('survey_instrument.json'),
            '--activate' => true,
            '--approved-by' => $admin->id,
            '--change-summary' => 'Publish canonical instrument for definition contract verification.',
        ]));

        $version = SurveyVersion::query()
            ->where('instrument_id', $payload['instrument_id'])
            ->where('version', $payload['version'])
            ->latest('id')
            ->firstOrFail();

        $company = Companies::create([
            'title' => 'Canonical Co',
            'manager' => 'Manager',
            'manager_email' => 'canonical-manager@example.com',
        ]);
        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
        ]);
        $survey = Survey::where('is_default', true)->firstOrFail();
        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employee->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
        ]);

        $response = $this->getJson(route('survey.definition', $assignment->token))
            ->assertOk()
            ->assertJsonPath('survey_meta.question_count', 62);

        $definition = $response->json();
        $qids = collect($definition['pages'])
            ->flatMap(function (array $page) {
                $standalone = collect($page['items'] ?? [])->pluck('qid');
                $sectionItems = collect($page['sections'] ?? [])
                    ->flatMap(fn (array $section) => collect($section['items'] ?? [])->pluck('qid'));

                return $standalone->concat($sectionItems);
            })
            ->values();

        $this->assertCount(62, $qids);
        $this->assertCount(62, $qids->unique());
        $this->assertFalse($qids->contains(fn ($qid) => str_starts_with($qid, 'CONTACT_')));
        $this->assertFalse($qids->contains(fn ($qid) => str_starts_with($qid, 'DEM_')));
        $this->assertFalse($qids->contains(fn ($qid) => str_starts_with($qid, 'OC_')));
        $this->assertArrayNotHasKey('token', $definition['assignment']);
        $this->assertArrayNotHasKey('user', $definition['assignment']);
    }

    public function test_autosave_rejects_stale_revision_without_overwriting_newer_draft(): void
    {
        $assignment = $this->seedAssignmentWithQuestions(1);

        $this->postJson(route('survey.autosave', $assignment->token), [
            'responses' => ['Q_01' => 4],
            'revision' => 0,
        ])->assertOk()->assertJsonPath('revision', 1);

        $this->postJson(route('survey.autosave', $assignment->token), [
            'responses' => ['Q_01' => 5],
            'revision' => 0,
        ])->assertConflict()->assertJsonPath('revision', 1);

        $assignment->refresh();
        $this->assertSame(1, $assignment->draft_revision);
        $this->assertSame(4, $assignment->draft_answers['Q_01']);
    }

    public function test_expired_revoked_and_ineligible_wave_links_fail_closed(): void
    {
        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();
        $company = Companies::create([
            'title' => 'Access Co',
            'manager' => 'Manager',
            'manager_email' => 'access-manager@example.com',
        ]);
        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
        ]);

        $expired = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employee->id,
            'token' => 'expired-token',
            'status' => 'pending',
            'token_expires_at' => now()->subMinute(),
        ]);
        $revoked = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employee->id,
            'token' => 'revoked-token',
            'status' => 'pending',
            'token_revoked_at' => now(),
        ]);
        $futureWave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'scheduled',
            'cadence' => 'manual',
            'label' => 'Future Wave',
            'opens_at' => now()->addDay(),
            'due_at' => now()->addWeek(),
        ]);
        $future = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $futureWave->id,
            'user_id' => $employee->id,
            'token' => 'future-token',
            'status' => 'pending',
        ]);
        $inactiveEmployee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'status' => 'inactive',
        ]);
        $inactive = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $inactiveEmployee->id,
            'token' => 'inactive-user-token',
            'status' => 'pending',
        ]);

        $this->get(route('survey.take', $expired->token))->assertStatus(410);
        $this->get(route('survey.take', $revoked->token))->assertStatus(410);
        $this->get(route('survey.take', $future->token))->assertStatus(410);
        $this->get(route('survey.take', $inactive->token))->assertConflict();

        $this->assertNull($expired->fresh()->getRawOriginal('token'));
        $this->assertNull($revoked->fresh()->getRawOriginal('token'));
        $this->assertNull($future->fresh()->getRawOriginal('token'));
        $this->assertNull($inactive->fresh()->getRawOriginal('token'));
    }

    public function test_assignment_without_pinned_version_fails_closed(): void
    {
        $survey = Survey::where('is_default', true)->firstOrFail();
        $user = User::factory()->create(['role' => 4]);
        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => null,
            'user_id' => $user->id,
            'token' => 'unpinned-token',
            'status' => 'pending',
        ]);

        $this->getJson(route('survey.definition', $assignment->token))
            ->assertConflict();

        $this->assertNull($assignment->fresh()->survey_version_id);
    }

    protected function seedAssignmentWithQuestions(int $questionCount): SurveyAssignment
    {
        $company = Companies::create([
            'title' => 'Acme',
            'manager' => 'Manager',
            'manager_email' => 'manager@example.com',
        ]);

        $survey = Survey::where('is_default', true)->firstOrFail();
        $version = SurveyVersion::where('is_active', true)->firstOrFail();

        $page = SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'intro',
            'title' => 'Intro',
            'sort_order' => 1,
        ]);

        for ($index = 1; $index <= $questionCount; $index++) {
            SurveyItem::create([
                'survey_version_id' => $version->id,
                'survey_page_id' => $page->id,
                'qid' => sprintf('Q_%02d', $index),
                'type' => 'slider',
                'question' => "Question {$index}",
                'sort_order' => $index,
            ]);
        }

        $employee = User::factory()->create([
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'email' => 'employee@example.com',
        ]);

        return SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'user_id' => $employee->id,
            'token' => (string) Str::uuid(),
            'status' => 'pending',
            'wave_label' => 'March Pulse',
        ]);
    }
}
