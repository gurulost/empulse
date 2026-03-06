<?php

namespace App\Http\Controllers;

use App\Models\SurveyAssignment;
use App\Services\SurveyDefinitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDashboardController extends Controller
{
    public function __construct(protected SurveyDefinitionService $definitionService)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || (int) $user->role !== 4) {
            return redirect()->route('home');
        }

        $currentAssignment = SurveyAssignment::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->orderByDesc('id')
            ->with(['response', 'surveyWave'])
            ->first();

        $assignmentHistory = SurveyAssignment::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->with(['response', 'surveyWave'])
            ->limit(6)
            ->get();

        $surveyMeta = $currentAssignment
            ? $this->definitionService->surveyMetaForAssignment($currentAssignment)
            : null;

        return view('employee.dashboard', [
            'assignment' => $currentAssignment,
            'assignmentHistory' => $assignmentHistory,
            'surveyMeta' => $surveyMeta,
        ]);
    }
}
