<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class FacebookController extends Controller
{
    public function createCompanyId($email, $name, $companyTitle) {
        $companyId = DB::table("companies")->where("manager_email", $email)->first();
        if($companyId == null) {
            $companyId = DB::table("companies")->insertGetId([
                "title" => $companyTitle,
                "manager" => $name,
                "manager_email" => $email,
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

            if (DB::table('company_worker')->where(["company_id" => $companyId, "email" => $email])->first()) {
                DB::table('company_worker')->where([
                    "company_id" => $companyId,
                    "email" => $email
                ])->update([
                    "name" => $name,
                    "manager" => "yes"
                ]);
            } else {
                DB::table('company_worker')->insert([
                    "company_id" => $companyId,
                    "name" => $name,
                    "email" => $email,
                    "manager" => "yes"
                ]);
            }
        }
    }

    public function facebookRedirect()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function facebookLogin()
    {
        try {

            $user = Socialite::driver('facebook')->user();
            $isUser = User::where('fb_id', $user->id)->first();
            $email = User::where('email', $user->email)->first();

            if($isUser)
            {
                Auth::login($isUser);
                return redirect('/home');
            }

            else if($email)
            {
                Auth::login($email);
                return redirect('/home');
            }

            else
            {
                $createUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'fb_id' => $user->id,
                    'password' => 'user',
                ]);

                Auth::login($createUser);
                return redirect('/home');
            }
        }

        catch(\Exception $e) {
            $session = \Session::put('facebook_auth_error', "Now you can't auth via facebook!");
            return response()->back()->with($session);
        }
    }
}
