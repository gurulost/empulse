<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Hash;
use Auth;
use App\Imports\UsersImport;
use App\Models\Companies;
use Illuminate\Support\Facades\DB;
use App\Services\SurveyAnalyticsService;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected SurveyAnalyticsService $surveyAnalytics;

    public function __construct(SurveyAnalyticsService $surveyAnalytics)
    {
        $this->middleware('auth');
        $this->surveyAnalytics = $surveyAnalytics;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public $companyDepartments = 'company_department';

    public function index() {
        return view('dashboard.analytics');
    }

    public function createCompanyId($email, $name, $companyTitle)
    {
        $company = DB::table("companies")->where("title", $companyTitle)->first();
        if (!$company) {
            $companyId = DB::table("companies")->insertGetId([
                "title" => $companyTitle,
                "manager" => $name,
                "manager_email" => $email,
            ]);

            /*$default_department = [
                ['company_id' => $companyId, 'title' => "Marketing & Proposals Department"],
                ['company_id' => $companyId, 'title' => "Sales Department"],
                ['company_id' => $companyId, 'title' => "Project Department"],
                ['company_id' => $companyId, 'title' => "Designing Department"],
                ['company_id' => $companyId, 'title' => "Production Department"],
                ['company_id' => $companyId, 'title' => "Maintenance Department"],
                ['company_id' => $companyId, 'title' => "Store Department"],
                ['company_id' => $companyId, 'title' => "Procurement Department"],
                ['company_id' => $companyId, 'title' => "Quality Department"],
                ['company_id' => $companyId, 'title' => "Inspection department"],
                ['company_id' => $companyId, 'title' => "Packaging Department"],
                ['company_id' => $companyId, 'title' => "Finance Department"],
                ['company_id' => $companyId, 'title' => "Dispatch Department"],
                ['company_id' => $companyId, 'title' => "Account Department"],
                ['company_id' => $companyId, 'title' => "Research & Development Department"],
                ['company_id' => $companyId, 'title' => "Information Technology Department"],
                ['company_id' => $companyId, 'title' => "Human Resource Department"],
                ['company_id' => $companyId, 'title' => "Security Department"],
                ['company_id' => $companyId, 'title' => "Administration department"],
            ];

            DB::table("company_department")->insert($default_department);*/

            DB::table("users")->where("email", $email)->update(["company_id" => $companyId]);

            if (DB::table('company_worker')->where(["company_id" => $companyId, "email" => $email])->first()) {
                DB::table('company_worker')->where([
                    "company_id" => $companyId,
                    "email" => $email
                ])->update([
                    "name" => $name,
                    "company_title" => $companyTitle,
                    "role" => 1
                ]);
            } else {
                DB::table('company_worker')->insertOrIgnore([
                    "company_id" => $companyId,
                    "name" => $name,
                    "company_title" => $companyTitle,
                    "email" => $email,
                    "role" => 1
                ]);
            }
        }
    }

    public function updatePassword(Request $request, $email) {
        $companyTitle = $request->input("company_title");
        $password = $request->input("new_password");
        $name = Auth::user()->name;

        $createCompanyId = $this->createCompanyId($email, \Auth::user()->name, $companyTitle);

        $model = new User();
        $updatePassword = $model->updateUserPasswordFunc($name, $email, $companyTitle, $password);

        if($updatePassword['status'] === 500) {
            $session = \Session::put('update_user_password', $updatePassword['message']);
            return back()->with($session);
        }

        return response()->redirectTo('home');
    }

    public function update_coworker_name($param, $currenty, $new) {
        try {

            $model = new User();
            $updateCoworkerName = $model->updateCoworkerNameFunc($param, $currenty, $new);

            if($updateCoworkerName['status'] === 500) {
                return response()->json(["status" => 500, 'message' => $updateCoworkerName['message']]);
            }

            return response()->json(["status" => 200]);
        } catch(\Exception $e) {
            return response()->json(["status" => 500, 'message' => $e->getMessage()]);
        }
    }

    public function update_coworker_department($email, $department) {
        try {
            $model = new User();
            $updateCoworkerDepartment = $model->updateCoworkerDepartmentFunc($email, $department);

            if($updateCoworkerDepartment['status'] === 500) {
                return response()->json(["status" => 500, 'message' => $updateCoworkerDepartment['message']]);
            }

            return response()->json(["status" => 200]);
        } catch(\Exception $e) {
            return response()->json(["status" => 500, 'message' => $e->getMessage()]);
        }
    }
}
