<?php

namespace App\Services;

use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentService
{
    protected string $table = 'company_department';

    protected string $workers = 'company_worker';

    public function __construct(protected OrganizationService $organizations) {}

    public function list(int $companyId, int $perPage = 8)
    {
        return DB::table($this->table)
            ->where('company_id', $companyId)
            ->select('id', 'title')
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }

    public function add(int $companyId, string $title): array
    {
        try {
            $exists = DB::table($this->table)->where(['company_id' => $companyId, 'title' => $title])->exists();
            if ($exists) {
                return ['status' => 500, 'message' => 'The department exists!'];
            }
            DB::transaction(function () use ($companyId, $title) {
                DB::table($this->table)->insert([
                    'company_id' => $companyId,
                    'title' => $title,
                ]);
                OrganizationUnit::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'type' => 'department',
                        'name' => $title,
                        'status' => 'active',
                        'valid_to' => null,
                    ],
                    [
                        'stable_key' => (string) Str::uuid(),
                        'valid_from' => now(),
                    ]
                );
            });

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
            DB::transaction(function () use ($companyId, $oldTitle, $newTitle) {
                DB::table($this->table)
                    ->where(['company_id' => $companyId, 'title' => $oldTitle])
                    ->update(['title' => $newTitle]);
                OrganizationUnit::query()
                    ->where('company_id', $companyId)
                    ->where('type', 'department')
                    ->where('name', $oldTitle)
                    ->whereNull('valid_to')
                    ->update([
                        'status' => 'renamed',
                        'valid_to' => now(),
                    ]);
                OrganizationUnit::create([
                    'company_id' => $companyId,
                    'stable_key' => (string) Str::uuid(),
                    'type' => 'department',
                    'name' => $newTitle,
                    'status' => 'active',
                    'valid_from' => now(),
                ]);

                $workers = DB::table($this->workers)
                    ->where(['company_id' => $companyId, 'department' => $oldTitle])
                    ->get();
                DB::table($this->workers)
                    ->where(['company_id' => $companyId, 'department' => $oldTitle])
                    ->update(['department' => $newTitle]);

                foreach ($workers as $worker) {
                    $user = User::query()
                        ->where('company_id', $companyId)
                        ->where('email', $worker->email)
                        ->first();
                    if ($user) {
                        $this->organizations->synchronize(
                            $user,
                            auth()->user(),
                            $newTitle,
                            $this->organizations->supervisorEmail($companyId, $worker->supervisor),
                            $user->status
                        );
                    }
                }
            });

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
            DB::transaction(function () use ($companyId, $title) {
                DB::table($this->table)
                    ->where(['company_id' => $companyId, 'title' => $title])
                    ->delete();
                OrganizationUnit::query()
                    ->where('company_id', $companyId)
                    ->where('type', 'department')
                    ->where('name', $title)
                    ->whereNull('valid_to')
                    ->update([
                        'status' => 'inactive',
                        'valid_to' => now(),
                    ]);
            });

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
