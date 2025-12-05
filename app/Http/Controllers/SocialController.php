<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

class SocialController extends Controller
{
    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function loginWithGoogle()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            if (empty($googleUser->email)) {
                \Session::put('google_auth_error', "Unable to retrieve email from Google account.");
                return redirect()->back();
            }
            
            $existingUser = User::where('google_id', $googleUser->id)->first();
            if ($existingUser) {
                Auth::login($existingUser);
                return redirect('/home');
            }

            $userByEmail = User::where('email', $googleUser->email)->first();
            if ($userByEmail) {
                $userByEmail->update(['google_id' => $googleUser->id]);
                Auth::login($userByEmail);
                return redirect('/home');
            }

            $newUser = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'role' => 4,
                'company_id' => null,
                'password' => null,
            ]);

            Auth::login($newUser);
            return redirect('/home');

        } catch (Exception $exception) {
            \Session::put('google_auth_error', "Google authentication is currently unavailable.");
            return redirect()->back();
        }
    }

}
