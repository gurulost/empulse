<?php

namespace Database\Seeders;

use App\Models\Companies;
use App\Models\MetricRegistryVersion;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyItem;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveCycle;
use App\Models\User;
use App\Services\MetricRegistryService;
use App\Services\OrganizationEntitlementService;
use App\Services\SurveyVersionIntegrityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Companies::create([
            'title' => 'Acme Corp',
            'manager' => 'Manager User',
            'manager_email' => 'manager@acme.com',
        ]);
        $password = Hash::make('password');

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@workfit.com',
            'password' => $password,
            'role' => 0,
            'company_id' => null,
            'is_admin' => 1,
            'status' => 'active',
        ]);
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@acme.com',
            'password' => $password,
            'role' => 1,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'tariff' => 1,
            'company' => 1,
            'status' => 'active',
        ]);
        $entitlements = app(OrganizationEntitlementService::class);
        $entitlements->ensureBillingOwner($company, $manager);
        $entitlements->grantManual($company, 'pulse', now()->addYear(), $manager);

        foreach ([
            ['Chief User', 'chief@acme.com', 2],
            ['Team Lead', 'lead@acme.com', 3],
        ] as [$name, $email, $role]) {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'company_id' => $company->id,
                'company_title' => $company->title,
                'status' => 'active',
            ]);
        }

        $employees = collect(range(1, 5))->map(fn (int $number) => User::create([
            'name' => "Employee {$number}",
            'email' => "employee{$number}@acme.com",
            'password' => $password,
            'role' => 4,
            'company_id' => $company->id,
            'company_title' => $company->title,
            'status' => 'active',
        ]));

        $exitCode = Artisan::call('survey:import', [
            'path' => base_path('survey_instrument.json'),
            '--activate' => true,
        ]);
        if ($exitCode !== 0) {
            throw new \RuntimeException('The canonical WorkFit instrument could not be imported.');
        }

        $version = SurveyVersion::query()
            ->where('instrument_id', 'empulse_workfit_baseline')
            ->where('is_active', true)
            ->firstOrFail();
        $contentHash = app(SurveyVersionIntegrityService::class)->semanticHash($version);
        $version->update([
            'content_hash' => $contentHash,
            'published_at' => now(),
        ]);
        $survey = Survey::where('is_default', true)->orderBy('id')->first()
            ?? Survey::create(['title' => 'WorkFit Baseline', 'is_default' => true]);
        $survey->update([
            'instrument_id' => $version->instrument_id,
            'title' => 'WorkFit Baseline',
            'description' => 'Canonical 62-item WorkFit baseline diagnostic.',
            'status' => 'published',
            'is_default' => true,
        ]);

        $registry = app(MetricRegistryService::class)->publishedVersion();
        $items = SurveyItem::where('survey_version_id', $version->id)
            ->orderBy('id')
            ->get();
        if ($items->count() !== 62) {
            throw new \RuntimeException("Canonical seed expected 62 items; imported {$items->count()}.");
        }

        $pastWave = $this->wave(
            $company,
            $survey,
            $version,
            'WorkFit Baseline — prior cycle',
            'completed',
            now()->subMonths(2),
            now()->subMonths(2)->addDays(14)
        );
        $pastCycle = $this->cycle($pastWave, $contentHash, $registry, 'completed');
        foreach ($employees as $index => $employee) {
            $this->completedAssignment(
                $employee,
                $survey,
                $version,
                $pastWave,
                $pastCycle,
                $items,
                4 + ($index % 3)
            );
        }

        $currentWave = $this->wave(
            $company,
            $survey,
            $version,
            'WorkFit Baseline — current cycle',
            'active',
            now()->subDay(),
            now()->addDays(14)
        );
        $currentCycle = $this->cycle($currentWave, $contentHash, $registry, 'active');
        foreach ($employees as $index => $employee) {
            if ($index < 2) {
                $this->completedAssignment(
                    $employee,
                    $survey,
                    $version,
                    $currentWave,
                    $currentCycle,
                    $items,
                    6 + $index
                );

                continue;
            }

            SurveyAssignment::create([
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $currentWave->id,
                'survey_wave_cycle_id' => $currentCycle->id,
                'user_id' => $employee->id,
                'status' => 'pending',
                'wave_label' => $currentWave->label,
                'last_dispatched_at' => now(),
                'dispatch_count' => 1,
                'invite_status' => 'accepted',
                'invited_at' => now(),
                'due_at' => $currentWave->due_at,
                'cohort_snapshot' => [
                    'company_id' => $company->id,
                    'role' => 4,
                ],
            ]);
        }
    }

    protected function wave(
        Companies $company,
        Survey $survey,
        SurveyVersion $version,
        string $label,
        string $status,
        \DateTimeInterface $opensAt,
        \DateTimeInterface $dueAt
    ): SurveyWave {
        return SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => $label,
            'status' => $status,
            'cadence' => 'manual',
            'target_roles' => [4],
            'opens_at' => $opensAt,
            'due_at' => $dueAt,
            'last_dispatched_at' => $opensAt,
        ]);
    }

    protected function cycle(
        SurveyWave $wave,
        string $contentHash,
        MetricRegistryVersion $registry,
        string $status
    ): SurveyWaveCycle {
        return SurveyWaveCycle::create([
            'survey_wave_id' => $wave->id,
            'sequence' => 1,
            'status' => $status,
            'instrument_hash' => $contentHash,
            'metric_definition_hash' => $registry->definition_hash,
            'metric_registry_version_id' => $registry->id,
            'audience_hash' => hash('sha256', "demo-audience:{$wave->id}"),
            'audience_count' => 5,
            'frozen_at' => $wave->opens_at,
            'dispatched_at' => $wave->opens_at,
            'due_at' => $wave->due_at,
        ]);
    }

    protected function completedAssignment(
        User $employee,
        Survey $survey,
        SurveyVersion $version,
        SurveyWave $wave,
        SurveyWaveCycle $cycle,
        $items,
        int $baseValue
    ): void {
        $submittedAt = $wave->opens_at->copy()->addDays(2);
        $snapshot = [
            'company_id' => $wave->company_id,
            'role' => 4,
        ];
        $assignment = SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'survey_wave_cycle_id' => $cycle->id,
            'user_id' => $employee->id,
            'status' => 'completed',
            'wave_label' => $wave->label,
            'last_dispatched_at' => $wave->opens_at,
            'dispatch_count' => 1,
            'invite_status' => 'delivered',
            'invited_at' => $wave->opens_at,
            'due_at' => $wave->due_at,
            'completed_at' => $submittedAt,
            'token_revoked_at' => $submittedAt,
            'cohort_snapshot' => $snapshot,
            'privacy_policy_version' => config('privacy.respondent_policy.version'),
            'privacy_acknowledged_at' => $submittedAt->copy()->subMinutes(8),
        ]);
        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'assignment_id' => $assignment->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'survey_wave_cycle_id' => $cycle->id,
            'user_id' => $employee->id,
            'wave_label' => $wave->label,
            'submitted_at' => $submittedAt,
            'duration_ms' => 480_000,
            'cohort_snapshot' => $snapshot,
            'privacy_policy_version' => config('privacy.respondent_policy.version'),
            'metric_registry_version_id' => $cycle->metric_registry_version_id,
            'metric_definition_hash' => $cycle->metric_definition_hash,
        ]);

        foreach ($items as $offset => $item) {
            $maximum = str_starts_with($item->qid, 'TC_') || str_starts_with($item->qid, 'WEL_')
                ? 9
                : 10;
            $value = min($maximum, max(1, $baseValue + ($offset % 2)));
            SurveyAnswer::create([
                'response_id' => $response->id,
                'question_id' => $item->id,
                'survey_item_id' => $item->id,
                'question_key' => $item->qid,
                'value' => (string) $value,
                'value_numeric' => (float) $value,
                'metadata' => [
                    'type' => $item->type,
                    'page_id' => $item->survey_page_id,
                    'section_id' => $item->survey_section_id,
                    ...($item->metadata ?? []),
                ],
            ]);
        }
    }
}
