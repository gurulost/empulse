<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                'unique:company_worker,email',
                Rule::unique('companies', 'manager_email'),
            ],
            'company_title' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\Models\User
     */

    protected function create(array $data)
    {
        $user = User::where('email', $data['email'])->first();
        $ifWorkerExist = DB::table('company_worker')->where("email", $data["email"])->first();
        $ifCompanyExist = DB::table("companies")->where("manager_email", $data["email"])->first();

        if ($user || $ifWorkerExist || $ifCompanyExist) {
            throw ValidationException::withMessages([
                'email' => [__('validation.unique', ['attribute' => 'email'])],
            ]);
        }

        $companyId = DB::table("companies")->insertGetId([
            "title" => $data['company_title'],
            "manager" => $data["name"],
            "manager_email" => $data["email"],
        ]);

        $default_department = [
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

        DB::table("company_department")->insert($default_department);

        DB::table('company_worker')->insert([
            "company_id" => $companyId,
            "name" => $data["name"],
            "email" => $data["email"],
            "role" => 1
        ]);

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'company_id' => $companyId,
            'company_title' => $data['company_title'],
            'role' => 1,
            'company' => 1,
            'password' => Hash::make($data['password']),
        ]);
    }

}
