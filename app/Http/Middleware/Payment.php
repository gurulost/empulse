<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Payment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only unpaid company owners should access payment pages
        if ((int)\Auth::user()->tariff === 1 || (int)\Auth::user()->company !== 1) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
