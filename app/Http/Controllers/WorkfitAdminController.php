<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkfitAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'workfit_admin']);
    }

    public function index()
    {
        return view('layouts.admin_modern');
    }

    public function getCompanies()
    {
        $companies = DB::table('companies')
            ->select([
                'companies.id',
                'companies.title',
                'companies.manager',
                'companies.manager_email',
                'users.tariff',
            ])
            ->leftJoin('users', 'users.company_id', '=', 'companies.id')
            ->where('users.role', 1) // Assuming manager role links tariff
            ->groupBy([
                'companies.id',
                'companies.title',
                'companies.manager',
                'companies.manager_email',
                'users.tariff',
            ])
            ->orderByDesc('companies.id')
            ->paginate(10);

        return response()->json($companies);
    }

    public function getUsers(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete yourself.'], 403);
        }

        if ($user->role === 0) {
            return response()->json(['message' => 'Cannot delete Super Admin.'], 403);
        }

        $user->delete();
        return response()->json(['status' => 'ok']);
    }

    public function impersonate($id)
    {
        $user = User::findOrFail($id);
        
        // Guard against impersonating other super admins if needed, but usually allowed.
        
        auth()->loginUsingId($user->id);

        $redirect = ((int) $user->role === 4)
            ? route('employee.dashboard')
            : route('home');

        return response()->json(['redirect' => $redirect]);
    }

    public function getSubscriptionList()
    {
        $subscriptions = DB::table('subscriptions')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->select([
                'subscriptions.*',
                'users.name as user_name',
                'users.email as user_email'
            ])
            ->orderByDesc('subscriptions.created_at')
            ->paginate(10);

        return response()->json($subscriptions);
    }

    public function getCompanyList()
    {
        return $this->getCompanies();
    }

    public function getUsersList()
    {
        return $this->getUsers(request());
    }

    public function getCompany($id)
    {
        $company = Companies::findOrFail($id);
        
        $manager = User::where('email', $company->manager_email)
            ->where('role', 1)
            ->first();
        
        $workerCount = DB::table('company_worker')
            ->where('company_id', $id)
            ->count();
            
        $departmentCount = DB::table('company_department')
            ->where('company_id', $id)
            ->count();

        return response()->json([
            'company' => $company,
            'manager' => $manager,
            'worker_count' => $workerCount,
            'department_count' => $departmentCount,
        ]);
    }
}
