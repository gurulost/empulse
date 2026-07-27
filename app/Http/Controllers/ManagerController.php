<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ManagerController extends Controller
{
    public function manager($email)
    {
        $data = DB::table('users')
            ->select('name')
            ->where('email', $email)
            ->get();

        return view('roles.managerPanel', compact('data'));
    }
}
