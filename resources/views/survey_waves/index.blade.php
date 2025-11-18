@extends('layouts.app')

@section('title', 'Survey Waves')

@section('content')
    <div class="container mt-4">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
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
                                <option value="drip">Drip</option>
                            </select>
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
                                    <option value="{{ $value }}">{{ $label }}</option>
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
                        <th>Opens</th>
                        <th>Due</th>
                        <th>Last Run</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($waves as $wave)
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
                            <td>{{ optional($wave->opens_at)->toDayDateTimeString() ?? '—' }}</td>
                            <td>{{ optional($wave->due_at)->toDayDateTimeString() ?? '—' }}</td>
                            <td>{{ optional($wave->last_dispatched_at)->toDayDateTimeString() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No waves yet.</td>
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
