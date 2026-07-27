<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CompanyBilling;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Payment
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        // Only company managers without a dispatch-eligible entitlement use
        // this legacy payment entry point.
        if (! $user instanceof User
            || (int) $user->role !== 1
            || CompanyBilling::allowsScheduling($user)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
