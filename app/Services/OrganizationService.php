<?php

namespace App\Services;

use App\Models\OrganizationAssignment;
use App\Models\OrganizationMembership;
use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationService
{
    public function __construct(protected AuditTrailService $audit) {}

    public function synchronize(
        User $user,
        ?User $actor = null,
        ?string $department = null,
        ?string $supervisorEmail = null,
        ?string $status = null
    ): OrganizationMembership {
        if (! $user->company_id) {
            throw new \DomainException('Organization membership requires a company.');
        }

        $membership = DB::transaction(function () use ($user, $actor, $department, $supervisorEmail, $status) {
            $now = now();
            $membershipStatus = $status ?: ($user->status ?: 'active');
            $membership = OrganizationMembership::query()
                ->where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->whereNull('valid_to')
                ->lockForUpdate()
                ->first();

            if (! $membership
                || (int) $membership->role !== (int) $user->role
                || $membership->status !== $membershipStatus) {
                $membership?->update(['valid_to' => $now]);
                $membership = OrganizationMembership::create([
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'role' => (int) $user->role,
                    'status' => $membershipStatus,
                    'valid_from' => $now,
                    'created_by' => $actor?->id,
                ]);
            }

            $unit = $this->resolveUnit((int) $user->company_id, $department, $now);
            $managerMembership = $this->resolveManagerMembership(
                (int) $user->company_id,
                $supervisorEmail
            );
            $unresolvedReason = $this->unresolvedReason($department, $unit, $supervisorEmail, $managerMembership);

            $current = OrganizationAssignment::query()
                ->where('membership_id', $membership->id)
                ->whereNull('valid_to')
                ->lockForUpdate()
                ->first();
            $desiredUnitId = $unit?->id;
            $desiredManagerId = $managerMembership?->id;

            if (! $current
                || (int) ($current->organization_unit_id ?? 0) !== (int) ($desiredUnitId ?? 0)
                || (int) ($current->reports_to_membership_id ?? 0) !== (int) ($desiredManagerId ?? 0)
                || $current->unresolved_reason !== $unresolvedReason
                || $current->status !== $membershipStatus) {
                $current?->update(['valid_to' => $now]);
                OrganizationAssignment::create([
                    'membership_id' => $membership->id,
                    'organization_unit_id' => $desiredUnitId,
                    'reports_to_membership_id' => $desiredManagerId,
                    'status' => $membershipStatus,
                    'unresolved_reason' => $unresolvedReason,
                    'valid_from' => $now,
                    'created_by' => $actor?->id,
                ]);
            }

            return $membership->fresh('currentAssignment');
        });

        $this->audit->record(
            'organization.membership_synchronized',
            $actor,
            (int) $user->company_id,
            User::class,
            $user->id,
            [
                'role' => $membership->role,
                'status' => $membership->status,
                'organization_unit_id' => $membership->currentAssignment?->organization_unit_id,
                'reports_to_membership_id' => $membership->currentAssignment?->reports_to_membership_id,
                'unresolved_reason' => $membership->currentAssignment?->unresolved_reason,
            ]
        );

        return $membership;
    }

    public function deactivate(User $user, ?User $actor = null): void
    {
        $worker = DB::table('company_worker')
            ->where('company_id', $user->company_id)
            ->where('email', $user->email)
            ->first();

        $this->synchronize(
            $user->forceFill(['status' => 'inactive']),
            $actor,
            $worker?->department,
            $this->supervisorEmail((int) $user->company_id, $worker?->supervisor),
            'inactive'
        );
    }

    protected function resolveUnit(int $companyId, ?string $department, $now): ?OrganizationUnit
    {
        $name = trim((string) $department);
        if ($name === '' || strcasecmp($name, 'None department') === 0) {
            return null;
        }

        return OrganizationUnit::firstOrCreate(
            [
                'company_id' => $companyId,
                'type' => 'department',
                'name' => $name,
                'status' => 'active',
                'valid_to' => null,
            ],
            [
                'stable_key' => (string) Str::uuid(),
                'valid_from' => $now,
            ]
        );
    }

    protected function resolveManagerMembership(int $companyId, ?string $supervisorEmail): ?OrganizationMembership
    {
        if (! $supervisorEmail) {
            return null;
        }

        $supervisor = User::query()
            ->where('company_id', $companyId)
            ->where('email', $supervisorEmail)
            ->first();

        if (! $supervisor) {
            return null;
        }

        return OrganizationMembership::query()
            ->where('company_id', $companyId)
            ->where('user_id', $supervisor->id)
            ->where('status', 'active')
            ->whereNull('valid_to')
            ->latest('id')
            ->first();
    }

    protected function unresolvedReason(
        ?string $department,
        ?OrganizationUnit $unit,
        ?string $supervisorEmail,
        ?OrganizationMembership $managerMembership
    ): ?string {
        if (trim((string) $department) !== ''
            && strcasecmp(trim((string) $department), 'None department') !== 0
            && ! $unit) {
            return 'organization_unit_unresolved';
        }

        if ($supervisorEmail && ! $managerMembership) {
            return 'reporting_relationship_unresolved';
        }

        if (! $unit) {
            return 'organization_unit_unassigned';
        }

        return null;
    }

    public function supervisorEmail(int $companyId, ?string $supervisorName): ?string
    {
        if (! $supervisorName) {
            return null;
        }

        return DB::table('company_worker')
            ->where('company_id', $companyId)
            ->where('name', $supervisorName)
            ->where('status', 'active')
            ->value('email');
    }
}
