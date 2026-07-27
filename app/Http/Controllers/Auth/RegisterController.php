<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Companies;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\OrganizationEntitlementService;
use App\Services\OrganizationService;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
    public function __construct(
        protected OrganizationService $organizations,
        protected OrganizationEntitlementService $entitlements
    ) {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
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
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return User
     */
    protected function create(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $companyId = DB::table('companies')->insertGetId([
                    'title' => $data['company_title'],
                    'manager' => $data['name'],
                    'manager_email' => $data['email'],
                    'status' => 'active',
                ]);

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'company_id' => $companyId,
                    'company_title' => $data['company_title'],
                    'role' => 1,
                    'company' => 1,
                    'status' => 'active',
                    'password' => Hash::make($data['password']),
                ]);

                DB::table('company_worker')->insert([
                    'company_id' => $companyId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => 1,
                    'status' => 'active',
                ]);
                $this->organizations->synchronize($user, $user, null, null, 'active');
                $this->entitlements->ensureBillingOwner(
                    Companies::findOrFail($companyId),
                    $user
                );

                return $user;
            });
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'email' => [__('validation.unique', ['attribute' => 'email'])],
                ]);
            }

            throw $e;
        }
    }
}
