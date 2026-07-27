<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TeamleadController extends Controller
{
    public function teamlead($email)
    {
        $data = DB::table('users')
            ->select('name')
            ->where('email', $email)
            ->get();

        return view('roles.teamleadPanel', compact('data'));
    }
}
