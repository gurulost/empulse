<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ChiefController extends Controller
{
    public function chief($email)
    {
        $data = DB::table('users')
            ->select('name')
            ->where('email', $email)
            ->get();

        return view('roles.chiefPanel', compact('data'));
    }
}
