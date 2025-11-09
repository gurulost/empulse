<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Socialite;
use Auth;

class SocialController extends Controller
{
    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function loginWithGoogle()
    {
        try {

            $user = Socialite::driver('google')->user();
            $isUser = User::where('google_id', $user->id)->first();
            $email = User::where('email', $user->email)->first();

            if ($isUser)
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
                // Create a basic account without elevating privileges; onboarding will attach company later
                $createUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'role' => 4,
                    'company' => null,
                    'password' => null,
                ]);

                Auth::login($createUser);

                return redirect('/home');
            }

        } catch (Exception $exception) {
            $session = \Session::put('google_auth_error', "Now you can't auth via google!");
            return response()->back()->with($session);
        }
    }

}
