<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Models\Companies;
use Illuminate\Support\Facades\Gate;
use App\Models\CompanyWorker;
use App\Services\UserService;
use App\Services\DepartmentService;
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

    protected UserService $userService;
    protected DepartmentService $departmentService;

    public function __construct(UserService $userService, DepartmentService $departmentService)
    {
        $this->userService = $userService;
        $this->departmentService = $departmentService;
    }

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
                $q = trim((string)$request->get('q', ''));
                $filterRole = $request->get('role');
                $filterDepartment = $request->get('department');

                $departments = DB::table($this->companyDepartmentTable)->where('company_id', $companyId)->get();
                $head = User::where([['company', 1], ['company_id', $companyId]])->value('email');
                $manager = null; $department = null; $chief = null; $teamlead_department = null;

                $authRole = \Auth::user()->role;
                $qb = DB::table($this->companyWorkerTable)->select('*')->where('company_id', $companyId);

                if ($authRole == 1) { // manager view: managers + chiefs
                    if ($filterRole) {
                        $qb->where('role', (int)$filterRole);
                    } else {
                        $qb->whereIn('role', [1,2]);
                    }
                    if ($filterDepartment) {
                        $qb->where('department', $filterDepartment);
                    }
                } elseif ($authRole == 2) { // chief view: teamleads + employees in own department
                    $department = DB::table($this->companyWorkerTable)->where(['company_id' => $companyId, 'email' => $email])->value('department');
                    $manager = DB::table($this->companyWorkerTable)->where(['company_id' => $companyId, 'role' => 1])->value('name');
                    $qb->where('department', $department);
                    if ($filterRole) { $qb->where('role', (int)$filterRole); } else { $qb->whereIn('role', [3,4]); }
                } elseif ($authRole == 3) { // teamlead view: supervised employees
                    $teamlead_department = DB::table($this->companyWorkerTable)->where(['company_id' => $companyId, 'email' => $email])->value('department');
                    $chief = DB::table($this->companyWorkerTable)->where(['company_id' => $companyId, 'role' => 2, 'department' => $teamlead_department])->value('name');
                    $qb->where(['role' => 4, 'supervisor' => $name]);
                }

                if ($q !== '') {
                    $qb->where(function($qq) use ($q) {
                        $qq->where('name', 'LIKE', "%$q%")
                           ->orWhere('email', 'LIKE', "%$q%");
                    });
                }

                $sort = $request->get('sort');
                $dir = strtolower((string)$request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
                $sortable = ['name','email','role','department'];
                if (!in_array($sort, $sortable)) { $sort = 'name'; }

                $users = $qb->orderBy($sort, $dir)->paginate(5)->appends([
                    'q' => $q,
                    'role' => $filterRole,
                    'department' => $filterDepartment,
                    'sort' => $sort,
                    'dir' => $dir,
                ]);

                return view('roles.usersPagination', compact('users', 'departments', 'manager', 'department', 'chief', 'teamlead_department', 'head'))->render();
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
            $uUsers = User::where('email', $email)->first();
            $uWorkers = CompanyWorker::where(['company_id' => $companyId, 'email' => $email])->first();
            if ($uUsers) { $this->authorize('delete', $uUsers); }
            elseif ($uWorkers) { $this->authorize('delete', $uWorkers); }
            else { return response()->json(['status' => 404, 'message' => 'User not found']); }
            $deleteUser = $this->userService->deleteByEmail($email, $companyId, $company, $this->companyDepartmentTable, $this->companyWorkerTable);

            if($deleteUser) {
                return response()->json(['status' => 200]);
            } else {
                return response()->json(['status' => 500, 'message' => 'Something went wrong!']);
            }
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function updateUser(\App\Http\Requests\Admin\UpdateUserRequest $request, $email) {
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

            if ($userFromUsers) { $this->authorize('update', $userFromUsers); }
            elseif ($userFromCompanyWorkers) { $this->authorize('update', new CompanyWorker((array)$userFromCompanyWorkers)); }
            else { return response()->json(['status' => 404, 'message' => 'User not found']); }

            $updateUserFunc = $this->userService->updateUser($email, $companyId, $company, $new_name,
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

    public function add_worker(\App\Http\Requests\Admin\AddWorkerRequest $request) {
        try {
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

            $createNewUser = $this->userService->createUser($companyId, $company, $tariff, $authUserName,
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
            $uUsers = User::where('email', $r->email)->first();
            $uWorkers = CompanyWorker::where(['company_id' => $companyId, 'email' => $r->email])->first();
            if ($uUsers) { $this->authorize('update', $uUsers); }
            elseif ($uWorkers) { $this->authorize('update', $uWorkers); }
            else { return response()->json(['status' => 404, 'message' => 'User not found']); }
            $res = $this->userService->setManager($companyId, $company, $r->email, (int)\Auth::user()->tariff);
            return response()->json(['status' => $res['status'], 'message' => $res['status'] === 200 ? 'OK' : ($res['message'] ?? 'Error')]);
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function teamlead_status(Request $r, $email)
    {
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        $uUsers = User::where('email', $r->email)->first();
        $uWorkers = CompanyWorker::where(['company_id' => $companyId, 'email' => $r->email])->first();
        if ($uUsers) { $this->authorize('update', $uUsers); }
        elseif ($uWorkers) { $this->authorize('update', $uWorkers); }
        else { return response()->json(['status' => 404, 'message' => 'User not found']); }

        $res = $this->userService->setTeamlead($companyId, $r->email, (int)\Auth::user()->tariff);
        return response()->json(['status' => $res['status'], 'message' => $res['status'] === 200 ? 'OK' : ($res['message'] ?? 'Error')]);
    }

    public function chief_status(Request $r, $email)
    {
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        try {
            $uUsers = User::where('email', $r->email)->first();
            $uWorkers = CompanyWorker::where(['company_id' => $companyId, 'email' => $r->email])->first();
            if ($uUsers) { $this->authorize('update', $uUsers); }
            elseif ($uWorkers) { $this->authorize('update', $uWorkers); }
            else { return response()->json(['status' => 404, 'message' => 'User not found']); }
            $res = $this->userService->setChief($companyId, $company, $r->email, (int)\Auth::user()->tariff);
            return response()->json(['status' => $res['status'], 'message' => $res['status'] === 200 ? 'OK' : ($res['message'] ?? 'Error')]);
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function employee_status(Request $r, $email)
    {
        $companyId = \Auth::user()->company_id;
        $company = \Auth::user()->company_title;

        Gate::authorize('manage-email', $email);
        $uUsers = User::where('email', $r->email)->first();
        $uWorkers = CompanyWorker::where(['company_id' => $companyId, 'email' => $r->email])->first();
        if ($uUsers) { $this->authorize('update', $uUsers); }
        elseif ($uWorkers) { $this->authorize('update', $uWorkers); }
        else { return response()->json(['status' => 404, 'message' => 'User not found']); }

        $res = $this->userService->setEmployee($companyId, $company, $r->email);
        return response()->json(['status' => $res['status'], 'message' => $res['status'] === 200 ? 'OK' : ($res['message'] ?? 'Error')]);
    }

    public function departments(Request $request)
    {
        $companyId = \Auth::user()->company_id;
        $departments = $this->departmentService->list($companyId, 8);

        return view("roles.departments", ["departments" => $departments]);
    }

    public function departments_list(Request $request) {
        $companyId = \Auth::user()->company_id;
        $departments = $this->departmentService->list($companyId, 8);

        if($request->ajax()) {
            return view("roles.departments_table", ["departments" => $departments])->render();
        }
    }

    public function addDepartment(\App\Http\Requests\Admin\AddDepartmentRequest $request) {
        $email = \Auth::user()->email;
        $name = \Auth::user()->name;
        $companyId = \Auth::user()->company_id;
        $companyTitle = \Auth::user()->company_title;
        $title = $request->input("title");

        try {
            $addDepartment = $this->departmentService->add($companyId, $title);

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
        $res = $this->departmentService->delete($companyId, $title);
        if ($res['status'] !== 200) {
            \Session::put('deleteDepartment_error_user_exist', $res['message'] ?? 'Unable to delete department');
        }
        return back();
    }

    public function updateDepartment(\App\Http\Requests\Admin\UpdateDepartmentRequest $request, $title) {
        $companyId = \Auth::user()->company_id;
        $newTitle = $request->newTitle;

        try {
            $res = $this->departmentService->update($companyId, $title, $newTitle);
            if ($res['status'] !== 200) {
                return response()->json(['status' => 500, 'title' => $res['title'] ?? $title, 'message' => $res['message'] ?? 'Unable to update']);
            }
            return response()->json(['status' => 200, 'title' => $res['title']]);
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'title' => $title, 'message' => $e->getMessage()]);
        }
    }

    public function changeName_chief(\App\Http\Requests\Admin\ChangeNameRequest $request, $name)
    {
        $companyId = \Auth::user()->company_id;
        $newName = $request->name;
        $worker = CompanyWorker::where(['company_id' => $companyId, 'name' => $name])->first();
        if (!$worker) {
            return response()->json(['status' => 403, 'message' => 'Forbidden']);
        }
        $this->authorize('update', $worker);
        DB::table($this->companyWorkerTable)->where(['company_id' => $companyId, "name" => $name])->update(["name" => $newName]);
        DB::table("users")->where(["name" => $name, 'company_id' => $companyId])->update(["name" => $newName]);

        return response()->json(["success" => "$name is $newName now!"]);
    }

    public function changeEmail_chief(\App\Http\Requests\Admin\ChangeEmailRequest $request, $email)
    {
        $companyId = \Auth::user()->company_id;
        $newEmail = $request->email;
        $worker = CompanyWorker::where(['company_id' => $companyId, 'email' => $email])->first();
        if ($worker) { $this->authorize('update', $worker); }
        else {
            $u = User::where(['company_id' => $companyId, 'email' => $email])->first();
            if ($u) { $this->authorize('update', $u); }
            else { return response()->json(['status' => 404, 'message' => 'User not found']); }
        }
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
