<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            $user = Auth::guard($guard)->user();
            if (! $user) {
                continue;
            }

            if ((int) $user->role === 4) {
                return redirect()->route('employee.dashboard');
            }

            return redirect(RouteServiceProvider::HOME);
        }

        return $next($request);
    }
}
