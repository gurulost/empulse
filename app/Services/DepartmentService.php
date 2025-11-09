<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DepartmentService
{
    protected string $table = 'company_department';
    protected string $workers = 'company_worker';

    public function list(int $companyId, int $perPage = 8)
    {
        return DB::table($this->table)
            ->where('company_id', $companyId)
            ->select('id','title')
            ->orderBy('id','asc')
            ->paginate($perPage);
    }

    public function add(int $companyId, string $title): array
    {
        try {
            $exists = DB::table($this->table)->where(['company_id' => $companyId, 'title' => $title])->exists();
            if ($exists) {
                return ['status' => 500, 'message' => 'The department exists!'];
            }
            DB::table($this->table)->insert([
                'company_id' => $companyId,
                'title' => $title,
            ]);
            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function update(int $companyId, string $oldTitle, string $newTitle): array
    {
        try {
            if ($newTitle === $oldTitle) {
                return ['status' => 200, 'title' => $newTitle];
            }
            $exists = DB::table($this->table)->where(['company_id' => $companyId, 'title' => $newTitle])->exists();
            if ($exists) {
                return ['status' => 500, 'message' => 'The department exists!', 'title' => $oldTitle];
            }
            DB::table($this->table)->where(['company_id' => $companyId, 'title' => $oldTitle])->update(['title' => $newTitle]);
            // Optionally update workers referencing this department
            DB::table($this->workers)->where(['company_id' => $companyId, 'department' => $oldTitle])->update(['department' => $newTitle]);
            return ['status' => 200, 'title' => $newTitle];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage(), 'title' => $oldTitle];
        }
    }

    public function delete(int $companyId, string $title): array
    {
        try {
            $workers = DB::table($this->workers)->where(['company_id' => $companyId, 'department' => $title])->count('email');
            if ($workers > 0) {
                return ['status' => 500, 'message' => 'You can not delete department, if it has workers!'];
            }
            DB::table($this->table)->where(['company_id' => $companyId, 'title' => $title])->delete();
            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}

