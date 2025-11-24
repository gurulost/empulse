<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\CompanyWorker;
use App\Services\UserService;
use App\Services\DepartmentService;
use App\Http\Requests\Admin\AddWorkerRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\AddDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;

use App\Http\Controllers\UserController;

class TeamController extends Controller
{
    protected UserService $userService;
    protected DepartmentService $departmentService;

    public function __construct(UserService $userService, DepartmentService $departmentService)
    {
        $this->middleware('auth');
        $this->userService = $userService;
        $this->departmentService = $departmentService;
    }

    public function index()
    {
        return view('team.manage');
    }

    // --- Members API ---

    public function getMembers(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $authRole = Auth::user()->role;
        $authEmail = Auth::user()->email;
        $authName = Auth::user()->name;

        $query = DB::table('company_worker')
            ->where('company_id', $companyId)
            ->select('name', 'email', 'role', 'department');

        // Role-based access control
        if ($authRole == 1) { // Manager: allow optional filters but default to entire roster
            if ($request->filled('role')) {
                $query->where('role', (int) $request->role);
            }

            if ($request->filled('department')) {
                $query->where('department', $request->department);
            }
        } elseif ($authRole == 2) { // Chief
            $department = DB::table('company_worker')->where(['company_id' => $companyId, 'email' => $authEmail])->value('department');
            $query->where('department', $department);
            
            if ($request->has('role') && $request->role) {
                $query->where('role', (int)$request->role);
            } else {
                $query->whereIn('role', [3, 4]);
            }
            
        } elseif ($authRole == 3) { // Teamlead
            $query->where(['role' => 4, 'supervisor' => $authName]);
        }

        // Search
        if ($search = $request->input('q')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'name');
        $dir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'email', 'role', 'department'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir);
        }

        $members = $query->paginate(10);

        return response()->json($members);
    }

    public function addMember(AddWorkerRequest $request)
    {
        $companyId = Auth::user()->company_id;
        $company = Auth::user()->company_title;
        $tariff = Auth::user()->tariff;
        $authUserName = Auth::user()->name;
        $authUserRole = Auth::user()->role;

        $name = $request->name;
        $email = $request->email;
        $role = $request->role;
        
        // Generate random password using UserService
        $password = $this->userService->generatePassword();
        
        $department = $request->department;
        if (!$department) {
            $department = DB::table('company_worker')->where('email', Auth::user()->email)->value('department');
        }

        $teamlead = ($authUserRole == 3) ? $authUserName : null;
        
        $status = "company manager"; // Default, logic inside UserService handles specific roles
        $link = config('app.login_url');
        $test = config('app.test_url');
        $companyWorkerTable = 'company_worker';

        $result = $this->userService->createUser(
            $companyId, $company, $tariff, $authUserName, $authUserRole,
            $name, $email, $password, $role, $status, $link, $test,
            $department, $teamlead, $companyWorkerTable
        );

        if ($result['status'] === 500) {
            return response()->json(['message' => $result['message']], 500);
        }

        return response()->json(['message' => 'Member added successfully']);
    }

    public function updateMember(UpdateUserRequest $request, $email)
    {
        $companyId = Auth::user()->company_id;
        $company = Auth::user()->company_title;
        
        $userFromUsers = User::where('email', $email)->first();
        $userFromCompanies = DB::table('companies')->where('manager_email', $email)->first();
        $userFromCompanyWorkers = DB::table('company_worker')->where('email', $email)->first();
        
        if ($userFromUsers) {
            $this->authorize('update', $userFromUsers);
        } elseif ($userFromCompanyWorkers) {
            // $this->authorize('update', new CompanyWorker((array)$userFromCompanyWorkers));
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->userService->updateUser(
            $email, $companyId, $company,
            $request->new_name,
            $request->new_email,
            $request->new_role,
            $request->new_department,
            $userFromUsers,
            $userFromCompanies,
            $userFromCompanyWorkers,
            Auth::user()->role,
            Auth::user()->name
        );

        if ($result['status'] === 500) {
            return response()->json(['message' => $result['message']], 500);
        }

        return response()->json(['message' => 'Member updated successfully']);
    }

    public function deleteMember($email)
    {
        $companyId = Auth::user()->company_id;
        $company = Auth::user()->company_title;

        $user = User::where('email', $email)->first();
        if ($user) {
            $this->authorize('delete', $user);
        }

        $success = $this->userService->deleteByEmail($email, $companyId, $company, 'company_department', 'company_worker');

        if ($success) {
            return response()->json(['message' => 'Member deleted successfully']);
        }

        return response()->json(['message' => 'Failed to delete member'], 500);
    }

    public function importUsers(Request $request)
    {
        return app(UserController::class)->importUsers($request);
    }

    // --- Departments API ---

    public function getDepartments()
    {
        $companyId = Auth::user()->company_id;
        $departments = $this->departmentService->list($companyId, 100);

        if ($departments instanceof \Illuminate\Contracts\Pagination\Paginator) {
            $departments = $departments->items();
        }

        return response()->json($departments);
    }

    public function addDepartment(AddDepartmentRequest $request)
    {
        $companyId = Auth::user()->company_id;
        $result = $this->departmentService->add($companyId, $request->title);

        if ($result['status'] === 500) {
            return response()->json(['message' => $result['message']], 500);
        }

        return response()->json(['message' => 'Department added successfully']);
    }

    public function updateDepartment(UpdateDepartmentRequest $request, $oldTitle)
    {
        $companyId = Auth::user()->company_id;
        $result = $this->departmentService->update($companyId, $oldTitle, $request->newTitle);

        if ($result['status'] === 500) {
            return response()->json(['message' => $result['message']], 500);
        }

        return response()->json(['message' => 'Department updated successfully']);
    }

    public function deleteDepartment($title)
    {
        $companyId = Auth::user()->company_id;
        $result = $this->departmentService->delete($companyId, $title);

        if ($result['status'] === 500) {
            return response()->json(['message' => $result['message']], 500);
        }

        return response()->json(['message' => 'Department deleted successfully']);
    }
}
