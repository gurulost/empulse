@extends('layouts.app')

@section('title', 'Survey Waves')

@section('content')
    <div class="container py-4">
        <div class="page-header">
            <h1 class="page-title">Survey Waves</h1>
            <p class="page-subtitle">Schedule and dispatch survey waves to your team.</p>
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @php
            $isBillingActive = in_array($billingStatus, config('survey.automation.billing_statuses'));
            $billingBadge = $isBillingActive ? 'success' : 'warning';
        @endphp

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-credit-card text-muted"></i>
                        <span class="small"><strong>Plan:</strong> {{ $planLabel }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-activity text-muted"></i>
                        <span class="small"><strong>Billing:</strong></span>
                        <span class="badge rounded-pill px-2 py-1 text-bg-{{ $billingBadge }}">{{ $billingLabel }}</span>
                    </div>
                </div>
                <div class="small text-muted mt-2">
                    Automation requires a running queue worker and scheduler. Keep both processes online or the drip cadence will pause.
                </div>
            </div>
        </div>

        @php
            $activePageCount = $activeSurveyVersion?->pages?->count() ?? 0;
            $activeSectionCount = $activeSurveyVersion
                ? $activeSurveyVersion->pages->sum(fn ($page) => $page->sections->count())
                : 0;
            $activeItemCount = $activeSurveyVersion
                ? $activeSurveyVersion->pages->sum(fn ($page) => $page->items->count() + $page->sections->sum(fn ($section) => $section->items->count()))
                : 0;
        @endphp

        @unless($canUseDrip)
            <div class="alert alert-warning rounded-3 border-0 mb-4">
                <i class="bi bi-info-circle me-1"></i>
                Drip cadences are disabled on your current plan. <a href="/plans" class="alert-link fw-semibold">Upgrade to Pulse</a> to unlock weekly/monthly drips.
            </div>
        @endunless

        @if(!$hasCompanyContext)
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="empty-state py-2">
                        <div class="empty-state-icon"><i class="bi bi-building"></i></div>
                        <h5 class="empty-state-title">No company context yet</h5>
                        <p class="empty-state-text mb-3">Survey waves can only be created for a manager attached to a company.</p>
                        <a href="{{ route('team.manage') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">Open Team Management</a>
                    </div>
                </div>
            </div>
        @elseif(!$hasSurveySetup)
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="empty-state py-2">
                        <div class="empty-state-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <h5 class="empty-state-title">Import and publish a survey first</h5>
                        <p class="empty-state-text">Publish an active survey version before creating your first wave.</p>
                    </div>
                </div>
            </div>
        @else
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle text-primary"></i>
                        <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.0625rem;">Create Wave</h5>
                    </div>
                    <small class="text-muted">
                        v{{ $activeSurveyVersion?->version ?? '—' }} &middot;
                        {{ $activePageCount }} pages &middot;
                        {{ $activeItemCount }} items
                    </small>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('survey-waves.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Survey</label>
                                <select name="survey_id" class="form-select" required>
                                    @foreach($surveys as $survey)
                                        <option value="{{ $survey->id }}">{{ $survey->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Version</label>
                                <select name="survey_version_id" class="form-select" required>
                                    @foreach($versions as $version)
                                        <option value="{{ $version->id }}" @selected($activeSurveyVersion && $version->id === $activeSurveyVersion->id)>
                                            {{ $version->version }}{{ $version->is_active ? ' (live)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Kind</label>
                                <select name="kind" class="form-select" required>
                                    <option value="full">Full</option>
                                    <option value="drip" @disabled(!$canUseDrip)>Drip</option>
                                </select>
                                @unless($canUseDrip)
                                    <div class="form-text text-warning small">Requires Pulse plan</div>
                                @endunless
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="paused">Paused</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Cadence</label>
                                <select name="cadence" class="form-select" required>
                                    @foreach($cadenceOptions as $value => $label)
                                        <option value="{{ $value }}" @disabled(!$canUseDrip && $value !== 'manual')>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Label</label>
                                <input type="text" name="label" class="form-control" required placeholder="March Pulse">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Audience</label>
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="row g-2">
                                        @foreach($roleOptions as $roleValue => $roleLabel)
                                            <div class="col-sm-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="target_roles[]" id="target-role-{{ $roleValue }}" value="{{ $roleValue }}"
                                                        @checked(in_array((int) $roleValue, old('target_roles', $defaultTargetRoles), true))>
                                                    <label class="form-check-label small" for="target-role-{{ $roleValue }}">{{ $roleLabel }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Opens At</label>
                                <input type="datetime-local" name="opens_at" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Due At</label>
                                <input type="datetime-local" name="due_at" class="form-control">
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button class="btn btn-primary rounded-pill px-4 fw-semibold">
                                <i class="bi bi-plus-lg me-1"></i>Create Wave
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-list-ul text-primary"></i>
                    <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.0625rem;">Existing Waves</h5>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th class="ps-4">Label</th>
                            <th>Audience</th>
                            <th>Survey</th>
                            <th>Version</th>
                            <th>Kind</th>
                            <th>Status</th>
                            <th>Cadence</th>
                            <th>Progress</th>
                            <th>Invites</th>
                            <th>Opens</th>
                            <th>Due</th>
                            <th>Last Run</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($waves as $wave)
                            @php
                                $stats = $assignmentStats->get($wave->id);
                                $totalAssigned = $stats->total ?? 0;
                                $sentAssigned = $stats->dispatched ?? 0;
                                $completedAssigned = $stats->completed ?? 0;
                                $invitedAssigned = $stats->invited ?? 0;
                                $failedInvites = $stats->invite_failed ?? 0;
                                $canDispatchWave = $wave->status === 'scheduled';
                                $canToggleWaveStatus = in_array($wave->status, ['scheduled', 'paused'], true);
                            @endphp
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $wave->label }}</td>
                                <td>
                                    @foreach(($wave->target_roles ?? $defaultTargetRoles) as $roleValue)
                                        <span class="badge bg-light text-dark border rounded-pill px-2" style="font-size: 0.7rem;">{{ $roleOptions[(int) $roleValue] ?? "Role {$roleValue}" }}</span>
                                    @endforeach
                                </td>
                                <td class="text-muted">{{ $wave->survey->title ?? '—' }}</td>
                                <td class="text-muted">{{ $wave->surveyVersion->version ?? '—' }}</td>
                                <td>{{ ucfirst($wave->kind) }}</td>
                                <td>
                                    <span class="badge rounded-pill px-2 py-1 text-bg-{{ $wave->status === 'paused' ? 'warning' : ($wave->status === 'completed' ? 'success' : 'secondary') }}">
                                        {{ ucfirst($wave->status) }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $cadenceOptions[$wave->cadence] ?? ucfirst($wave->cadence) }}</td>
                                <td>
                                    <div class="small text-muted">Sent {{ $sentAssigned }}/{{ $totalAssigned }}</div>
                                    <div class="small text-muted">Done {{ $completedAssigned }}</div>
                                </td>
                                <td>
                                    <div class="small text-muted">{{ $invitedAssigned }} delivered</div>
                                    @if($failedInvites > 0)
                                        <div class="small text-danger">{{ $failedInvites }} failed</div>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ optional($wave->opens_at)->format('M d, H:i') ?? '—' }}</td>
                                <td class="text-muted small">{{ optional($wave->due_at)->format('M d, H:i') ?? '—' }}</td>
                                <td class="text-muted small">{{ optional($wave->last_dispatched_at)->format('M d, H:i') ?? '—' }}</td>
                                <td class="text-end pe-4">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button
                                            class="btn btn-sm btn-outline-dark rounded-pill px-2"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#wave-edit-{{ $wave->id }}"
                                            aria-expanded="false"
                                            aria-controls="wave-edit-{{ $wave->id }}"
                                            title="Edit wave"
                                            @disabled($wave->status === 'processing')
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('survey-waves.dispatch', $wave) }}">
                                            @csrf
                                            <button
                                                class="btn btn-sm btn-outline-primary rounded-pill px-2"
                                                type="submit"
                                                title="{{ $canDispatchWave ? 'Run now' : ($wave->status === 'paused' ? 'Resume before dispatching' : ($wave->status === 'processing' ? 'Wave is already processing' : 'Completed waves cannot be re-run')) }}"
                                                @disabled(!$canDispatchWave)
                                            >
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('survey-waves.status', $wave) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $wave->status === 'paused' ? 'scheduled' : 'paused' }}">
                                            <button
                                                class="btn btn-sm btn-outline-secondary rounded-pill px-2"
                                                type="submit"
                                                title="{{ $canToggleWaveStatus ? ($wave->status === 'paused' ? 'Resume' : 'Pause') : ($wave->status === 'processing' ? 'Wait for processing to finish' : 'Completed waves cannot be paused') }}"
                                                @disabled(!$canToggleWaveStatus)
                                            >
                                                <i class="bi bi-{{ $wave->status === 'paused' ? 'play' : 'pause' }}-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="13" class="ps-4 pe-4 py-2" style="background: #fafbfc; border-bottom: 2px solid #f1f5f9;">
                                    <details>
                                        <summary class="small fw-semibold text-muted cursor-pointer">
                                            <i class="bi bi-clock-history me-1"></i>Recent activity ({{ ($logsByWave[$wave->id] ?? collect())->count() }} events)
                                        </summary>
                                        <ul class="list-unstyled mb-0 mt-2 ms-3">
                                            @forelse(($logsByWave[$wave->id] ?? collect())->take(10) as $log)
                                                <li class="d-flex justify-content-between align-items-center small py-1">
                                                    <span>
                                                        <span class="text-muted">[{{ $log->created_at->format('M d H:i') }}]</span>
                                                        <span class="badge rounded-pill px-2 py-1 text-bg-{{ $log->status === 'failed' ? 'danger' : ($log->status === 'skipped' ? 'warning' : ($log->status === 'paused' ? 'secondary' : 'success')) }}">
                                                            {{ ucfirst($log->status) }}
                                                        </span>
                                                        {{ $log->message ?? '' }}
                                                    </span>
                                                    <span class="text-muted">{{ optional($log->user)->email ?? 'System' }}</span>
                                                </li>
                                            @empty
                                                <li class="text-muted small py-1">No activity yet.</li>
                                            @endforelse
                                        </ul>
                                    </details>
                                </td>
                            </tr>
                            <tr class="collapse" id="wave-edit-{{ $wave->id }}">
                                <td colspan="13" class="ps-4 pe-4 py-3 bg-white border-bottom">
                                    @if($wave->status === 'processing')
                                        <div class="alert alert-warning mb-0">
                                            This wave is processing right now. Wait for the dispatch to finish before editing it.
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('survey-waves.update', $wave) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold small">Label</label>
                                                    <input type="text" name="label" class="form-control" required value="{{ $wave->label }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fw-semibold small">Status</label>
                                                    @if($wave->status === 'completed')
                                                        <input type="hidden" name="status" value="completed">
                                                        <input type="text" class="form-control" value="Completed" disabled>
                                                    @else
                                                        <select name="status" class="form-select" required>
                                                            <option value="scheduled" @selected($wave->status === 'scheduled')>Scheduled</option>
                                                            <option value="paused" @selected($wave->status === 'paused')>Paused</option>
                                                        </select>
                                                    @endif
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fw-semibold small">Cadence</label>
                                                    <select name="cadence" class="form-select" required>
                                                        @foreach($cadenceOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected($wave->cadence === $value) @disabled((!$canUseDrip && $value !== 'manual') || ($wave->kind === 'full' && $value !== 'manual'))>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fw-semibold small">Opens At</label>
                                                    <input type="datetime-local" name="opens_at" class="form-control" value="{{ optional($wave->opens_at)->format('Y-m-d\\TH:i') }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fw-semibold small">Due At</label>
                                                    <input type="datetime-local" name="due_at" class="form-control" value="{{ optional($wave->due_at)->format('Y-m-d\\TH:i') }}">
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label fw-semibold small">Audience</label>
                                                    <div class="border rounded-3 p-3 bg-light">
                                                        <div class="row g-2">
                                                            @foreach($roleOptions as $roleValue => $roleLabel)
                                                                <div class="col-sm-3">
                                                                    <div class="form-check">
                                                                        <input
                                                                            class="form-check-input"
                                                                            type="checkbox"
                                                                            name="target_roles[]"
                                                                            id="wave-{{ $wave->id }}-role-{{ $roleValue }}"
                                                                            value="{{ $roleValue }}"
                                                                            @checked(in_array((int) $roleValue, $wave->target_roles ?? $defaultTargetRoles, true))
                                                                        >
                                                                        <label class="form-check-label small" for="wave-{{ $wave->id }}-role-{{ $roleValue }}">{{ $roleLabel }}</label>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 d-flex justify-content-end gap-2">
                                                <button
                                                    class="btn btn-outline-secondary rounded-pill px-4"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#wave-edit-{{ $wave->id }}"
                                                >
                                                    Cancel
                                                </button>
                                                <button class="btn btn-primary rounded-pill px-4 fw-semibold" type="submit">
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4 text-muted">
                                    <i class="bi bi-calendar2-x d-block mb-2" style="font-size: 1.5rem; opacity: 0.4;"></i>
                                    No waves yet. Create one above to get started.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($waves->hasPages())
                <div class="card-footer bg-white border-top py-3 px-4">
                    {{ $waves->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
