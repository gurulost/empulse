<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function welcome() 
    {
        if (!auth()->check()) {
            return view('welcome');
        }

        if ((int) auth()->user()->role === 4) {
            return redirect()->route('employee.dashboard');
        }

        return redirect()->route('home');
    }
}
