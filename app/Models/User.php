<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use App\Models\Companies;
use App\Services\SurveyAnalyticsService;
use App\Services\SurveyService;
use DB;
use Hash;

class User extends Authenticatable
{
    public $timestamps = false;
    use HasApiTokens, HasFactory, Notifiable, Billable;

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
        'google_id',
        'fb_id',
        'is_admin',
        'tariff',
        'company'
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
    ];

    public function getUsersList() {
        return DB::table("users")->get();
    }

    public function user_role($email) {
        $user = User::where("email", $email)->first();
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

        if(!$company) {
            return [
                'users' => null,
                'departments' => null
            ];
        }

        $departments = DB::table($companyDepartmentTable)->where('company_id', $companyId)->get();
        $head = User::where([['company', 1], ['company_id', $companyId]])->value('email');

        if ($role == 1) {
            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(function ($query) use ($companyId, $email) {
                    $query->where('role', 2)
                        ->where('company_id', $companyId);
                })
                ->orWhere(function ($query) use ($companyId, $email, $head) {
                    $query->where('role', 1)
                        ->where('company_id', $companyId);
//                        ->where("email", "!=", $email)
//                        ->where("email", "!=", $head);
                })
                ->orderBy("id", 'asc')
                ->paginate(5);
        } elseif ($role == 2) {
            $department = DB::table($companyWorkerTable)
                ->where(['company_id' => $companyId, "email" => $email])
                ->value("department");

            $manager = DB::table($companyWorkerTable)
                ->where(['company_id' => $companyId, "email" => $email, 'role' => 1])
                ->value("name");

            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(function ($query) use ($department, $companyId) {
                    $query->where('company_id', $companyId)
                        ->where("role", 3)
                        ->where("department", $department);
                })
                ->orWhere(function ($query) use ($department, $companyId) {
                    $query->where('company_id', $companyId)
                        ->where("role", 4)
                        ->where("department", $department);
                })
                ->orderBy("id", 'asc')
                ->paginate(5);
        } elseif ($role == 3) {
            $teamlead_department = DB::table($companyWorkerTable)
                ->where("email", $email)
                ->value("department");

            $chief = DB::table($companyWorkerTable)
                ->where(['company_id' => $companyId, "email" => $email, 'role' => 2])
                ->value("name");

            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(['company_id' => $companyId, 'role' => 4, "supervisor" => $name])
                ->orderBy("id", 'asc')
                ->paginate(5);
        }

        return [
            'head' => $head,
            'users' => $users,
            'departments' => $departments,
            "manager" => $manager,
            "chief_department" => $department,
            "chief" => $chief,
            "teamlead_department" => $teamlead_department,
        ];
    }

    public function usersPagination($name, $email, $companyId, $company, $companyDepartmentTable, $companyWorkerTable) {
        $role = $this->user_role($email);
        $users = null;
        $departments = null;

        if(!$company) {
            return [
                'users' => null,
                'departments' => null
            ];
        }

        $departments = DB::table($companyDepartmentTable)->where(['company_id'=>$companyId])->select("title")->get();

        if($role == 1) {
            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(function ($query) use ($companyId, $email) {
                    $query->where('company_id', $companyId)
                        ->where('role', 2)
                        ->where("email", "!=", $email);
                })
                ->orWhere(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->where('role', 1);
                })
                ->orderBy("id", 'asc')
                ->paginate(25);
        }
        else if($role == 3) {
            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where(['company_id' => $companyId, "role" => 4, "supervisor" => $name])
                ->orderBy("id", 'asc')
                ->paginate(25);
        }
        else if($role == 2) {
            $users = DB::table($companyWorkerTable)
                ->select('*')
                ->where('company_id', $companyId)
                ->where(function($query) use ($companyWorkerTable, $companyId, $email) {
                    $query->where([['role', 3], ['role', 4], ["department", DB::table($companyWorkerTable)->where(['company_id' => $companyId, "email" => $email])->value("department")]])
                        ->orWhere([['role', 4], ["department", DB::table($companyWorkerTable)->where(['company_id' => $companyId, "email" => $email])->value("department")]]);
                })
                ->orderBy("id", 'asc')
                ->paginate(25);
        }

        return [
            'users' => $users,
            'departments' => $departments
        ];
    }

    public function deleteUser($email, $companyId, $company, $companyDepartmentTable, $companyWorkerTable) {
        $maxUsers = DB::table("users")->max("id") + 1;

        if($company) {
            $max = DB::table($companyWorkerTable)->max("id") + 1;

            DB::table($companyWorkerTable)->where(['company_id' => $companyId, "email" => $email])->delete();
            DB::table('users')->where("email", $email)->delete();
            DB::table('companies')->where(['id' => $companyId, "manager_email" => $email])->delete();
            // Avoid resetting AUTO_INCREMENT; unnecessary and error-prone

            return true;
        }

        return false;
    }

    public function qualtricsFunc($userName, $userEmail, $userRole, $userPassword, $companyTitle, ?array $dataset = null) {
        $qualtrics = new \stdClass();
        $qualtrics->data = [];
        $qualtrics->time = null;

        if ($dataset === null) {
            $analyticsService = app(SurveyAnalyticsService::class);
            $dataset = $analyticsService->datasetForCompany($this->company_id);
        }

        $qualtrics->time = $dataset['time'] ?? now()->valueOf();
        $entries = $dataset['data'] ?? [];
        $allUsers = \DB::table('company_worker')->pluck('email')->toArray();
        $userDepartment = \DB::table('company_worker')->where('email', $userEmail)->value('department');

        foreach ($entries as $payload) {
            $decoded = is_array($payload) ? $payload : json_decode($payload, true);
            if (!$decoded || !isset($decoded['values'])) {
                continue;
            }

            $values = $decoded['values'];
            $companyMatches = isset($values['QID101_TEXT']) && $values['QID101_TEXT'] === $companyTitle;
            $emailAllowed = isset($values['QID62_TEXT']) && in_array($values['QID62_TEXT'], $allUsers);
            if (!$companyMatches || !$emailAllowed) {
                continue;
            }

            if ($userRole == 1) {
                $qualtrics->data[] = json_encode($decoded);
            } elseif ($userRole == 2) {
                if (isset($values['QID63_TEXT']) && $values['QID63_TEXT'] === $userDepartment) {
                    $qualtrics->data[] = json_encode($decoded);
                }
            } elseif ($userRole == 3) {
                if (isset($values['QID103_TEXT']) && $values['QID103_TEXT'] === $userName) {
                    $qualtrics->data[] = json_encode($decoded);
                }
            }
        }

        return $qualtrics;
    }

    public function deleteAvatarFunc($id) {
        try {
            $avatarImage = User::findOrFail($id);

            $img = $avatarImage->image;
            if ($img != NULL) {
                $path = public_path('upload/' . $img);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            User::where('id', $id)->update(
                ['image' => NULL]
            );

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function editPasswordFunc($userEmail, $name, $email, $new_pass, $conf_new_pass, $company_title) {
        $userFromUsers = User::where("email", $userEmail)->first();
        $userRole = $userFromUsers->role;
        $userName = $userFromUsers->name;
        $userPassword = $userFromUsers->password;
        $userCompanyTitle = $userFromUsers->company_title;
        $updated = [];
        $non_updated = [];

        if($userRole == 1) {
            if($company_title) {
                if (strlen($company_title) > 0 && $userCompanyTitle !== $company_title) {
                    \DB::table("users")->where("company_title", $userCompanyTitle)->update(["company_title" => $company_title]);
                    \DB::table("company_worker")->where("company_title", $userCompanyTitle)->update(["company_title" => $company_title]);
                    \DB::table("companies")->where("title", $userCompanyTitle)->update(["title" => $company_title]);

                    $updated[] = 'company title';
                } else {
                    $non_updated[] = 'company title';
                }
            }
        }

        if (strlen($name) > 0 && $userName !== $name) {
            $userFromUsers->name = $name;
            \DB::table("company_worker")->where("email", $userEmail)->update(["name" => $name]);
            $updated[] = 'name';
        } else {
            $non_updated[] = 'name';
        }

        if (strlen($email) > 0 && $userFromUsers->email !== $email) {
            $emailExists = User::where('email', $email)
                ->where('id', '!=', $userFromUsers->id)
                ->exists();

            if ($emailExists) {
                $non_updated[] = 'email';
            } else {
                $userFromUsers->email = $email;
                \DB::table("company_worker")->where("email", $userEmail)->update(["email" => $email]);

                if ((int) $userRole === 1) {
                    \DB::table('companies')->where('manager_email', $userEmail)->update([
                        'manager_email' => $email,
                    ]);
                }

                $updated[] = 'email';
            }
        } else {
            $non_updated[] = 'email';
        }

        if (strlen($new_pass) >= 8 && $new_pass === $conf_new_pass && !\Hash::check($new_pass, $userPassword)) {
            $userFromUsers->password = \Hash::make($new_pass);
            $updated[] = 'password';
        } else {
            $non_updated[] = 'password';
        }

        if (count($updated) > 0) {
            $userFromUsers->save();
            $updated = implode(", ", $updated);
            if(count($non_updated) > 0) {
                $non_updated = implode(", ", $non_updated);
                return ["status" => 200, 'message' => "Your $updated updated! But $non_updated didn't update, because of an incorrect format or duplicate."];
            }

            return ["status" => 200, 'message' => "Your $updated updated!"];
        } else {
            if(count($non_updated) > 0) {
                $non_updated = implode(", ", $non_updated);
                return ["status" => 400, 'message' => "$non_updated didn't update, because of an incorrect format or duplicate!"];
            }

            return ["status" => 400, 'message' => "No fields were updated."];
        }
    }

    public function surveyLink(): ?string
    {
        return app(SurveyService::class)->assignmentLink($this);
    }

    public function updateUserPasswordFunc($name, $email, $companyTitle, $password) {

        try {
            \DB::table("users")->where("email", $email)->update([
                "company_title" => $companyTitle,
                "password" => Hash::make($password),
                "role" => 1
            ]);

            if (Companies::where("manager_email", $email)->first()) {
                \DB::table('companies')->where("manager_email", $email)->update([
                    "title" => $companyTitle,
                    "manager" => $name,
                    "manager_email" => $email
                ]);
            } else {
                \DB::table('companies')->insert([
                    "title" => $companyTitle,
                    "manager" => $name,
                    "manager_email" => $email
                ]);
            }

            return ['status' => 200];
        } catch(\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function updateCoworkerNameFunc($param, $currenty, $new) {
        try {
            if($param === 'name') {
                DB::table('users')->where('name', $currenty)->update(['name' => $new]);
                DB::table('companies')->where('manager', $currenty)->update(['manager' => $new]);
                DB::table('company_worker')->where('name', $currenty)->update(['name' => $new]);
            } else if($param === 'email') {
                DB::table('users')->where('email', $currenty)->update(['email' => $new]);
                DB::table('companies')->where('manager_email', $currenty)->update(['manager_email' => $new]);
                DB::table('company_worker')->where('email', $currenty)->update(['email' => $new]);
            }

            return ['status' => 200];
        } catch(\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function updateCoworkerDepartmentFunc($email, $department) {
        try {
            $user = User::where('email', $email)->value('role');

            if($user == 2) {
                DB::table('company_worker')->where('email', $email)->update(['department' => $department]);
            } else {
                return ['status' => 500, 'message' => 'This user is a manager and can not to be a department chief!'];
            }

            return ['status' => 200];
        } catch(\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function addDepartmentFunc($email, $name, $companyId, $companyTitle, $title, $companyDepartmentTable) {
        try {
            if($companyId) {
                if(strlen($title) > 0 && strlen($title) <= 50) {
                    $departments = DB::table($companyDepartmentTable)->where('company_id', $companyId)->get();
                    $departments_array = [];
                    foreach ($departments as $department) {
                        $departments_array[] = $department->title;
                    }

                    if(in_array($title, $departments_array)) {
                        return ['status' => 500, 'message' => 'The department exists!'];
                    }

                    DB::table($companyDepartmentTable)->insertOrIgnore([
                        "company_id" => $companyId,
                        "title" => $title
                    ]);
                } else {
                    return ['status' => 500, 'message' => 'Max. symbols count equals 50 and min. symbols count equals 1!'];
                }
            }

            return ['status' => 200];
        } catch(\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    static public function ifDepartmentExist($department, $companyId) {
        $departments = \DB::table('company_department')->where('company_id', $companyId)->pluck('title')->toArray();
        $departments = array_map(function ($e) {
            return str_replace(' ', '', strtolower($e));
        }, $departments);

        if (!in_array(str_replace(' ', '', strtolower($department)), $departments)) {
            return false;
        }

        return true;
    }

    static public function ifSupervisorExist($supervisor, $companyId) {
        $supervisors = \DB::table('company_department')->where([['company_id', $companyId], ['role', 3]])->pluck('name')->toArray();
        $supervisors = array_map(function ($e) {
            return str_replace(' ', '', strtolower($e));
        }, $supervisors);
        if(!in_array(str_replace(' ', '', strtolower($supervisor)), $supervisors)) {
            return false;
        }

        return true;
    }





    static public function updateUserFunc($email, $companyId, $company, $new_name, $new_email, $new_role, $new_department, $userFromUsers, $userFromCompanies, $userFromCompanyWorkers, $authUserRole, $authUserName) {
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
                    'title' => $company
                ]);
            }

            if ($userFromCompanyWorkers) {
                $updateData = [
                    'name' => $new_name,
                    'email' => $new_email,
                ];

                if($new_department) {
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

            if(count($updatedData) !== 0) {
                $link = config('app.test_url');

                $status = 'company manager';
                if($new_role !== null) {
                    if($new_role == 2) {
                        $status = 'department chief';
                    } elseif($new_role == 3) {
                        $status = 'teamlead';
                    } elseif($new_role == 4) {
                        $status = 'employee';
                    }
                } else {
                    if($userOldRole == 2) {
                        $status = 'department chief';
                    } elseif($userOldRole == 3) {
                        $status = 'teamlead';
                    } elseif($userOldRole == 4) {
                        $status = 'employee';
                    }
                }

                $send_letter = self::send_letter($new_email, $new_name, $company, view("admin-msg", [
                    "name" => $new_name,
                    "link" => $link,
                    "email" => $new_email,
                    "password" => null,
                    "company" => $company,
                    "status" => $status,
                    "department" => $new_department,
                    'teamlead' => $authUserRole == 3 ? $authUserName : null
                ])->render());
            }

            return ['status' => 200];
        } catch(\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    static public function createNewUserFunc($companyId, $company, $tariff, $authUserName, $authUserRole, $name, $email, $password, $role, $status, $link, $test, $department, $teamlead, $companyWorkerTable) {
        try {
            if(!User::where('email', $email)->first()) {
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
                    'tariff' => $tariff
                ]);

                DB::table($companyWorkerTable)->insert([
                    "company_id" => $companyId,
                    "name" => $name,
                    "email" => $email,
                    'role' => $role,
                    "supervisor" => $teamlead,
                    "department" => $department
                ]);

                if ($role == 2) {
                    $status = "department chief";
                } elseif ($role == 3) {
                    $status = "teamlead";
                } elseif ($role == 1) {
                    if ($companyId){
                        $ifUserIsManagerExists = Companies::where("id", $companyId)->first();

                        if (!$ifUserIsManagerExists) {
                            Companies::insert([
                                "title" => $company,
                                "manager" => $name,
                                "manager_email" => $email
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
                    $status = "employee";
                }

                $send_letter = self::send_letter($email, $name, $company, view("admin-msg", [
                    "name" => $name,
                    "link" => $link,
                    "email" => $email,
                    "password" => $password,
                    "company" => $company,
                    "status" => $status,
                    "test" => $test,
                    "department" => $department,
                    'teamlead' => $teamlead
                ])->render());

                if ($send_letter['status'] === 500) {
                    return ['message' => $send_letter['message'], 'status' => 500];
                }

                return ['status' => 200];
            } else {
                return ['message' => 'User exists!', 'status' => 500];
            }
        } catch(\Exception $e) {
            return ['message' => $e->getMessage(), 'status' => 500];
        }
    }
}
