<?php

namespace App\Models;

use App\Services\OrganizationScopeService;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    public $timestamps = false;

    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'company_id',
        'company_title',
        'password',
        'role',
        'status',
        'left_at',
        'google_id',
        'fb_id',
        'is_admin',
        'tariff',
        'company',
        'privacy_erased_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'left_at' => 'datetime',
        'privacy_erased_at' => 'datetime',
    ];

    public function capabilities(): array
    {
        if ((int) ($this->is_admin ?? 0) === 1) {
            return array_values(array_unique(config('capabilities.workfit_admin', [])));
        }

        $role = app(OrganizationScopeService::class)->currentRole($this);

        return array_values(array_unique(config('capabilities.roles.'.$role, [])));
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    public function getUsersList()
    {
        return DB::table('users')->get();
    }

    public function user_role($email)
    {
        $user = User::where('email', $email)->first();
        $role = $user ? $user->role : null;

        return $role;
    }

    public function uploadCoworkers($name, $email, $companyId, $company, $companyDepartmentTable, $companyWorkerTable)
    {
        $role = $this->user_role($email);
        $users = null;
        $departments = null;
        $manager = null;
        $department = null;
        $chief = null;
        $teamlead_department = null;

        if (! $company) {
            return [
                'users' => null,
                'departments' => null,
            ];
        }

        $departments = DB::table($companyDepartmentTable)->where('company_id', $companyId)->get();
        $head = User::where([['company', 1], ['company_id', $companyId]])->value('email');

        if ($role == 1) {
            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(function ($query) use ($companyId) {
                    $query->where('role', 2)
                        ->where('company_id', $companyId);
                })
                ->orWhere(function ($query) use ($companyId) {
                    $query->where('role', 1)
                        ->where('company_id', $companyId);
                    //                        ->where("email", "!=", $email)
                    //                        ->where("email", "!=", $head);
                })
                ->orderBy('id', 'asc')
                ->paginate(5);
        } elseif ($role == 2) {
            $department = DB::table($companyWorkerTable)
                ->where(['company_id' => $companyId, 'email' => $email])
                ->value('department');

            $manager = DB::table($companyWorkerTable)
                ->where(['company_id' => $companyId, 'email' => $email, 'role' => 1])
                ->value('name');

            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(function ($query) use ($department, $companyId) {
                    $query->where('company_id', $companyId)
                        ->where('role', 3)
                        ->where('department', $department);
                })
                ->orWhere(function ($query) use ($department, $companyId) {
                    $query->where('company_id', $companyId)
                        ->where('role', 4)
                        ->where('department', $department);
                })
                ->orderBy('id', 'asc')
                ->paginate(5);
        } elseif ($role == 3) {
            $teamlead_department = DB::table($companyWorkerTable)
                ->where('email', $email)
                ->value('department');

            $chief = DB::table($companyWorkerTable)
                ->where(['company_id' => $companyId, 'email' => $email, 'role' => 2])
                ->value('name');

            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(['company_id' => $companyId, 'role' => 4, 'supervisor' => $name])
                ->orderBy('id', 'asc')
                ->paginate(5);
        }

        return [
            'head' => $head,
            'users' => $users,
            'departments' => $departments,
            'manager' => $manager,
            'chief_department' => $department,
            'chief' => $chief,
            'teamlead_department' => $teamlead_department,
        ];
    }

    public function usersPagination($name, $email, $companyId, $company, $companyDepartmentTable, $companyWorkerTable)
    {
        $role = $this->user_role($email);
        $users = null;
        $departments = null;

        if (! $company) {
            return [
                'users' => null,
                'departments' => null,
            ];
        }

        $departments = DB::table($companyDepartmentTable)->where(['company_id' => $companyId])->select('title')->get();

        if ($role == 1) {
            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(function ($query) use ($companyId, $email) {
                    $query->where('company_id', $companyId)
                        ->where('role', 2)
                        ->where('email', '!=', $email);
                })
                ->orWhere(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->where('role', 1);
                })
                ->orderBy('id', 'asc')
                ->paginate(25);
        } elseif ($role == 3) {
            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(['company_id' => $companyId, 'role' => 4, 'supervisor' => $name])
                ->orderBy('id', 'asc')
                ->paginate(25);
        } elseif ($role == 2) {
            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where('company_id', $companyId)
                ->where(function ($query) use ($companyWorkerTable, $companyId, $email) {
                    $query->where([['role', 3], ['role', 4], ['department', DB::table($companyWorkerTable)->where(['company_id' => $companyId, 'email' => $email])->value('department')]])
                        ->orWhere([['role', 4], ['department', DB::table($companyWorkerTable)->where(['company_id' => $companyId, 'email' => $email])->value('department')]]);
                })
                ->orderBy('id', 'asc')
                ->paginate(25);
        }

        return [
            'users' => $users,
            'departments' => $departments,
        ];
    }

    public function addDepartmentFunc($email, $name, $companyId, $companyTitle, $title, $companyDepartmentTable)
    {
        try {
            if ($companyId) {
                if (strlen($title) > 0 && strlen($title) <= 50) {
                    $departments = DB::table($companyDepartmentTable)->where('company_id', $companyId)->get();
                    $departments_array = [];
                    foreach ($departments as $department) {
                        $departments_array[] = $department->title;
                    }

                    if (in_array($title, $departments_array)) {
                        return ['status' => 500, 'message' => 'The department exists!'];
                    }

                    DB::table($companyDepartmentTable)->insertOrIgnore([
                        'company_id' => $companyId,
                        'title' => $title,
                    ]);
                } else {
                    return ['status' => 500, 'message' => 'Max. symbols count equals 50 and min. symbols count equals 1!'];
                }
            }

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public static function ifDepartmentExist($department, $companyId)
    {
        $departments = DB::table('company_department')->where('company_id', $companyId)->pluck('title')->toArray();
        $departments = array_map(function ($e) {
            return str_replace(' ', '', strtolower($e));
        }, $departments);

        if (! in_array(str_replace(' ', '', strtolower($department)), $departments)) {
            return false;
        }

        return true;
    }

    public static function ifSupervisorExist($supervisor, $companyId)
    {
        $supervisors = DB::table('company_department')->where([['company_id', $companyId], ['role', 3]])->pluck('name')->toArray();
        $supervisors = array_map(function ($e) {
            return str_replace(' ', '', strtolower($e));
        }, $supervisors);
        if (! in_array(str_replace(' ', '', strtolower($supervisor)), $supervisors)) {
            return false;
        }

        return true;
    }

    public static function updateUserFunc($email, $companyId, $company, $new_name, $new_email, $new_role, $new_department, $userFromUsers, $userFromCompanies, $userFromCompanyWorkers, $authUserRole, $authUserName)
    {
        try {
            $userOldRole = User::where('email', $email)->value('role');

            $updatedData = [];

            if ($userFromUsers) {
                if ($userFromUsers->name !== $new_name) {
                    $userFromUsers->name = $new_name;
                    $updatedData[] = 1;
                }
                if ($userFromUsers->email !== $new_email) {
                    $userFromUsers->email = $new_email;
                    $updatedData[] = 1;
                }

                if ($new_role !== null) {
                    $userFromUsers->role = $new_role;
                    $updatedData[] = 1;
                }

                $userFromUsers->save();
            }

            if ($userFromCompanies) {
                if ($new_role == 2) {
                    $userFromCompanies->delete();
                }
            } elseif ($new_role == 1) {
                Companies::create([
                    'manager_email' => $new_email,
                    'manager' => $new_name,
                    'title' => $company,
                ]);
            }

            if ($userFromCompanyWorkers) {
                $updateData = [
                    'name' => $new_name,
                    'email' => $new_email,
                ];

                if ($new_department) {
                    $updateData['department'] = $new_department;
                    $updatedData[] = 1;
                } else {
                    $updateData['department'] = null;
                }

                if ($new_role) {
                    $updateData['role'] = $new_role;
                }

                DB::table('company_worker')->where('email', $email)->update($updateData);
            }

            if (count($updatedData) !== 0) {
                $link = config('app.test_url');

                $status = 'company manager';
                if ($new_role !== null) {
                    if ($new_role == 2) {
                        $status = 'department chief';
                    } elseif ($new_role == 3) {
                        $status = 'teamlead';
                    } elseif ($new_role == 4) {
                        $status = 'employee';
                    }
                } else {
                    if ($userOldRole == 2) {
                        $status = 'department chief';
                    } elseif ($userOldRole == 3) {
                        $status = 'teamlead';
                    } elseif ($userOldRole == 4) {
                        $status = 'employee';
                    }
                }

                $send_letter = self::send_letter($new_email, $new_name, $company, view('admin-msg', [
                    'name' => $new_name,
                    'link' => $link,
                    'email' => $new_email,
                    'password' => null,
                    'company' => $company,
                    'status' => $status,
                    'department' => $new_department,
                    'teamlead' => $authUserRole == 3 ? $authUserName : null,
                ])->render());
            }

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public static function createNewUserFunc($companyId, $company, $tariff, $authUserName, $authUserRole, $name, $email, $password, $role, $status, $link, $test, $department, $teamlead, $companyWorkerTable)
    {
        try {
            if (! User::where('email', $email)->first()) {
                if ($department !== null) {
                    $ifDepartmentExist = self::ifDepartmentExist($department, $companyId);
                    if ($ifDepartmentExist === false) {
                        $department = 'None department';
                    }
                }

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => \Hash::make($password),
                    'company_id' => $companyId,
                    'company_title' => $company,
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

                if ($role == 2) {
                    $status = 'department chief';
                } elseif ($role == 3) {
                    $status = 'teamlead';
                } elseif ($role == 1) {
                    if ($companyId) {
                        $ifUserIsManagerExists = Companies::where('id', $companyId)->first();

                        if (! $ifUserIsManagerExists) {
                            Companies::insert([
                                'title' => $company,
                                'manager' => $name,
                                'manager_email' => $email,
                            ]);
                            //                        } else {
                            //                            Companies::where("manager_email", $email)->insert([
                            //                                "title" => $company,
                            //                                "manager" => $name,
                            //                                "manager_email" => $email
                            //                            ]);
                        }
                    }
                    //                    $ifUserIsManagerExists = Companies::where("company_id", $companyId)->first();
                    //                    if ($ifUserIsManagerExists) {
                    //                        Companies::where("manager_email", $email)->update([
                    //                            "title" => $company,
                    //                            "manager" => $name,
                    //                            "manager_email" => $email
                    //                        ]);
                    //                    } else {
                    //                        Companies::where("manager_email", $email)->insert([
                    //                            "title" => $company,
                    //                            "manager" => $name,
                    //                            "manager_email" => $email
                    //                        ]);
                    //                    }
                } elseif ($role == 4) {
                    $status = 'employee';
                }

                $send_letter = self::send_letter($email, $name, $company, view('admin-msg', [
                    'name' => $name,
                    'link' => $link,
                    'email' => $email,
                    'password' => $password,
                    'company' => $company,
                    'status' => $status,
                    'test' => $test,
                    'department' => $department,
                    'teamlead' => $teamlead,
                ])->render());

                if ($send_letter['status'] === 500) {
                    return ['message' => $send_letter['message'], 'status' => 500];
                }

                return ['status' => 200];
            } else {
                return ['message' => 'User exists!', 'status' => 500];
            }
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'status' => 500];
        }
    }
}
