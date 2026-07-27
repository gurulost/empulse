<?php

namespace App\Http\Controllers;

use App\Services\SocialAuthService;
use Laravel\Socialite\Facades\Socialite;

class FacebookController extends Controller
{
    protected SocialAuthService $socialAuth;

    public function __construct(SocialAuthService $socialAuth)
    {
        $this->socialAuth = $socialAuth;
    }

    public function facebookRedirect()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function facebookLogin()
    {
        try {
            $fbUser = Socialite::driver('facebook')->user();

            $result = $this->socialAuth->handleFacebookLogin(
                $fbUser->id,
                $fbUser->email,
                $fbUser->name
            );

            if (! $result['success']) {
                \Session::put('facebook_auth_error', $result['error']);

                return redirect()->route('login')->withErrors($result['error']);
            }

            return redirect('/home');

        } catch (\Exception $e) {
            \Session::put('facebook_auth_error', 'Facebook authentication is currently unavailable.');

            return redirect()->route('login')->withErrors('Facebook authentication is currently unavailable.');
        }
    }
}
