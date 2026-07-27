<?php

namespace Tests\Feature;

use App\Jobs\ProcessSurveyWave;
use App\Models\Companies;
use App\Models\OrganizationAssignment;
use App\Models\OrganizationMembership;
use App\Models\OrganizationUnit;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveCycle;
use App\Models\User;
use App\Services\OrganizationEntitlementService;
use App\Services\OrganizationService;
use App\Services\SurveyService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationCohortIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_prior_wave_cohort_and_denominator_survive_roster_changes(): void
    {
        [$company, $wave, $employee] = $this->waveFixture();
        Queue::fake();

        (new ProcessSurveyWave($wave->id))->handle(app(SurveyService::class));

        $cycle = SurveyWaveCycle::query()->sole();
        $assignment = $wave->assignments()->where('user_id', $employee->id)->sole();

        $this->assertSame(1, $cycle->audience_count);
        $this->assertSame('Operations', $assignment->cohort_snapshot['department']);
        $this->assertNotNull($cycle->audience_hash);
        $this->assertNotNull($cycle->instrument_hash);
        $this->assertNotNull($cycle->metric_definition_hash);

        DB::table('company_worker')
            ->where('company_id', $company->id)
            ->where('email', $employee->email)
            ->update(['department' => 'Finance', 'status' => 'inactive']);
        $employee->update(['status' => 'inactive', 'left_at' => now()]);
        app(OrganizationService::class)->synchronize(
            $employee,
            null,
            'Finance',
            null,
            'inactive'
        );

        $assignment->refresh();
        $cycle->refresh();
        $this->assertSame('Operations', $assignment->cohort_snapshot['department']);
        $this->assertSame(1, $cycle->audience_count);
        $this->assertSame(1, $cycle->audienceMembers()->count());

        $response = app(SurveyService::class)->recordResponse($assignment, []);
        $this->assertSame('Operations', $response->cohort_snapshot['department']);
    }

    public function test_membership_and_unit_changes_are_effective_dated_and_tenant_is_restricted(): void
    {
        [$company, $wave, $employee, $manager] = $this->waveFixture();
        $organizations = app(OrganizationService::class);

        $first = $organizations->synchronize($employee, $manager, 'Operations', null, 'active');
        $employee->update(['role' => 3]);
        $second = $organizations->synchronize($employee->fresh(), $manager, 'Operations', null, 'active');
        $organizations->synchronize($employee->fresh(), $manager, 'Finance', null, 'active');

        $this->assertNotSame($first->id, $second->id);
        $this->assertNotNull($first->fresh()->valid_to);
        $this->assertSame(2, OrganizationMembership::where('user_id', $employee->id)->count());
        $this->assertSame(
            3,
            OrganizationAssignment::whereIn(
                'membership_id',
                OrganizationMembership::where('user_id', $employee->id)->pluck('id')
            )->count()
        );

        OrganizationUnit::create([
            'company_id' => $company->id,
            'stable_key' => (string) Str::uuid(),
            'type' => 'team',
            'name' => 'Shared Name',
            'status' => 'active',
            'valid_from' => now(),
        ]);
        OrganizationUnit::create([
            'company_id' => $company->id,
            'stable_key' => (string) Str::uuid(),
            'type' => 'department',
            'name' => 'Shared Name',
            'status' => 'active',
            'valid_from' => now(),
        ]);
        $this->assertSame(2, OrganizationUnit::where('name', 'Shared Name')->count());

        $this->expectException(QueryException::class);
        $company->delete();
    }

    private function waveFixture(): array
    {
        $company = Companies::create([
            'title' => 'Cohort Co',
            'manager' => 'Manager',
            'manager_email' => 'manager@cohort.test',
        ]);
        $manager = User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@cohort.test',
            'company_id' => $company->id,
            'company_title' => $company->title,
            'company' => 1,
            'role' => 1,
            'tariff' => 1,
            'status' => 'active',
        ]);
        app(OrganizationEntitlementService::class)->grantManual(
            $company,
            'pulse',
            now()->addYear()
        );
        $employee = User::factory()->create([
            'name' => 'Employee',
            'email' => 'employee@cohort.test',
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 4,
            'status' => 'active',
        ]);
        DB::table('company_worker')->insert([
            'company_id' => $company->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'company_title' => $company->title,
            'department' => 'Operations',
            'supervisor' => null,
            'role' => 4,
            'status' => 'active',
        ]);
        $survey = Survey::create([
            'company_id' => $company->id,
            'title' => 'Cohort Survey',
            'is_default' => true,
        ]);
        $version = SurveyVersion::create([
            'instrument_id' => 'cohort-test',
            'version' => '1.0.0',
            'title' => 'Cohort Survey',
            'is_active' => true,
        ]);
        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'status' => 'processing',
            'cadence' => 'manual',
            'label' => 'Baseline',
            'target_roles' => [4],
        ]);

        return [$company, $wave, $employee, $manager];
    }
}
