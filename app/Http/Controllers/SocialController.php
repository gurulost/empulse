<?php

namespace App\Http\Controllers;

use App\Services\SocialAuthService;
use Exception;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    protected SocialAuthService $socialAuth;

    public function __construct(SocialAuthService $socialAuth)
    {
        $this->socialAuth = $socialAuth;
    }

    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function loginWithGoogle()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $result = $this->socialAuth->handleGoogleLogin(
                $googleUser->id,
                $googleUser->email,
                $googleUser->name
            );

            if (! $result['success']) {
                \Session::put('google_auth_error', $result['error']);

                return redirect()->route('login')->withErrors($result['error']);
            }

            return redirect('/home');

        } catch (Exception $exception) {
            \Session::put('google_auth_error', 'Google authentication is currently unavailable.');

            return redirect()->route('login')->withErrors('Google authentication is currently unavailable.');
        }
    }
}
