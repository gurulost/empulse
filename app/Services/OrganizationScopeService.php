<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\OrganizationMembership;
use App\Models\User;

class OrganizationScopeService
{
    public function currentMembership(User $user): ?OrganizationMembership
    {
        return OrganizationMembership::query()
            ->with('currentAssignment')
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('valid_to')
            ->latest('id')
            ->first();
    }

    public function currentRole(User $user): int
    {
        return (int) ($this->currentMembership($user)?->role ?? $user->role);
    }

    public function canManage(User $actor, User $target): bool
    {
        if (! $actor->company_id
            || (int) $actor->company_id !== (int) $target->company_id
            || $actor->status === 'inactive') {
            return false;
        }

        $actorMembership = $this->currentMembership($actor);
        $targetMembership = $this->currentMembership($target);
        if (! $actorMembership || ! $targetMembership) {
            return $this->legacyCanManage($actor, $target);
        }

        return match ((int) $actorMembership->role) {
            Role::MANAGER->value => true,
            Role::CHIEF->value => $this->chiefCanManage($actorMembership, $targetMembership),
            Role::TEAMLEAD->value => $this->teamLeadCanManage($actorMembership, $targetMembership),
            default => false,
        };
    }

    public function canView(User $actor, User $target): bool
    {
        return $actor->is($target) || $this->canManage($actor, $target);
    }

    /**
     * Return immutable cohort constraints for analytics/reporting.
     *
     * Managers and platform administrators may see the company-wide aggregate.
     * Chiefs are constrained to their assigned organization unit; team leads
     * are constrained to respondents whose frozen cohort reported to them.
     * Missing hierarchy truth fails closed.
     */
    public function analyticsFilters(User $actor): array
    {
        $role = $this->currentRole($actor);
        if ($role === Role::ADMIN->value || $role === Role::MANAGER->value) {
            return [];
        }

        $membership = $this->currentMembership($actor);
        if (! $membership) {
            return ['deny_all' => true];
        }

        if ($role === Role::CHIEF->value) {
            $unitId = (int) ($membership->currentAssignment?->organization_unit_id ?? 0);

            return $unitId > 0
                ? ['organization_unit_id' => $unitId]
                : ['deny_all' => true];
        }

        if ($role === Role::TEAMLEAD->value) {
            return ['reports_to_membership_id' => (int) $membership->id];
        }

        return ['deny_all' => true];
    }

    public function canViewCohort(User $actor, ?array $cohort): bool
    {
        $scope = $this->analyticsFilters($actor);
        if ($scope === []) {
            return true;
        }
        if (($scope['deny_all'] ?? false) || ! is_array($cohort)) {
            return false;
        }
        if (isset($scope['organization_unit_id'])) {
            return (int) ($cohort['organization_unit_id'] ?? 0)
                === (int) $scope['organization_unit_id'];
        }
        if (isset($scope['reports_to_membership_id'])) {
            return (int) ($cohort['reports_to_membership_id'] ?? 0)
                === (int) $scope['reports_to_membership_id'];
        }

        return false;
    }

    protected function chiefCanManage(
        OrganizationMembership $actor,
        OrganizationMembership $target
    ): bool {
        if (! in_array((int) $target->role, [Role::TEAMLEAD->value, Role::EMPLOYEE->value], true)) {
            return false;
        }

        $actorUnit = $actor->currentAssignment?->organization_unit_id;
        $targetUnit = $target->currentAssignment?->organization_unit_id;

        return $actorUnit !== null && (int) $actorUnit === (int) $targetUnit;
    }

    protected function teamLeadCanManage(
        OrganizationMembership $actor,
        OrganizationMembership $target
    ): bool {
        return (int) $target->role === Role::EMPLOYEE->value
            && (int) ($target->currentAssignment?->reports_to_membership_id ?? 0) === (int) $actor->id;
    }

    protected function legacyCanManage(User $actor, User $target): bool
    {
        if ((int) $actor->role === Role::MANAGER->value) {
            return true;
        }

        return false;
    }
}
