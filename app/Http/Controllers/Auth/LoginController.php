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

    use AuthenticatesUsers;

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

    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        try {
            return $this->limiter()->tooManyAttempts(
                $this->throttleKey($request),
                $this->maxAttempts()
            );
        } catch (\Throwable $e) {
            Log::warning('Login throttle check failed (cache unavailable) — degrading safely.', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function incrementLoginAttempts(Request $request): void
    {
        try {
            $this->limiter()->hit(
                $this->throttleKey($request),
                $this->decayMinutes() * 60
            );
        } catch (\Throwable $e) {
            Log::warning('Login attempt increment failed (cache unavailable) — skipping.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function clearLoginAttempts(Request $request): void
    {
        try {
            $this->limiter()->clear($this->throttleKey($request));
        } catch (\Throwable $e) {
            Log::warning('Login attempt clear failed (cache unavailable) — skipping.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
