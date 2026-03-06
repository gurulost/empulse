<?php

namespace App\Http\Controllers;

use App\Models\SurveyAssignment;
use App\Models\SurveyWave;
use App\Models\SurveyWaveLog;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Jobs\ProcessSurveyWave;
use App\Support\CompanyBilling;
use App\Support\SurveyWaveAutomation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SurveyWaveController extends Controller
{
    public function index()
    {
        $this->authorizeAccess();
        $user = Auth::user();
        $hasCompanyContext = (int) ($user->company_id ?? 0) > 0;
        $activeSurveyVersion = SurveyVersion::query()
            ->with('pages.sections.items', 'pages.items')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        $surveys = Survey::query()
            ->where(function ($query) use ($user, $hasCompanyContext) {
                $query->where('is_default', true);

                if ($hasCompanyContext) {
                    $query->orWhere('company_id', $user->company_id);
                }
            })
            ->orderByDesc('is_default')
            ->orderBy('title')
            ->get();
        $versions = $activeSurveyVersion ? collect([$activeSurveyVersion]) : collect();
        $defaultTargetRoles = config('billing.default_wave_roles', [1, 2, 3, 4]);
        $roleOptions = config('billing.role_labels', []);
        $hasSurveySetup = $surveys->isNotEmpty() && $activeSurveyVersion !== null;

        if ($hasCompanyContext) {
            $waves = SurveyWave::with(['survey', 'surveyVersion'])
                ->withCount([
                    'assignments as total_assignments_count',
                    'assignments as dispatched_assignments_count' => function ($query) {
                        $query->whereNotNull('last_dispatched_at');
                    },
                    'assignments as completed_assignments_count' => function ($query) {
                        $query->where('status', 'completed');
                    },
                ])
                ->where('company_id', $user->company_id)
                ->orderByDesc('opens_at')
                ->paginate(15);
        } else {
            $waves = new LengthAwarePaginator([], 0, 15);
        }

        $statusOptions = [
            'scheduled' => 'Scheduled',
            'processing' => 'Processing',
            'paused' => 'Paused',
            'completed' => 'Completed',
        ];
        $cadenceOptions = [
            'manual' => 'Manual (one-time)',
            'weekly' => 'Weekly Drip',
            'monthly' => 'Monthly Drip',
            'quarterly' => 'Quarterly Drip',
        ];

        $waveIds = collect($waves->items())->pluck('id');
        $logsByWave = SurveyWaveLog::whereIn('survey_wave_id', $waveIds)
            ->latest()
            ->get()
            ->groupBy('survey_wave_id');

        $assignmentStats = $waveIds->isNotEmpty()
            ? SurveyAssignment::select('survey_wave_id')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN last_dispatched_at IS NOT NULL THEN 1 ELSE 0 END) as dispatched")
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
                ->selectRaw("SUM(CASE WHEN invite_status = 'sent' THEN 1 ELSE 0 END) as invited")
                ->selectRaw("SUM(CASE WHEN invite_status = 'failed' THEN 1 ELSE 0 END) as invite_failed")
                ->whereIn('survey_wave_id', $waveIds)
                ->groupBy('survey_wave_id')
                ->get()
                ->keyBy('survey_wave_id')
            : collect();

        $billingStatus = CompanyBilling::status($user);
        $billingLabel = SurveyWaveAutomation::billingStatusLabel($billingStatus);
        $planLabel = SurveyWaveAutomation::planLabel((int) $user->tariff);
        $canUseDrip = SurveyWaveAutomation::dripEnabledForTariff((int) $user->tariff);

        return view('survey_waves.index', compact(
            'waves',
            'surveys',
            'versions',
            'statusOptions',
            'cadenceOptions',
            'logsByWave',
            'assignmentStats',
            'billingStatus',
            'billingLabel',
            'planLabel',
            'canUseDrip',
            'hasCompanyContext',
            'hasSurveySetup',
            'roleOptions',
            'defaultTargetRoles',
            'activeSurveyVersion'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $companyId = (int) (Auth::user()->company_id ?? 0);
        if (!$companyId) {
            return back()->withErrors('Assign this manager to a company before creating survey waves.');
        }

        $data = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'survey_version_id' => 'required|exists:survey_versions,id',
            'kind' => 'required|in:full,drip',
            'label' => 'required|string|max:255',
            'target_roles' => 'required|array|min:1',
            'target_roles.*' => 'integer|in:1,2,3,4',
            'status' => 'required|in:scheduled,paused',
            'cadence' => 'required|in:manual,weekly,monthly,quarterly',
            'opens_at' => 'nullable|date',
            'due_at' => 'nullable|date|after_or_equal:opens_at',
        ]);

        $survey = Survey::query()
            ->whereKey($data['survey_id'])
            ->where(function ($query) use ($companyId) {
                $query->where('is_default', true)
                    ->orWhere('company_id', $companyId);
            })
            ->first();

        $activeVersion = SurveyVersion::query()
            ->whereKey($data['survey_version_id'])
            ->where('is_active', true)
            ->first();

        if (!$survey || !$activeVersion) {
            return back()->withErrors('Import and publish a survey before creating waves.');
        }

        if ($data['kind'] === 'full' && $data['cadence'] !== 'manual') {
            throw ValidationException::withMessages([
                'cadence' => 'Full waves must use the manual cadence.',
            ]);
        }

        $this->guardDripAccess($data['kind'], $data['cadence']);

        SurveyWave::create(array_merge($data, [
            'company_id' => $companyId,
            'survey_id' => $survey->id,
            'survey_version_id' => $activeVersion->id,
            'target_roles' => collect($data['target_roles'])
                ->map(fn ($role) => (int) $role)
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ]));

        return redirect()->route('survey-waves.index')->with('status', 'Wave created.');
    }

    public function updateStatus(Request $request, SurveyWave $wave)
    {
        $this->authorizeWave($wave);

        $validated = $request->validate([
            'status' => 'required|in:scheduled,paused',
        ]);

        $wave->update(['status' => $validated['status']]);

        SurveyWaveLog::create([
            'survey_wave_id' => $wave->id,
            'status' => $validated['status'],
            'message' => 'Status updated manually.',
        ]);

        return back()->with('status', 'Wave status updated.');
    }

    public function dispatchWave(SurveyWave $wave)
    {
        $this->authorizeWave($wave);

        $manager = CompanyBilling::manager($wave->company_id);
        if (!CompanyBilling::allowsScheduling($manager)) {
            return back()->withErrors('Billing inactive. Update your subscription to dispatch waves.');
        }

        if ($wave->kind === 'drip') {
            $this->guardDripAccess($wave->kind, $wave->cadence);
        }

        if ($wave->status === 'paused') {
            return back()->withErrors('Wave is paused. Resume it before dispatching.');
        }

        ProcessSurveyWave::dispatch($wave->id);
        $wave->update(['status' => 'processing']);

        SurveyWaveLog::create([
            'survey_wave_id' => $wave->id,
            'status' => 'processing',
            'message' => 'Manual dispatch requested.',
        ]);

        return back()->with('status', 'Wave dispatched.');
    }

    protected function authorizeAccess(): void
    {
        if (!Auth::check() || (int) Auth::user()->role !== 1) {
            abort(403);
        }
    }

    protected function authorizeWave(SurveyWave $wave): void
    {
        $this->authorizeAccess();
        if ($wave->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }

    protected function guardDripAccess(string $kind, string $cadence): void
    {
        if ($kind !== 'drip' && $cadence === 'manual') {
            return;
        }

        $user = Auth::user();

        if (!SurveyWaveAutomation::dripEnabledForTariff((int) $user->tariff)) {
            throw ValidationException::withMessages([
                'cadence' => 'Upgrade your subscription to enable drip cadences.',
            ]);
        }

        if (!CompanyBilling::allowsScheduling($user)) {
            throw ValidationException::withMessages([
                'cadence' => 'Drip scheduling requires an active subscription.',
            ]);
        }
    }
}
