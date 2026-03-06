<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers {
        hasTooManyLoginAttempts as protected traitHasTooManyLoginAttempts;
        incrementLoginAttempts as protected traitIncrementLoginAttempts;
        clearLoginAttempts as protected traitClearLoginAttempts;
    }

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
    }


    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            // 'g-recaptcha-response' => ['required', 'captcha'],
        ]);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [__('auth.failed')],
        ]);
    }

    protected function redirectTo(): string
    {
        $user = Auth::user();
        if ($user && (int) $user->role === 4) {
            return '/employee';
        }

        return RouteServiceProvider::HOME;
    }

    protected function hasTooManyLoginAttempts(Request $request)
    {
        try {
            return $this->traitHasTooManyLoginAttempts($request);
        } catch (\Throwable $exception) {
            Log::warning('Login rate limiter unavailable during lockout check.', [
                'email' => $request->input($this->username()),
                'ip' => $request->ip(),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function incrementLoginAttempts(Request $request)
    {
        try {
            $this->traitIncrementLoginAttempts($request);
        } catch (\Throwable $exception) {
            Log::warning('Login rate limiter unavailable while recording failed attempt.', [
                'email' => $request->input($this->username()),
                'ip' => $request->ip(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function clearLoginAttempts(Request $request)
    {
        try {
            $this->traitClearLoginAttempts($request);
        } catch (\Throwable $exception) {
            Log::warning('Login rate limiter unavailable while clearing attempts.', [
                'email' => $request->input($this->username()),
                'ip' => $request->ip(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
