<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function welcome() 
    {
        if (auth()->user() == null){
            return view('welcome');
        }
        return redirect('/home');
    }
}
