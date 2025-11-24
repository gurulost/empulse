<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Companies;
use App\Models\User;

class TeamManagementPolicy
{
    public function before(User $user, string $ability)
    {
        if ((int) ($user->is_admin ?? 0) === 1) {
            return true;
        }
    }

    public function manageMembers(User $user, Companies $company): bool
    {
        if (!$this->belongsToCompany($user, $company)) {
            return false;
        }

        return in_array((int) $user->role, [
            Role::ADMIN->value,
            Role::MANAGER->value,
            Role::CHIEF->value,
            Role::TEAMLEAD->value,
        ], true);
    }

    public function manageDepartments(User $user, Companies $company): bool
    {
        if (!$this->belongsToCompany($user, $company)) {
            return false;
        }

        return in_array((int) $user->role, [
            Role::ADMIN->value,
            Role::MANAGER->value,
            Role::CHIEF->value,
        ], true);
    }

    protected function belongsToCompany(User $user, Companies $company): bool
    {
        return (int) ($user->company_id ?? 0) === (int) $company->id;
    }
}
