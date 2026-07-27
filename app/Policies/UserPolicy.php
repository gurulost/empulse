<?php

namespace App\Policies;

use App\Models\User;
use App\Services\OrganizationScopeService;

class UserPolicy
{
    public function __construct(protected OrganizationScopeService $scope) {}

    public function viewAny(User $authUser): bool
    {
        return $authUser->hasCapability('team.manage');
    }

    public function view(User $authUser, User $target): bool
    {
        return $this->scope->canView($authUser, $target);
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasCapability('team.manage');
    }

    public function update(User $authUser, User $target): bool
    {
        return $this->scope->canManage($authUser, $target);
    }

    public function delete(User $authUser, User $target): bool
    {
        // Deletion follows same constraints as update
        return $this->update($authUser, $target);
    }
}
