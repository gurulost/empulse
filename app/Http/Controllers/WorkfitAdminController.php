<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use App\Models\User;
use App\Services\OnboardingReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkfitAdminController extends Controller
{
    public function __construct(protected OnboardingReportService $onboardingReport)
    {
        $this->middleware(['auth', 'capability:workfit.admin']);
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
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }

    public function getSubscriptionList()
    {
        $subscriptions = DB::table('subscriptions')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->select([
                'subscriptions.*',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->orderByDesc('subscriptions.created_at')
            ->paginate(10);

        return response()->json($subscriptions);
    }

    public function getOnboardingReport(Request $request)
    {
        $report = $this->onboardingReport->report(
            page: max(1, (int) $request->input('page', 1)),
            search: $request->input('search'),
            perPage: 10,
            stage: $request->input('stage'),
        );

        return response()->json($report);
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
