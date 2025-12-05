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
            $fbUser = Socialite::driver('facebook')->user();
            
            if (empty($fbUser->email)) {
                \Session::put('facebook_auth_error', "Unable to retrieve email from Facebook account. Please ensure email permissions are granted.");
                return redirect()->back();
            }
            
            $existingUser = User::where('fb_id', $fbUser->id)->first();
            if ($existingUser) {
                Auth::login($existingUser);
                return redirect('/home');
            }

            $userByEmail = User::where('email', $fbUser->email)->first();
            if ($userByEmail) {
                $userByEmail->update(['fb_id' => $fbUser->id]);
                Auth::login($userByEmail);
                return redirect('/home');
            }

            $newUser = User::create([
                'name' => $fbUser->name,
                'email' => $fbUser->email,
                'fb_id' => $fbUser->id,
                'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                'role' => 4,
                'company_id' => null,
            ]);

            Auth::login($newUser);
            return redirect('/home');

        } catch (\Exception $e) {
            \Session::put('facebook_auth_error', "Facebook authentication is currently unavailable.");
            return redirect()->back();
        }
    }
}
