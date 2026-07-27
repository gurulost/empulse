<?php

namespace App\Services;

use App\Models\OrganizationMembership;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\SurveyWaveAudienceMember;
use App\Models\SurveyWaveCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SurveyCohortService
{
    public function __construct(
        protected OrganizationService $organizations,
        protected AuditTrailService $audit,
        protected SurveyVersionIntegrityService $versionIntegrity,
        protected MetricRegistryService $metricRegistry,
        protected PulseFatiguePolicyService $fatiguePolicy
    ) {}

    public function freeze(SurveyWave $wave, array $targetRoles): SurveyWaveCycle
    {
        $cycle = DB::transaction(function () use ($wave, $targetRoles) {
            $lockedWave = SurveyWave::query()->whereKey($wave->id)->lockForUpdate()->firstOrFail();
            $existing = SurveyWaveCycle::query()
                ->where('survey_wave_id', $lockedWave->id)
                ->where('status', 'preparing')
                ->latest('sequence')
                ->first();

            if (! $existing && $lockedWave->kind === 'full') {
                $existing = SurveyWaveCycle::query()
                    ->where('survey_wave_id', $lockedWave->id)
                    ->latest('sequence')
                    ->first();
            }

            if ($existing) {
                return $existing->load('audienceMembers');
            }

            $sequence = ((int) SurveyWaveCycle::query()
                ->where('survey_wave_id', $lockedWave->id)
                ->max('sequence')) + 1;
            $metricRegistry = $this->metricRegistry->publishedVersion();
            $cycle = SurveyWaveCycle::create([
                'survey_wave_id' => $lockedWave->id,
                'sequence' => $sequence,
                'status' => 'preparing',
                'instrument_hash' => $this->instrumentHash($lockedWave),
                'metric_definition_hash' => $metricRegistry->definition_hash,
                'metric_registry_version_id' => $metricRegistry->id,
                'due_at' => $lockedWave->due_at,
            ]);

            $users = User::query()
                ->where('company_id', $lockedWave->company_id)
                ->where('status', 'active')
                ->whereIn('role', $targetRoles)
                ->orderBy('id')
                ->get();

            $snapshots = [];
            foreach ($users as $user) {
                $eligibility = $this->fatiguePolicy->eligibility($user, $lockedWave);
                if (! $eligibility['eligible']) {
                    DB::table('survey_wave_audience_exclusions')->insert([
                        'survey_wave_cycle_id' => $cycle->id,
                        'user_id' => $user->id,
                        'reason' => $eligibility['reason'],
                        'policy_snapshot' => json_encode($eligibility['policy'], JSON_THROW_ON_ERROR),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    continue;
                }

                $worker = DB::table('company_worker')
                    ->where('company_id', $lockedWave->company_id)
                    ->where('email', $user->email)
                    ->first();
                $membership = $this->currentMembership($user)
                    ?: $this->organizations->synchronize(
                        $user,
                        null,
                        $worker?->department,
                        $this->organizations->supervisorEmail(
                            (int) $lockedWave->company_id,
                            $worker?->supervisor
                        ),
                        'active'
                    );
                $membership->loadMissing('currentAssignment');
                $assignment = $membership->currentAssignment;
                $unit = $assignment?->organization_unit_id
                    ? DB::table('organization_units')->find($assignment->organization_unit_id)
                    : null;
                $manager = $assignment?->reports_to_membership_id
                    ? OrganizationMembership::with('currentAssignment')->find($assignment->reports_to_membership_id)
                    : null;
                $managerUser = $manager ? User::find($manager->user_id) : null;

                $snapshot = [
                    'company_id' => (int) $lockedWave->company_id,
                    'membership_id' => $membership->id,
                    'user_id' => $user->id,
                    'role' => (int) $membership->role,
                    'organization_unit_id' => $assignment?->organization_unit_id,
                    'organization_unit_key' => $unit?->stable_key,
                    'department' => $unit?->name,
                    'reports_to_membership_id' => $assignment?->reports_to_membership_id,
                    'team' => $managerUser?->name,
                    'unresolved_reason' => $assignment?->unresolved_reason,
                    'membership_valid_from' => $membership->valid_from?->toAtomString(),
                ];

                SurveyWaveAudienceMember::create([
                    'survey_wave_cycle_id' => $cycle->id,
                    'user_id' => $user->id,
                    'organization_membership_id' => $membership->id,
                    'organization_unit_id' => $assignment?->organization_unit_id,
                    'role' => (int) $membership->role,
                    'snapshot' => $snapshot,
                ]);
                $snapshots[] = $snapshot;
            }

            $audienceHash = hash('sha256', json_encode($snapshots, JSON_UNESCAPED_SLASHES));
            $cycle->update([
                'status' => 'frozen',
                'audience_hash' => $audienceHash,
                'audience_count' => count($snapshots),
                'frozen_at' => now(),
            ]);

            return $cycle->fresh('audienceMembers');
        });

        $this->audit->record(
            'survey.cohort_frozen',
            auth()->user(),
            (int) $wave->company_id,
            SurveyWaveCycle::class,
            $cycle->id,
            [
                'survey_wave_id' => $wave->id,
                'sequence' => $cycle->sequence,
                'audience_count' => $cycle->audience_count,
                'audience_hash' => $cycle->audience_hash,
                'instrument_hash' => $cycle->instrument_hash,
                'metric_definition_hash' => $cycle->metric_definition_hash,
            ]
        );

        return $cycle;
    }

    public function markDispatched(SurveyWaveCycle $cycle): void
    {
        $cycle->update([
            'status' => 'active',
            'dispatched_at' => now(),
        ]);
    }

    protected function currentMembership(User $user): ?OrganizationMembership
    {
        return OrganizationMembership::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('valid_to')
            ->latest('id')
            ->first();
    }

    protected function instrumentHash(SurveyWave $wave): string
    {
        $version = SurveyVersion::query()->find($wave->survey_version_id);

        if (! $version) {
            throw new \DomainException('Wave is not pinned to a survey version.');
        }

        return $version->content_hash ?: $this->versionIntegrity->semanticHash($version);
    }
}
