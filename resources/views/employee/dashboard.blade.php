@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Employee Dashboard</h1>
                <p class="text-muted mb-0">Your current survey, progress, and recent assignment history.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        @if(!$assignment)
                            <h5 class="mb-2">No survey is assigned right now</h5>
                            <p class="text-muted mb-3">
                                Your next survey link will appear here as soon as your manager launches a wave for your team.
                            </p>
                            <div class="small text-muted">
                                If you expected a survey already, contact your manager or support team.
                            </div>
                        @else
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <div class="text-muted small text-uppercase">Current Assignment</div>
                                    <h5 class="mb-1">{{ $assignment->wave_label ?? 'Active survey' }}</h5>
                                    <div class="small text-muted">
                                        Status: {{ ucfirst($assignment->status ?? 'pending') }}
                                        @if($assignment->invite_status === 'sent' && $assignment->invited_at)
                                            · Invited {{ $assignment->invited_at->format('M d, Y H:i') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="badge {{ $assignment->draft_answers ? 'bg-warning text-dark' : 'bg-primary' }}">
                                    {{ $assignment->draft_answers ? 'Draft in progress' : 'Ready to respond' }}
                                </span>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="small text-muted text-uppercase">Due Date</div>
                                        <div class="fw-semibold">{{ optional($assignment->due_at)->format('M d, Y') ?? 'No due date set' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="small text-muted text-uppercase">Resume Point</div>
                                        <div class="fw-semibold">
                                            @if($assignment->draft_answers)
                                                Draft saved
                                            @else
                                                Not started
                                            @endif
                                        </div>
                                        @if($assignment->last_autosaved_at)
                                            <div class="small text-muted">Last saved {{ $assignment->last_autosaved_at->format('M d, Y H:i') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="small text-muted text-uppercase">Wave</div>
                                        <div class="fw-semibold">{{ $assignment->surveyWave?->label ?? $assignment->wave_label ?? 'Current survey' }}</div>
                                    </div>
                                </div>
                            </div>

                            <a
                                class="btn btn-primary"
                                href="{{ route('survey.take', $assignment->token) }}"
                                target="_blank"
                                rel="noreferrer"
                            >
                                {{ $assignment->draft_answers ? 'Resume Survey' : 'Open Survey' }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent History</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Wave</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignmentHistory as $historyAssignment)
                                        <tr>
                                            <td>{{ $historyAssignment->wave_label ?? 'Survey assignment' }}</td>
                                            <td>
                                                @if($historyAssignment->status === 'completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @elseif($historyAssignment->draft_answers)
                                                    <span class="badge bg-warning text-dark">Draft</span>
                                                @else
                                                    <span class="badge bg-secondary">Pending</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($historyAssignment->response)->submitted_at?->format('M d, Y') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">No assignment history yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
