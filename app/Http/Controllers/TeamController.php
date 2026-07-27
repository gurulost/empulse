<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Admin\AddDepartmentRequest;
use App\Http\Requests\Admin\AddWorkerRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Companies;
use App\Models\CompanyWorker;
use App\Models\User;
use App\Services\DepartmentService;
use App\Services\UserService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function index(Request $request)
    {
        $hasCompanyContext = (bool) ($request->user()?->company_id);

        return view('team.manage', [
            'hasCompanyContext' => $hasCompanyContext,
        ]);
    }

    // --- Members API ---

    public function getMembers(Request $request)
    {
        $company = $this->authorizeCompany('manageMembers');
        $companyId = $company->id;
        $authRole = Auth::user()->role;
        $authEmail = Auth::user()->email;
        $authName = Auth::user()->name;

        $query = DB::table('company_worker')
            ->where('company_id', $companyId)
            ->where('status', 'active')
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
                $query->where('role', (int) $request->role);
            } else {
                $query->whereIn('role', [3, 4]);
            }

        } elseif ($authRole == 3) { // Teamlead
            $query->where(['role' => 4, 'supervisor' => $authName]);
        }

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
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
        $company = $this->authorizeCompany('manageMembers');
        $authUser = Auth::user();
        $companyId = $company->id;
        $companyTitle = $company->title ?? $authUser->company_title;
        $tariff = $authUser->tariff;
        $authUserName = $authUser->name;
        $authUserRole = $authUser->role;

        $name = $request->name;
        $email = $request->email;
        $role = $request->role;
        try {
            $targetRole = Role::from((int) $role);
        } catch (\ValueError $e) {
            return response()->json(['message' => 'Invalid role selection'], 422);
        }

        if (! $this->userService->userCanAssignRole($authUser, $targetRole)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Generate random password using UserService
        $password = $this->userService->generatePassword();

        $department = $request->department;
        if (! $department) {
            $department = DB::table('company_worker')->where('email', Auth::user()->email)->value('department');
        }

        $teamlead = ($authUserRole == Role::TEAMLEAD->value) ? $authUserName : null;

        $status = $this->userService->roleLabel($targetRole);
        $link = config('app.login_url');
        $test = config('app.test_url');
        $companyWorkerTable = 'company_worker';

        $result = $this->userService->createUser(
            $companyId, $companyTitle, $tariff, $authUserName, $authUserRole,
            $name, $email, $password, $targetRole->value, $status, $link, $test,
            $department, $teamlead, $companyWorkerTable
        );

        if ($result['status'] === 500) {
            return response()->json(['message' => 'Member could not be added.'], 500);
        }

        return response()->json(['message' => 'Member added successfully']);
    }

    public function updateMember(UpdateUserRequest $request, $email)
    {
        $company = $this->authorizeCompany('manageMembers');
        $authUser = Auth::user();
        $companyId = $company->id;
        $companyTitle = $company->title ?? $authUser->company_title;

        $userFromUsers = User::where('email', $email)->first();
        $userFromCompanies = DB::table('companies')->where('manager_email', $email)->first();
        $userFromCompanyWorkers = DB::table('company_worker')->where('email', $email)->first();

        if ($userFromUsers) {
            $this->authorize('update', $userFromUsers);
        } elseif ($userFromCompanyWorkers) {
            $workerModel = new CompanyWorker((array) $userFromCompanyWorkers);
            $workerModel->exists = true;
            $this->authorize('update', $workerModel);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }

        $newRoleValue = $request->new_role;
        if (! is_null($newRoleValue)) {
            try {
                $newRoleEnum = Role::from((int) $newRoleValue);
            } catch (\ValueError $e) {
                return response()->json(['message' => 'Invalid role selection'], 422);
            }

            if (! $this->userService->userCanAssignRole($authUser, $newRoleEnum)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $result = $this->userService->updateUser(
            $email, $companyId, $companyTitle,
            $request->new_name,
            $request->new_email,
            $request->new_role,
            $request->new_department,
            $userFromUsers,
            $userFromCompanies,
            $userFromCompanyWorkers,
            $authUser->role,
            $authUser->name
        );

        if ($result['status'] === 500) {
            return response()->json(['message' => 'Member could not be updated.'], 500);
        }

        return response()->json(['message' => 'Member updated successfully']);
    }

    public function deleteMember($email)
    {
        $company = $this->authorizeCompany('manageMembers');
        $companyId = $company->id;
        $companyTitle = $company->title ?? Auth::user()->company_title;

        $user = User::where('email', $email)->first();
        if ($user) {
            $this->authorize('delete', $user);
        }

        $success = $this->userService->deleteByEmail($email, $companyId, $companyTitle, 'company_department', 'company_worker');

        if ($success) {
            return response()->json(['message' => 'Member deactivated successfully']);
        }

        return response()->json(['message' => 'Failed to deactivate member'], 500);
    }

    // --- Departments API ---

    public function getDepartments()
    {
        $company = $this->authorizeCompany('manageDepartments');
        $departments = $this->departmentService->list($company->id, 100);

        if ($departments instanceof Paginator) {
            $departments = $departments->items();
        }

        return response()->json($departments);
    }

    public function addDepartment(AddDepartmentRequest $request)
    {
        $company = $this->authorizeCompany('manageDepartments');
        $result = $this->departmentService->add($company->id, $request->title);

        if ($result['status'] === 500) {
            return response()->json(['message' => 'Department could not be added.'], 500);
        }

        return response()->json(['message' => 'Department added successfully']);
    }

    public function updateDepartment(UpdateDepartmentRequest $request, $oldTitle)
    {
        $company = $this->authorizeCompany('manageDepartments');
        $result = $this->departmentService->update($company->id, $oldTitle, $request->newTitle);

        if ($result['status'] === 500) {
            return response()->json(['message' => 'Department could not be updated.'], 500);
        }

        return response()->json(['message' => 'Department updated successfully']);
    }

    public function deleteDepartment($title)
    {
        $company = $this->authorizeCompany('manageDepartments');
        $result = $this->departmentService->delete($company->id, $title);

        if ($result['status'] === 500) {
            return response()->json(['message' => 'Department could not be removed.'], 500);
        }

        return response()->json(['message' => 'Department deleted successfully']);
    }

    protected function authorizeCompany(string $ability): Companies
    {
        $companyId = Auth::user()->company_id;
        if (! $companyId) {
            abort(403, 'Company context required.');
        }

        $company = Companies::findOrFail($companyId);
        $this->authorize($ability, $company);

        return $company;
    }

    public function deleteMemberLegacy($email)
    {
        try {
            $company = $this->authorizeCompany('manageMembers');
            $companyId = $company->id;
            $companyTitle = $company->title ?? Auth::user()->company_title;

            $user = User::where('email', $email)->first();
            if ($user) {
                $this->authorize('delete', $user);
            }

            $success = $this->userService->deleteByEmail($email, $companyId, $companyTitle, 'company_department', 'company_worker');

            if ($success) {
                return response()->json(['status' => 200, 'message' => 'Member deleted successfully']);
            }

            return response()->json(['status' => 500, 'message' => 'Failed to delete member']);
        } catch (\Exception) {
            return response()->json(['status' => 500, 'message' => 'Member could not be removed.']);
        }
    }

    public function getMembersLegacy(Request $request)
    {
        try {
            $company = $this->authorizeCompany('manageMembers');
            $companyId = $company->id;
            $authRole = Auth::user()->role;
            $authEmail = Auth::user()->email;
            $authName = Auth::user()->name;

            $query = DB::table('company_worker')
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->select('id', 'name', 'email', 'role', 'department');

            if ($authRole == 1) {
                // Manager sees all
            } elseif ($authRole == 2) {
                $department = DB::table('company_worker')->where(['company_id' => $companyId, 'email' => $authEmail])->value('department');
                $query->where('department', $department)->whereIn('role', [3, 4]);
            } elseif ($authRole == 3) {
                $query->where(['role' => 4, 'supervisor' => $authName]);
            }

            $members = $query->orderBy('name', 'asc')->paginate(25);

            return view('team.partials.members-table', ['users' => $members]);
        } catch (\Exception) {
            return response()->json(['status' => 500, 'message' => 'Members could not be loaded.']);
        }
    }

    public function getDepartmentsLegacy(Request $request)
    {
        try {
            $company = $this->authorizeCompany('manageDepartments');
            $departments = DB::table('company_department')
                ->where('company_id', $company->id)
                ->orderBy('id', 'desc')
                ->paginate(10);

            return view('team.partials.departments-table', ['departments' => $departments]);
        } catch (\Exception) {
            return response()->json(['status' => 500, 'message' => 'Departments could not be loaded.']);
        }
    }

    public function addDepartmentLegacy(Request $request)
    {
        try {
            $company = $this->authorizeCompany('manageDepartments');
            $title = $request->input('title');

            if (empty($title) || strlen($title) > 30) {
                return response()->json(['status' => 500, 'message' => 'Invalid department title']);
            }

            $result = $this->departmentService->add($company->id, $title);

            if ($result['status'] === 500) {
                return response()->json(['status' => 500, 'message' => 'Department could not be added.']);
            }

            return response()->json(['status' => 200, 'message' => 'Department added successfully']);
        } catch (\Exception) {
            return response()->json(['status' => 500, 'message' => 'Department could not be added.']);
        }
    }

    public function deleteDepartmentLegacy($title)
    {
        try {
            $company = $this->authorizeCompany('manageDepartments');
            $result = $this->departmentService->delete($company->id, $title);

            if ($result['status'] === 500) {
                return redirect()->back()->with('error', 'Department could not be removed.');
            }

            return redirect()->back()->with('success', 'Department deleted successfully');
        } catch (\Exception) {
            return redirect()->back()->with('error', 'Department could not be removed.');
        }
    }
}
