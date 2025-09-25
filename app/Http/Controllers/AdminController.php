<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Models\Companies;
use App\Mail\CoworkersMsg;
use App\Mail\AdminMsg;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use MongoDB\Driver\Session;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminController extends Controller
{
    public $companyDepartmentTable = 'company_department';
    public $companyWorkerTable = 'company_worker';

    public function send_letter($email, $name, $subject, $content) {
        try {
            $response = Http::withHeaders([
                'api-key' => env("BREVO_API_KEY"),
                'Content-Type' => 'application/json'
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => 'Workfitdx',
                    'email' => 'billing@workfitdx.com'
                ],
                'to' => [
                    [
                        'email' => $email,
                        'name' => $name
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $content
            ]);

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function upload_coworkers()
    {
        $name = \Auth::user()->name;
        $email = \Auth::user()->email;
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        try {
            $model = new User();
            $upload_coworkers = $model->uploadCoworkers($name, $email, $companyId, $company, $this->companyDepartmentTable, $this->companyWorkerTable);

            $users = $upload_coworkers["users"];
            $departments = $upload_coworkers["departments"];
            $manager = $upload_coworkers["manager"];
            $department = $upload_coworkers["chief_department"];
            $chief = $upload_coworkers["chief"];
            $teamlead_department = $upload_coworkers["teamlead_department"];
            $head = $upload_coworkers["head"];

            return view('roles.adminPanel', ['users' => $users, 'departments' => $departments, "manager" => $manager, "chief_department" => $department,  "chief" => $chief, "teamlead_department" => $teamlead_department, 'head' => $head]);
        } catch(\Exception $e) {
            return view('roles.adminPanel', ['users' => null, 'departments' => null, "manager" => null, "chief_department" => null,  "chief" => null, "teamlead_department" => null, "head" => null, 'error' => $e->getMessage()]);
        }
    }

    public function usersPagination(Request $request) {
        if($request->ajax()) {
            $name = \Auth::user()->name;
            $email = \Auth::user()->email;
            $companyId = \Auth::user()->company_id;
            $company = \Auth::user()->company_title;
            try {
                $model = new User();
                $upload_coworkers = $model->uploadCoworkers($name, $email, $companyId, $company, $this->companyDepartmentTable, $this->companyWorkerTable);

                $users = $upload_coworkers["users"];
                $departments = $upload_coworkers["departments"];
                $manager = $upload_coworkers["manager"];
                $department = $upload_coworkers["chief_department"];
                $chief = $upload_coworkers["chief"];
                $teamlead_department = $upload_coworkers["teamlead_department"];
                $head = $upload_coworkers["head"];

                return view('roles.usersPagination', ['users' => $users, 'departments' => $departments, "manager" => $manager, "chief_department" => $department,  "chief" => $chief, "teamlead_department" => $teamlead_department, 'head' => $head])->render();
            } catch(\Exception $e) {
                return view('roles.usersPagination', ['users' => null, 'departments' => null, "manager" => null, "chief_department" => null,  "chief" => null, "teamlead_department" => null, "head" => null, 'error' => $e->getMessage()])->render();
            }
        }
    }

    public function delete($email)
    {
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        try {
            $model = new User();
            $deleteUser = $model->deleteUser($email, $companyId, $company, $this->companyDepartmentTable, $this->companyWorkerTable);

            if($deleteUser) {
                return response()->json(['status' => 200]);
            } else {
                return response()->json(['status' => 500, 'message' => 'Something went wrong!']);
            }
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function updateUser(Request $request, $email) {
        try {
            $request->validate([
                'new_name' => 'required|string',
                'new_email' => 'required|email',
                'new_role' => 'nullable|integer',
                'new_department' => 'nullable|string',
            ]);

            $companyId = auth()->user()->company_id;
            $company = auth()->user()->company_title;
            $new_name = $request->input('new_name');
            $new_email = $request->input('new_email');
            $new_role = $request->input('new_role');
            $new_department = $request->input('new_department');
            $userFromUsers = User::where('email', $email)->first();
            $userFromCompanies = Companies::where('manager_email', $email)->first();
            $userFromCompanyWorkers = DB::table('company_worker')->where('email', $email)->first();
            $authUserRole = \Auth::user()->role;
            $authUserName = \Auth::user()->name;

            $updateUserFunc = User::updateUserFunc($email, $companyId, $company, $new_name,
                $new_email, $new_role, $new_department, $userFromUsers, $userFromCompanies, $userFromCompanyWorkers, $authUserRole, $authUserName);

            if($updateUserFunc['status'] === 500) {
                return response()->json(['status' => 500, 'message' => $updateUserFunc['message']]);
            }

            return response()->json(['status' => 200, 'message' => 'Data updated!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function generatePassword() {
        $str = 'ABCDEFGHIGKLMNOPQRSTUVWXYZabcdefgghigklmnopqrtuvwxyz1234567890';
        $password = '';

        while(strlen($password) < 16) {
            $index = rand(0, strlen($str) - 1);
            $password .= $str[$index];
        }

        return $password;
    }

    public function add_worker(Request $request) {
        try {
            $request->validate([
                'name' => 'min:5|required|string',
                'email' => 'required|email',
                'role' => 'required|integer'
            ]);

            $companyId = \Auth::user()->company_id;
            $company = \Auth::user()->company_title;
            $tariff = \Auth::user()->tariff;
            $authUserName = \Auth::user()->name;
            $authUserRole = \Auth::user()->role;

            $name = $request->name;
            $email = $request->email;
            $password = $this->generatePassword();
            $role = $request->role;
            $department = $request->department == null ? \DB::table('company_worker')->where('email', \Auth::user()->email)->value('department') : $request->department;

            $teamlead = null;

            if ($authUserRole == 3) {
                $teamlead = $authUserName;
            }

            $status = "company manager";
            $link = env('LOGIN_URL');
            $test = env('TEST_URL');

            $companyWorkerTable = $this->companyWorkerTable;

            $createNewUser = User::createNewUserFunc($companyId, $company, $tariff, $authUserName,
                $authUserRole, $name, $email, $password, $role, $status, $link, $test, $department, $teamlead, $companyWorkerTable);

            if($createNewUser['status'] === 500) {
                return response()->json(['message' => $createNewUser['message'], 'status' => 500]);
            }

            return response()->json(['status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => 500]);
        }
    }

    public function manager_status(Request $r, $email)
    {
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        try {
            if ($companyId) {
                DB::table('users')
                    ->where('email', $r->email)
                    ->update(['role' => 1, "tariff" => (\Auth::user()->tariff === 1) ? 1 : 0,]);

                DB::table($this->companyWorkerTable)
                    ->where(['company_id' => $companyId, 'email' => $r->email])
                    ->update(['role' => 1, "department" => ""]);
            } else {
                DB::table('users')
                    ->where('email', $r->email)
                    ->update(['role' => 1, "tariff" => (\Auth::user()->tariff === 1) ? 1 : 0,]);
            }

            $newChiefName = User::where("email", $r->email)->value("name");
            DB::table('companies')->updateOrInsert([
                "title" => $company,
                "manager" => $newChiefName,
                "manager_email" => $r->email,
            ]);

            return response()->json(['status' => 200]);
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function teamlead_status(Request $r, $email)
    {
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        if($companyId) {
            DB::table('users')
                ->where('email', $r->email)
                ->update(['role' => 3, "tariff" => (\Auth::user()->tariff === 1) ? 1:0,]);

            DB::table($this->companyWorkerTable)
                ->where(['company_id' => $companyId, 'email' => $r->email])
                ->update(['role' => 3]);
        } else {
            DB::table('users')
                ->where('email', $r->email)
                ->update(['role' => 3, "tariff" => (\Auth::user()->tariff === 1) ? 1:0,]);
        }

        $chief = Companies::where("manager_email", $email)->first();
        if($chief)
        {
            Companies::where("manager_email", $email)->delete();
            $maxCompanies = DB::table("companies")->max("id") + 1;
            DB::statement("ALTER TABLE companies AUTO_INCREMENT = $maxCompanies");
        }

        return response()->json(['success' => 'success']);
    }

    public function chief_status(Request $r, $email)
    {
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        try {
            if($companyId) {
                DB::table('users')
                    ->where('email', $r->email)
                    ->update(['role' => 2, "tariff" => (\Auth::user()->tariff === 1) ? 1:0,]);

                DB::table($this->companyWorkerTable)
                    ->where(['company_id' => $companyId, 'email' => $r->email])
                    ->update(['role' => 2]);
            } else {
                DB::table('users')
                    ->where('email', $r->email)
                    ->update(['role' => 2, "tariff" => (\Auth::user()->tariff === 1) ? 1:0,]);
            }

            $chief = Companies::where("manager_email", $email)->first();
            if($chief)
            {
                $maxCompanies = DB::table("companies")->max("id") + 1;
                Companies::where("manager_email", $email)->delete();
                DB::statement("ALTER TABLE companies AUTO_INCREMENT = $maxCompanies");
            }

            return response()->json(['success' => 'success']);
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function employee_status(Request $r, $email)
    {
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        if($companyId) {
            DB::table('users')
                ->where('email', $r->email)
                ->update(['role' => 4, "tariff" => 0]);
            DB::table($this->companyWorkerTable)
                ->where(['company_id' => $companyId, 'email' => $r->email])
                ->update(['role' => 4]);
        } else {
            DB::table('users')
                ->where('email', $r->email)
                ->update(['role' => 4, "tariff" => 0]);
        }

        $chief = Companies::where("manager_email", $email)->first();
        if($chief)
        {
            return response()->json(['message' => 'tyt']);
            Companies::where("manager_email", $email)->delete();
            $maxCompanies = DB::table("companies")->max("id") + 1;
            DB::statement("ALTER TABLE companies AUTO_INCREMENT = $maxCompanies");
        }

        return response()->json(['success' => 'success']);
    }

    public function departments(Request $request)
    {
        $companyId = \Auth::user()->company_id;
        $departments = DB::table($this->companyDepartmentTable)->where(['company_id' => $companyId])->select("id", "title")->orderBy("id", "asc")->paginate(8);

        return view("roles.departments", ["departments" => $departments]);
    }

    public function departments_list(Request $request) {
        $companyId = \Auth::user()->company_id;
        $departments = DB::table($this->companyDepartmentTable)->where(['company_id' => $companyId])->select("id", "title")->orderBy("id", "asc")->paginate(8);

        if($request->ajax()) {
            return view("roles.departments_table", ["departments" => $departments])->render();
        }
    }

    public function addDepartment(Request $request) {
        $email = \Auth::user()->email;
        $name = \Auth::user()->name;
        $companyId = \Auth::user()->company_id;
        $companyTitle = \Auth::user()->comoany_title;
        $title = $request->input("title");
        $companyDepartmentTable = $this->companyDepartmentTable;

        try {
            $model = new User();
            $addDepartment = $model->addDepartmentFunc($email, $name, $companyId, $companyTitle, $title, $companyDepartmentTable);

            if($addDepartment['status'] === 500) {
                return response()->json(['status' => 500, 'message' => $addDepartment['message']]);
            }

            return response()->json(['status' => 200, 'message' => 'New department added!']);
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function deleteDepartment($title)
    {
        $companyId = \Auth::user()->company_id;
        $workers = DB::table($this->companyWorkerTable)
            ->where('department', $title)
            ->count('email');
        if($workers > 0) {
            \Session::put("deleteDepartment_error_user_exist", "You can not delete department, if it has workers!");
        } else {
            DB::table($this->companyDepartmentTable)->where(['company_id' => $companyId, "title" => $title])->delete();
            DB::table($this->companyWorkerTable)->where(['company_id' => $companyId, "department" => $title])->update(["department" => ""]);
        }

        return back();
    }

    public function updateDepartment(Request $request, $title) {
        $companyId = \Auth::user()->company_id;
        $newTitle = $request->newTitle;

        try {
            if(strlen($newTitle) > 0 && strlen($newTitle) <= 50) {
                $departments = DB::table($this->companyDepartmentTable)->where('company_id', $companyId)->get();
                $departments_array = [];
                foreach ($departments as $department) {
                    $departments_array[] = $department->title;
                }

                if(in_array($title, $departments_array)) {
                    return ['status' => 500, 'message' => 'The department exists!'];
                }
                
                DB::table($this->companyDepartmentTable)->where(['company_id' => $companyId, "title" => $title])->update(["title" => $newTitle]);
                return response()->json(['status' => 200, 'title' => $newTitle]);
            }

            else {
                return response()->json(['status' => 500, 'title' => $title, 'message' => 'Max. symbols count equals 50 and min. symbols count equals 1!']);
            }
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'title' => $title, 'message' => $e->getMessage()]);
        }
    }

    public function changeName_chief(Request $request, $name)
    {
        $companyId = \Auth::user()->company_id;
        $newName = $request->name;
        DB::table($this->companyWorkerTable)->where(['company_id' => $companyId, "name" => $name])->update(["name" => $newName]);
        DB::table("users")->where("name", $name)->update(["name" => $newName]);

        return response()->json(["success" => "$name is $newName now!"]);
    }

    public function changeEmail_chief(Request $request, $email)
    {
        $companyId = \Auth::user()->company_id;

        $newEmail = $request->email;
        DB::table($this->companyWorkerTable)->where(['company_id' => $companyId, "email" => $email])->update(["email" => $newEmail]);
        DB::table("users")->where("email", $email)->update(["email" => $newEmail]);

        return response()->json(["success" => "$email is $newEmail now!"]);
    }

    public function checkStatus($userAuthRole, $status) {
        if(($userAuthRole == 1 && (str_contains($status, 'manager') || str_contains($status, 'chief'))) ||
            ($userAuthRole == 2 && (str_contains($status, 'employee') || str_contains($status, 'teamlead'))) ||
            ($userAuthRole == 3 && str_contains($status, 'employee'))) {
            return true;
        }

        return false;
    }
}
