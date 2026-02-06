<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use App\Models\Companies;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\EmailService;
use Illuminate\Support\Str;

class UserService
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function generatePassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = random_int(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }

    public function checkStatus($userAuthRole, $status) {
        $targetRole = $this->normalizeStatus($status);
        if (!$targetRole) {
            return false;
        }

        return $this->canAssignRole(Role::tryFrom((int) $userAuthRole), $targetRole);
    }

    public function addWorker(string $name, string $email, string $password, string $status, ?string $department)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            throw new \RuntimeException('Unauthorized.');
        }

        $targetRole = $this->normalizeStatus($status);
        if (!$targetRole) {
            throw new \InvalidArgumentException('Invalid status provided.');
        }

        if (!$this->userCanAssignRole($authUser, $targetRole)) {
            throw new \RuntimeException('Insufficient permissions to assign this role.');
        }

        $result = $this->createUser(
            (int) $authUser->company_id,
            (string) $authUser->company_title,
            $authUser->tariff,
            (string) $authUser->name,
            (int) $authUser->role,
            $name,
            $email,
            $password,
            $targetRole->value,
            $this->roleLabel($targetRole),
            $this->loginUrl(),
            $this->testUrl(),
            $department,
            null,
            'company_worker'
        );

        if (($result['status'] ?? 500) !== 200 || empty($result['user'])) {
            throw new \RuntimeException($result['message'] ?? 'Unable to add worker.');
        }

        return $result['user'];
    }

    public function addWorkerTeamlead(string $name, string $email, string $teamlead)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            throw new \RuntimeException('Unauthorized.');
        }

        if (!$this->userCanAssignRole($authUser, Role::EMPLOYEE)) {
            throw new \RuntimeException('Insufficient permissions to add employees.');
        }

        $department = DB::table('company_worker')
            ->where('email', $authUser->email)
            ->value('department');

        $password = $this->generatePassword();

        $result = $this->createUser(
            (int) $authUser->company_id,
            (string) $authUser->company_title,
            $authUser->tariff,
            (string) $authUser->name,
            (int) $authUser->role,
            $name,
            $email,
            $password,
            Role::EMPLOYEE->value,
            $this->roleLabel(Role::EMPLOYEE),
            $this->loginUrl(),
            $this->testUrl(),
            $department,
            $teamlead,
            'company_worker'
        );

        if (($result['status'] ?? 500) !== 200 || empty($result['user'])) {
            throw new \RuntimeException($result['message'] ?? 'Unable to add team member.');
        }

        return $result['user'];
    }

    public function updateUser(string $email, int $companyId, string $companyTitle, string $newName, string $newEmail, ?int $newRole, ?string $newDepartment, ?object $userFromUsers, ?object $userFromCompanies, ?object $userFromCompanyWorkers, int $authUserRole, string $authUserName): array
    {
        try {
            $newRoleEnum = null;
            if ($newRole !== null) {
                $newRoleEnum = Role::tryFrom($newRole);
                if (!$newRoleEnum) {
                    return ['status' => 500, 'message' => 'Invalid role provided.'];
                }
                if (!$this->canAssignRole(Role::tryFrom($authUserRole), $newRoleEnum)) {
                    return ['status' => 500, 'message' => 'Insufficient permissions to assign role.'];
                }
            }

            $result = DB::transaction(function () use (
                $email,
                $companyId,
                $companyTitle,
                $newName,
                $newEmail,
                $newRole,
                $newRoleEnum,
                $newDepartment,
                $userFromUsers,
                $userFromCompanies,
                $userFromCompanyWorkers
            ) {
                $userOldRole = User::where('email', $email)->value('role');
                $updated = false;

                if ($userFromUsers) {
                    if ($userFromUsers->name !== $newName) {
                        $userFromUsers->name = $newName;
                        $updated = true;
                    }
                    if ($userFromUsers->email !== $newEmail) {
                        $userFromUsers->email = $newEmail;
                        $updated = true;
                    }
                    if ($newRole !== null) {
                        $userFromUsers->role = $newRole;
                        $updated = true;
                    }
                    $userFromUsers->save();
                }

                if ($userFromCompanies) {
                    if ($newRole === Role::CHIEF->value) {
                        DB::table('companies')->where('manager_email', $email)->delete();
                        $updated = true;
                    }
                } elseif ($newRole === Role::MANAGER->value) {
                    DB::table('companies')->updateOrInsert(
                        ['title' => $companyTitle],
                        ['manager_email' => $newEmail, 'manager' => $newName]
                    );
                    $updated = true;
                }

                if ($userFromCompanyWorkers) {
                    $updateData = [
                        'name' => $newName,
                        'email' => $newEmail,
                    ];
                    $updateData['department'] = $newDepartment ?: null;
                    if ($newDepartment || $userFromCompanyWorkers->department !== $updateData['department']) {
                        $updated = true;
                    }
                    if ($newRole) {
                        $updateData['role'] = $newRole;
                        $updated = true;
                    }
                    DB::table('company_worker')->where('email', $email)->update($updateData);
                }

                $targetUser = User::where('email', $newEmail)->first();
                $statusRole = $newRoleEnum ?? Role::tryFrom((int) $userOldRole) ?? Role::EMPLOYEE;

                return [
                    'updated' => $updated,
                    'target_user' => $targetUser,
                    'status_role' => $statusRole,
                ];
            });

            if (!$result['updated']) {
                return ['status' => 200];
            }

            $link = $this->testUrl();
            $statusLabel = $this->roleLabel($result['status_role']);
            $surveyLink = $result['target_user']
                ? $this->resolveSurveyLink($result['target_user'], $link)
                : $link;

            $sendLetter = $this->emailService->sendLetter($newEmail, $newName, $companyTitle, view('admin-msg', [
                'name' => $newName,
                'link' => $link,
                'email' => $newEmail,
                'password' => null,
                'company' => $companyTitle,
                'status' => $statusLabel,
                'department' => $newDepartment,
                'teamlead' => $authUserRole == Role::TEAMLEAD->value ? $authUserName : null,
                'surveyLink' => $surveyLink,
            ])->render());

            if ($sendLetter['status'] === 500) {
                return ['status' => 500, 'message' => $sendLetter['message']];
            }

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function createUser(int $companyId, string $companyTitle, $tariff, string $authUserName, int $authUserRole, string $name, string $email, string $password, int $role, string $status, ?string $loginLink, ?string $testLink, ?string $department, ?string $teamlead, string $companyWorkerTable): array
    {
        try {
            if (User::where('email', $email)->exists()) {
                return ['message' => 'User exists!', 'status' => 500];
            }

            $roleEnum = Role::tryFrom($role);
            if (!$roleEnum) {
                return ['message' => 'Invalid role provided.', 'status' => 500];
            }

            $actorRole = Role::tryFrom((int) $authUserRole);
            if (!$this->canAssignRole($actorRole, $roleEnum)) {
                return ['message' => 'Insufficient permissions to assign role.', 'status' => 500];
            }

            $loginLink = $loginLink ?: $this->loginUrl();
            $testLink = $testLink ?: $this->testUrl();

            $createdUser = DB::transaction(function () use (
                $companyId,
                $companyTitle,
                $tariff,
                $name,
                $email,
                $password,
                $roleEnum,
                &$department,
                $teamlead,
                $companyWorkerTable
            ) {
                if ($department !== null) {
                    $existsDept = DB::table('company_department')
                        ->where(['company_id' => $companyId, 'title' => $department])
                        ->exists();
                    if (!$existsDept) {
                        $department = 'None department';
                    }
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'company_id' => $companyId,
                    'company_title' => $companyTitle,
                    'role' => $roleEnum->value,
                    'tariff' => $tariff,
                ]);

                DB::table($companyWorkerTable)->insert([
                    'company_id' => $companyId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $roleEnum->value,
                    'supervisor' => $teamlead,
                    'department' => $department,
                ]);

                if ($roleEnum === Role::MANAGER && $companyId) {
                    $exists = DB::table('companies')->where('id', $companyId)->exists();
                    if (!$exists) {
                        DB::table('companies')->insert([
                            'title' => $companyTitle,
                            'manager' => $name,
                            'manager_email' => $email,
                        ]);
                    }
                }

                return $user;
            });

            $statusLabel = $status ?: $this->roleLabel($roleEnum);
            $surveyLink = $this->resolveSurveyLink($createdUser, $testLink);

            $sendLetter = $this->emailService->sendLetter($email, $name, $companyTitle, view('admin-msg', [
                'name' => $name,
                'link' => $loginLink,
                'email' => $email,
                'password' => $password,
                'company' => $companyTitle,
                'status' => $statusLabel,
                'department' => $department,
                'teamlead' => $teamlead,
                'surveyLink' => $surveyLink,
            ])->render());

            if ($sendLetter['status'] === 500) {
                return ['message' => $sendLetter['message'], 'status' => 500];
            }

            return ['status' => 200, 'user' => $createdUser];
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

    protected function normalizeStatus(?string $status): ?Role
    {
        if ($status === null) {
            return null;
        }

        $slug = (string) Str::of($status)->lower()->replace('-', ' ')->squish();

        return match ($slug) {
            'admin', 'administrator' => Role::ADMIN,
            'manager', 'company manager' => Role::MANAGER,
            'chief', 'department chief' => Role::CHIEF,
            'team lead', 'teamlead' => Role::TEAMLEAD,
            'employee' => Role::EMPLOYEE,
            default => null,
        };
    }

    protected function canAssignRole(?Role $actor, Role $target): bool
    {
        if (!$actor) {
            return false;
        }

        return match ($actor) {
            Role::ADMIN => true,
            Role::MANAGER => in_array($target, [Role::MANAGER, Role::CHIEF, Role::TEAMLEAD, Role::EMPLOYEE], true),
            Role::CHIEF => in_array($target, [Role::TEAMLEAD, Role::EMPLOYEE], true),
            Role::TEAMLEAD => $target === Role::EMPLOYEE,
            default => false,
        };
    }

    public function userCanAssignRole(User $user, Role $target): bool
    {
        if ((int) ($user->is_admin ?? 0) === 1) {
            return true;
        }

        return $this->canAssignRole(Role::tryFrom((int) $user->role), $target);
    }

    public function roleLabel(Role $role): string
    {
        return match ($role) {
            Role::ADMIN => 'admin',
            Role::MANAGER => 'company manager',
            Role::CHIEF => 'department chief',
            Role::TEAMLEAD => 'teamlead',
            Role::EMPLOYEE => 'employee',
        };
    }

    protected function loginUrl(): string
    {
        return (string) config('app.login_url', rtrim(config('app.url', 'http://localhost'), '/').'/login');
    }

    protected function testUrl(): string
    {
        return (string) config('app.test_url', config('app.url', 'http://localhost'));
    }

    protected function resolveSurveyLink(User $user, string $fallback): string
    {
        try {
            return $user->surveyLink() ?? $fallback;
        } catch (\Throwable $e) {
            Log::warning('Failed to generate survey link', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }
}
