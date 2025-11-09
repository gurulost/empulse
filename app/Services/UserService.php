<?php

namespace App\Services;

use App\Models\User;
use App\Models\Companies;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function updateUser(string $email, int $companyId, string $companyTitle, string $newName, string $newEmail, ?int $newRole, ?string $newDepartment, ?object $userFromUsers, ?object $userFromCompanies, ?object $userFromCompanyWorkers, int $authUserRole, string $authUserName): array
    {
        try {
            $userOldRole = User::where('email', $email)->value('role');
            $updatedData = [];

            if ($userFromUsers) {
                if ($userFromUsers->name !== $newName) {
                    $userFromUsers->name = $newName;
                    $updatedData[] = 1;
                }
                if ($userFromUsers->email !== $newEmail) {
                    $userFromUsers->email = $newEmail;
                    $updatedData[] = 1;
                }
                if ($newRole !== null) {
                    $userFromUsers->role = $newRole;
                    $updatedData[] = 1;
                }
                $userFromUsers->save();
            }

            if ($userFromCompanies) {
                if ($newRole === 2) {
                    DB::table('companies')->where('manager_email', $email)->delete();
                }
            } elseif ($newRole === 1) {
                DB::table('companies')->updateOrInsert(
                    ['title' => $companyTitle],
                    ['manager_email' => $newEmail, 'manager' => $newName]
                );
            }

            if ($userFromCompanyWorkers) {
                $updateData = [
                    'name' => $newName,
                    'email' => $newEmail,
                ];
                if ($newDepartment) {
                    $updateData['department'] = $newDepartment;
                    $updatedData[] = 1;
                } else {
                    $updateData['department'] = null;
                }
                if ($newRole) {
                    $updateData['role'] = $newRole;
                }
                DB::table('company_worker')->where('email', $email)->update($updateData);
            }

            if (count($updatedData) !== 0) {
                $link = env('TEST_URL');
                $status = 'company manager';
                if ($newRole !== null) {
                    if ($newRole == 2) $status = 'department chief';
                    elseif ($newRole == 3) $status = 'teamlead';
                    elseif ($newRole == 4) $status = 'employee';
                } else {
                    if ($userOldRole == 2) $status = 'department chief';
                    elseif ($userOldRole == 3) $status = 'teamlead';
                    elseif ($userOldRole == 4) $status = 'employee';
                }

                $sendLetter = User::send_letter($newEmail, $newName, $companyTitle, view('admin-msg', [
                    'name' => $newName,
                    'link' => $link,
                    'email' => $newEmail,
                    'password' => null,
                    'company' => $companyTitle,
                    'status' => $status,
                    'department' => $newDepartment,
                    'teamlead' => $authUserRole == 3 ? $authUserName : null,
                ])->render());
                if ($sendLetter['status'] === 500) {
                    return ['status' => 500, 'message' => $sendLetter['message']];
                }
            }

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function createUser(int $companyId, string $companyTitle, $tariff, string $authUserName, int $authUserRole, string $name, string $email, string $password, int $role, string $status, string $loginLink, string $testLink, ?string $department, ?string $teamlead, string $companyWorkerTable): array
    {
        try {
            if (User::where('email', $email)->first()) {
                return ['message' => 'User exists!', 'status' => 500];
            }

            if ($department !== null) {
                $existsDept = DB::table('company_department')->where(['company_id' => $companyId, 'title' => $department])->exists();
                if (!$existsDept) {
                    $department = 'None department';
                }
            }

            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'company_id' => $companyId,
                'company_title' => $companyTitle,
                'role' => $role,
                'tariff' => $tariff,
            ]);

            DB::table($companyWorkerTable)->insert([
                'company_id' => $companyId,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'supervisor' => $teamlead,
                'department' => $department,
            ]);

            if ($role == 1 && $companyId) {
                $exists = DB::table('companies')->where('id', $companyId)->exists();
                if (!$exists) {
                    DB::table('companies')->insert([
                        'title' => $companyTitle,
                        'manager' => $name,
                        'manager_email' => $email,
                    ]);
                }
            }

            if ($role == 2) $status = 'department chief';
            elseif ($role == 3) $status = 'teamlead';
            elseif ($role == 4) $status = 'employee';

            $sendLetter = User::send_letter($email, $name, $companyTitle, view('admin-msg', [
                'name' => $name,
                'link' => $loginLink,
                'email' => $email,
                'password' => $password,
                'company' => $companyTitle,
                'status' => $status,
                'test' => $testLink,
                'department' => $department,
                'teamlead' => $teamlead,
            ])->render());

            if ($sendLetter['status'] === 500) {
                return ['message' => $sendLetter['message'], 'status' => 500];
            }

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'status' => 500];
        }
    }

    public function deleteByEmail(string $email, int $companyId, string $companyTitle, string $companyDepartmentTable, string $companyWorkerTable): bool
    {
        return DB::transaction(function () use ($email, $companyId, $companyTitle, $companyWorkerTable) {
            // Delete from company workers for this company
            DB::table($companyWorkerTable)->where(['company_id' => $companyId, 'email' => $email])->delete();
            // Delete user record
            DB::table('users')->where('email', $email)->delete();
            // If user was set as manager for this company, remove that association
            DB::table('companies')->where(['id' => $companyId, 'manager_email' => $email])->delete();
            return true;
        });
    }

    public function setManager(?int $companyId, string $companyTitle, string $email, int $authTariff): array
    {
        try {
            DB::table('users')
                ->where('email', $email)
                ->update(['role' => 1, 'tariff' => ($authTariff === 1) ? 1 : 0]);

            if ($companyId) {
                DB::table('company_worker')
                    ->where(['company_id' => $companyId, 'email' => $email])
                    ->update(['role' => 1, 'department' => '']);
            }

            $newManagerName = User::where('email', $email)->value('name');
            DB::table('companies')->updateOrInsert(
                ['title' => $companyTitle],
                ['manager' => $newManagerName, 'manager_email' => $email]
            );

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function setTeamlead(?int $companyId, string $email, int $authTariff): array
    {
        try {
            DB::table('users')
                ->where('email', $email)
                ->update(['role' => 3, 'tariff' => ($authTariff === 1) ? 1 : 0]);

            if ($companyId) {
                DB::table('company_worker')
                    ->where(['company_id' => $companyId, 'email' => $email])
                    ->update(['role' => 3]);
            }

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function setChief(?int $companyId, string $companyTitle, string $email, int $authTariff): array
    {
        try {
            DB::table('users')
                ->where('email', $email)
                ->update(['role' => 2, 'tariff' => ($authTariff === 1) ? 1 : 0]);

            if ($companyId) {
                DB::table('company_worker')
                    ->where(['company_id' => $companyId, 'email' => $email])
                    ->update(['role' => 2]);
            }

            // If user is manager of a company, remove that company manager assignment
            $chief = DB::table('companies')->where('manager_email', $email)->first();
            if ($chief) {
                DB::table('companies')->where('manager_email', $email)->delete();
            }

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function setEmployee(?int $companyId, string $companyTitle, string $email): array
    {
        try {
            DB::table('users')
                ->where('email', $email)
                ->update(['role' => 4, 'tariff' => 0]);

            if ($companyId) {
                DB::table('company_worker')
                    ->where(['company_id' => $companyId, 'email' => $email])
                    ->update(['role' => 4]);
            }

            $chief = DB::table('companies')->where('manager_email', $email)->first();
            if ($chief) {
                DB::table('companies')->where('manager_email', $email)->delete();
            }

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
