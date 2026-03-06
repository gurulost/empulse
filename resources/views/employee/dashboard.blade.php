@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
    <div class="container py-4">
        <div class="page-header">
            <h1 class="page-title">Employee Dashboard</h1>
            <p class="page-subtitle">Your current survey, progress, and recent assignment history.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body p-4">
                        @if(!$assignment)
                            <div class="empty-state py-4">
                                <div class="empty-state-icon">
                                    <i class="bi bi-clipboard2-check"></i>
                                </div>
                                <h5 class="empty-state-title">No survey is assigned right now</h5>
                                <p class="empty-state-text">
                                    Your next survey link will appear here as soon as your manager launches a wave for your team.
                                </p>
                                <p class="small text-muted mt-3">
                                    If you expected a survey already, contact your manager or support team.
                                </p>
                            </div>
                        @else
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                <div>
                                    <div class="stat-label" style="margin-bottom: 0.25rem;">Current Assignment</div>
                                    <h4 class="mb-1 fw-bold" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">{{ $assignment->wave_label ?? 'Active survey' }}</h4>
                                    <div class="small text-muted">
                                        Status: {{ ucfirst($assignment->status ?? 'pending') }}
                                        @if($assignment->invite_status === 'sent' && $assignment->invited_at)
                                            &middot; Invited {{ $assignment->invited_at->format('M d, Y H:i') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="badge rounded-pill px-3 py-2 {{ $assignment->draft_answers ? 'bg-warning text-dark' : 'bg-primary' }}">
                                    {{ $assignment->draft_answers ? 'Draft in progress' : 'Ready to respond' }}
                                </span>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="stat-card h-100">
                                        <div class="stat-label">Due Date</div>
                                        <div class="stat-value" style="font-size: 1.125rem;">{{ optional($assignment->due_at)->format('M d, Y') ?? 'No due date set' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card h-100">
                                        <div class="stat-label">Resume Point</div>
                                        <div class="stat-value" style="font-size: 1.125rem;">
                                            @if($assignment->draft_answers)
                                                Draft saved
                                            @else
                                                Not started
                                            @endif
                                        </div>
                                        @if($assignment->last_autosaved_at)
                                            <div class="stat-detail">Last saved {{ $assignment->last_autosaved_at->format('M d, Y H:i') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card h-100">
                                        <div class="stat-label">Wave</div>
                                        <div class="stat-value" style="font-size: 1.125rem;">{{ $assignment->surveyWave?->label ?? $assignment->wave_label ?? 'Current survey' }}</div>
                                    </div>
                                </div>
                            </div>

                            <a class="btn btn-primary rounded-pill px-4 fw-semibold"
                                id="employee-survey-launch-cta"
                                href="{{ route('survey.take', $assignment->token) }}"
                                target="_blank"
                                rel="noreferrer"
                                data-estimated-minutes="{{ (int) ($surveyMeta['estimated_minutes'] ?? 4) }}">
                                <i class="bi bi-{{ $assignment->draft_answers ? 'pencil-square' : 'play-fill' }} me-2"></i>
                                {{ $assignment->draft_answers ? 'Resume Survey' : 'Open Survey' }}
                            </a>

                            <div class="border rounded-4 bg-light-subtle p-3 mt-4">
                                <div class="small text-uppercase fw-semibold text-secondary mb-2">Before you start</div>
                                <div class="small text-muted mb-2">
                                    Your responses stay inside Empulse and are attached to this secure assignment link.
                                </div>
                                <div class="small text-muted mb-2">
                                    Progress autosaves while you move through the survey, so you can pause and return without losing your place.
                                </div>
                                <div class="small text-muted mb-0">
                                    Most people finish in about {{ (int) ($surveyMeta['estimated_minutes'] ?? 4) }} minute{{ (int) ($surveyMeta['estimated_minutes'] ?? 4) === 1 ? '' : 's' }} across {{ (int) ($surveyMeta['question_count'] ?? 0) }} question{{ (int) ($surveyMeta['question_count'] ?? 0) === 1 ? '' : 's' }}.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.0625rem;">Recent History</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Wave</th>
                                        <th>Status</th>
                                        <th class="pe-4">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignmentHistory as $historyAssignment)
                                        <tr>
                                            <td class="ps-4">{{ $historyAssignment->wave_label ?? 'Survey assignment' }}</td>
                                            <td>
                                                @if($historyAssignment->status === 'completed')
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">Completed</span>
                                                @elseif($historyAssignment->draft_answers)
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2">Draft</span>
                                                @else
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2">Pending</span>
                                                @endif
                                            </td>
                                            <td class="pe-4 text-muted">{{ optional($historyAssignment->response)->submitted_at?->format('M d, Y') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                <i class="bi bi-clock-history d-block mb-2" style="font-size: 1.5rem; opacity: 0.4;"></i>
                                                No assignment history yet.
                                            </td>
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

@section('script')
@if($assignment)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const telemetry = window.empulseOnboardingTelemetry;
        const button = document.getElementById('employee-survey-launch-cta');
        const companyId = {{ (int) (auth()->user()->company_id ?? 0) }};

        if (!telemetry || !button || !companyId) {
            return;
        }

        button.addEventListener('click', function () {
            telemetry.track({
                companyId,
                name: 'employee_survey_launch_clicked',
                contextSurface: 'employee.dashboard',
                taskId: 'survey_launch',
                userSegment: 'employee',
                guidanceLevel: 'light',
                useKeepalive: true,
                properties: {
                    destination: button.getAttribute('href'),
                    assignment_id: {{ (int) $assignment->id }},
                    estimated_minutes: Number(button.dataset.estimatedMinutes || 4),
                },
            });
        });
    });
</script>
@endif
@endsection
