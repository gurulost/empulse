<?php

namespace App\Policies;

use App\Models\CompanyWorker;
use App\Models\User;
use App\Models\User as UserModel;
use App\Services\OrganizationScopeService;

class CompanyWorkerPolicy
{
    public function __construct(protected OrganizationScopeService $scope) {}

    public function viewAny(User $authUser): bool
    {
        return $authUser->hasCapability('team.manage');
    }

    public function view(User $authUser, CompanyWorker $target): bool
    {
        $targetUser = UserModel::query()
            ->where('company_id', $target->company_id)
            ->where('email', $target->email)
            ->first();

        return $targetUser ? $this->scope->canView($authUser, $targetUser) : false;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasCapability('team.manage');
    }

    public function update(User $authUser, CompanyWorker $target): bool
    {
        $targetUser = UserModel::query()
            ->where('company_id', $target->company_id)
            ->where('email', $target->email)
            ->first();

        return $targetUser ? $this->scope->canManage($authUser, $targetUser) : false;
    }

    public function delete(User $authUser, CompanyWorker $target): bool
    {
        return $this->update($authUser, $target);
    }
}
