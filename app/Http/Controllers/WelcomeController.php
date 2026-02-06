<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function welcome(Request $request)
    {
        $userAgent = strtolower((string) $request->userAgent());
        if (str_contains($userAgent, 'go-http-client') || str_contains($userAgent, 'kube-probe')) {
            return response('OK', 200);
        }

        if (!auth()->check()) {
            return view('welcome');
        }

        if ((int) auth()->user()->role === 4) {
            return redirect()->route('employee.dashboard');
        }

        return redirect()->route('home');
    }
}
