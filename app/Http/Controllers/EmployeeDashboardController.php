<?php

namespace App\Http\Controllers;

use App\Models\SurveyAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || (int) $user->role !== 4) {
            return redirect()->route('home');
        }

        $assignment = SurveyAssignment::query()
            ->where('user_id', $user->id)
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderByDesc('id')
            ->with('response')
            ->first();

        return view('employee.dashboard', [
            'assignment' => $assignment,
        ]);
    }
}

