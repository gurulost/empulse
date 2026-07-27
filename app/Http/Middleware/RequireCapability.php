<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCapability
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        if (! $user->hasCapability($capability)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            if ($user->hasCapability('employee.dashboard')) {
                return redirect()->route('employee.dashboard');
            }

            abort(403);
        }

        return $next($request);
    }
}
