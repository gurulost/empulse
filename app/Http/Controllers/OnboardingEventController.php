<?php

namespace App\Http\Controllers;

use App\Services\OnboardingTelemetryService;
use Illuminate\Http\Request;

class OnboardingEventController extends Controller
{
    public function __construct(protected OnboardingTelemetryService $telemetry)
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'context_surface' => 'required|string|max:80',
            'task_id' => 'nullable|string|max:80',
            'user_segment' => 'nullable|string|max:24',
            'guidance_level' => 'nullable|string|max:24',
            'session_id' => 'nullable|string|max:120',
            'attempt_index' => 'nullable|integer|min:1|max:50',
            'time_since_session_start_sec' => 'nullable|integer|min:0|max:86400',
            'company_id' => 'nullable|integer|exists:companies,id',
            'properties' => 'nullable|array',
        ]);

        $companyId = (int) ($validated['company_id'] ?? $user->company_id ?? 0);
        $isWorkfitAdmin = (int) ($user->is_admin ?? 0) === 1 || (int) ($user->role ?? 0) === 0;

        if (!$isWorkfitAdmin && $companyId > 0 && (int) $user->company_id !== $companyId) {
            abort(403, 'Forbidden');
        }

        if (!$companyId && !$isWorkfitAdmin) {
            abort(422, 'Company context required.');
        }

        $event = $this->telemetry->record(array_merge($validated, [
            'company_id' => $companyId ?: null,
        ]), $user);

        return response()->json([
            'status' => 'ok',
            'id' => $event->id,
        ], 201);
    }
}
