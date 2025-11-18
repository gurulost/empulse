<?php

namespace App\Http\Controllers;

use App\Models\SurveyWave;
use App\Models\Survey;
use App\Models\SurveyVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyWaveController extends Controller
{
    public function index()
    {
        $this->authorizeAccess();
        $waves = SurveyWave::with(['survey', 'surveyVersion'])
            ->where('company_id', Auth::user()->company_id)
            ->orderByDesc('opens_at')
            ->paginate(15);

        $surveys = Survey::all();
        $versions = SurveyVersion::orderByDesc('id')->get();
        $statusOptions = [
            'scheduled' => 'Scheduled',
            'processing' => 'Processing',
            'paused' => 'Paused',
        ];
        $cadenceOptions = [
            'manual' => 'Manual (one-time)',
            'weekly' => 'Weekly Drip',
            'monthly' => 'Monthly Drip',
            'quarterly' => 'Quarterly Drip',
        ];

        return view('survey_waves.index', compact('waves', 'surveys', 'versions', 'statusOptions', 'cadenceOptions'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'survey_version_id' => 'required|exists:survey_versions,id',
            'kind' => 'required|in:full,drip',
            'label' => 'required|string|max:255',
            'status' => 'required|in:scheduled,paused',
            'cadence' => 'required|in:manual,weekly,monthly,quarterly',
            'opens_at' => 'nullable|date',
            'due_at' => 'nullable|date|after_or_equal:opens_at',
        ]);

        SurveyWave::create(array_merge($data, [
            'company_id' => Auth::user()->company_id,
        ]));

        return redirect()->route('survey-waves.index')->with('status', 'Wave created.');
    }

    protected function authorizeAccess(): void
    {
        if (!Auth::check() || Auth::user()->role !== 1) {
            abort(403);
        }
    }
}
