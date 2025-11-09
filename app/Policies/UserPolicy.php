<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserPolicy
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

    public function view(User $authUser, User $target): bool
    {
        return (int)$authUser->company_id === (int)$target->company_id;
    }

    public function create(User $authUser): bool
    {
        return (int)$authUser->role !== 4; // not employee
    }

    public function update(User $authUser, User $target): bool
    {
        if ((int)$authUser->company_id !== (int)$target->company_id) {
            return false;
        }

        // Manager can manage anyone in company (except superadmin handled by before)
        if ((int)$authUser->role === 1) {
            return true;
        }

        // Chief can manage teamleads and employees in their department
        if ((int)$authUser->role === 2) {
            $authDept = DB::table('company_worker')->where(['company_id' => $authUser->company_id, 'email' => $authUser->email])->value('department');
            $targetRow = DB::table('company_worker')->where(['company_id' => $authUser->company_id, 'email' => $target->email])->first();
            if (!$targetRow) return false;
            return in_array((int)$targetRow->role, [3,4], true) && $targetRow->department === $authDept;
        }

        // Teamlead can manage employees they supervise
        if ((int)$authUser->role === 3) {
            $targetRow = DB::table('company_worker')->where(['company_id' => $authUser->company_id, 'email' => $target->email])->first();
            if (!$targetRow) return false;
            return (int)$targetRow->role === 4 && $targetRow->supervisor === $authUser->name;
        }

        return false;
    }

    public function delete(User $authUser, User $target): bool
    {
        // Deletion follows same constraints as update
        return $this->update($authUser, $target);
    }
}

