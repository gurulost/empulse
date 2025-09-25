<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Admin
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
        try {
            if (\Auth::check()) {
                if(\Auth::user()->role !== 4) {
                    return $next($request);
                } else {
                    \Auth::logout();
                }
            }

            return redirect()->route('login');
        } catch(\Exception $e) {
            return redirect()->route('login');
        }
    }
}
