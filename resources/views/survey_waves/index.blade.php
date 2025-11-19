@extends('layouts.app')

@section('title', 'Survey Waves')

@section('content')
    <div class="container mt-4">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @php
            $isBillingActive = in_array($billingStatus, config('survey.automation.billing_statuses'));
            $billingBadge = $isBillingActive ? 'success' : 'warning';
        @endphp
        <div class="alert alert-info">
            <div><strong>Current plan:</strong> {{ $planLabel }} · <strong>Billing:</strong> <span class="badge text-bg-{{ $billingBadge }}">{{ $billingLabel }}</span></div>
            <div class="small mt-2">
                Automation requires a running queue worker <code>php artisan queue:work --tries=1</code> and scheduler <code>* * * * * php artisan schedule:run >> storage/logs/schedule.log 2>&1</code>. Keep both processes online or the drip cadence will pause.
            </div>
        </div>
        @unless($canUseDrip)
            <div class="alert alert-warning">
                Drip cadences are disabled on your current plan. <a href="/plans" class="alert-link">Upgrade</a> to Pulse to unlock weekly/monthly drips.
            </div>
        @endunless
        <div class="card mb-4">
            <div class="card-header">Create Wave</div>
            <div class="card-body">
                <form method="POST" action="{{ route('survey-waves.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Survey</label>
                            <select name="survey_id" class="form-select" required>
                                @foreach($surveys as $survey)
                                    <option value="{{ $survey->id }}">{{ $survey->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Version</label>
                            <select name="survey_version_id" class="form-select" required>
                                @foreach($versions as $version)
                                    <option value="{{ $version->id }}">{{ $version->version }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kind</label>
                            <select name="kind" class="form-select" required>
                                <option value="full">Full</option>
                                <option value="drip" @disabled(!$canUseDrip)>Drip</option>
                            </select>
                            @unless($canUseDrip)
                                <div class="form-text text-warning">Drip waves require the Pulse plan.</div>
                            @endunless
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cadence</label>
                            <select name="cadence" class="form-select" required>
                                @foreach($cadenceOptions as $value => $label)
                                    <option value="{{ $value }}" @disabled(!$canUseDrip && $value !== 'manual')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Label</label>
                            <input type="text" name="label" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Opens At</label>
                            <input type="datetime-local" name="opens_at" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due At</label>
                            <input type="datetime-local" name="due_at" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-primary">Create Wave</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Existing Waves</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th>Label</th>
                        <th>Survey</th>
                        <th>Version</th>
                        <th>Kind</th>
                        <th>Status</th>
                        <th>Cadence</th>
                        <th>Progress</th>
                        <th>Opens</th>
                        <th>Due</th>
                        <th>Last Run</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($waves as $wave)
                        @php
                            $stats = $assignmentStats->get($wave->id);
                            $totalAssigned = $stats->total ?? 0;
                            $sentAssigned = $stats->dispatched ?? 0;
                            $completedAssigned = $stats->completed ?? 0;
                        @endphp
                        <tr>
                            <td>{{ $wave->label }}</td>
                            <td>{{ $wave->survey->title ?? '—' }}</td>
                            <td>{{ $wave->surveyVersion->version ?? '—' }}</td>
                            <td>{{ ucfirst($wave->kind) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $wave->status === 'paused' ? 'warning' : ($wave->status === 'completed' ? 'success' : 'secondary') }}">
                                    {{ ucfirst($wave->status) }}
                                </span>
                            </td>
                            <td>{{ $cadenceOptions[$wave->cadence] ?? ucfirst($wave->cadence) }}</td>
                            <td>
                                <div class="small text-muted">Sent {{ $sentAssigned }}/{{ $totalAssigned }}</div>
                                <div class="small text-muted">Completed {{ $completedAssigned }}</div>
                            </td>
                            <td>{{ optional($wave->opens_at)->toDayDateTimeString() ?? '—' }}</td>
                            <td>{{ optional($wave->due_at)->toDayDateTimeString() ?? '—' }}</td>
                            <td>{{ optional($wave->last_dispatched_at)->toDayDateTimeString() ?? '—' }}</td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <form method="POST" action="{{ route('survey-waves.dispatch', $wave) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Run now</button>
                                    </form>
                                    <form method="POST" action="{{ route('survey-waves.status', $wave) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $wave->status === 'paused' ? 'scheduled' : 'paused' }}">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $wave->status === 'paused' ? 'Resume' : 'Pause' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="11" class="bg-light">
                                <details>
                                    <summary class="small">Recent activity ({{ ($logsByWave[$wave->id] ?? collect())->count() }} events)</summary>
                                    <ul class="list-unstyled mb-0 mt-2">
                                        @forelse(($logsByWave[$wave->id] ?? collect())->take(10) as $log)
                                            <li class="d-flex justify-content-between align-items-center small">
                                                <span>
                                                    [{{ $log->created_at->format('M d H:i') }}]
                                                    <span class="badge text-bg-{{ $log->status === 'failed' ? 'danger' : ($log->status === 'skipped' ? 'warning' : ($log->status === 'paused' ? 'secondary' : 'success')) }}">
                                                        {{ ucfirst($log->status) }}
                                                    </span>
                                                    {{ $log->message ?? '' }}
                                                </span>
                                                <span class="text-muted">{{ optional($log->user)->email ?? 'System' }}</span>
                                            </li>
                                        @empty
                                            <li class="text-muted">No activity yet.</li>
                                        @endforelse
                                    </ul>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-3">No waves yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $waves->links() }}
            </div>
        </div>
    </div>
@endsection
