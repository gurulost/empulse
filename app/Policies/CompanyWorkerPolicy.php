<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CompanyWorker;

class CompanyWorkerPolicy
{
    public function before(User $authUser, $ability)
    {
        if ((int)($authUser->is_admin ?? 0) === 1) {
            return true;
        }
    }

    public function viewAny(User $authUser): bool
    {
        return true;
    }

    public function view(User $authUser, CompanyWorker $target): bool
    {
        return (int)$authUser->company_id === (int)$target->company_id;
    }

    public function create(User $authUser): bool
    {
        return (int)$authUser->role !== 4; // not employee
    }

    public function update(User $authUser, CompanyWorker $target): bool
    {
        if ((int)$authUser->company_id !== (int)$target->company_id) {
            return false;
        }
        if ((int)$authUser->role === 1) {
            return true;
        }
        if ((int)$authUser->role === 2) {
            return in_array((int)$target->role, [3,4], true) && $target->department === optional(CompanyWorker::where(['company_id' => $authUser->company_id, 'email' => $authUser->email])->first())->department;
        }
        if ((int)$authUser->role === 3) {
            return (int)$target->role === 4 && $target->supervisor === $authUser->name;
        }
        return false;
    }

    public function delete(User $authUser, CompanyWorker $target): bool
    {
        return $this->update($authUser, $target);
    }
}

