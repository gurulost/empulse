@extends('layouts.app')

@section('title', 'Survey Management')

@section('content')
<div class="container py-4">
    <div class="page-header">
        <h1 class="page-title">Survey Management</h1>
        <p class="page-subtitle">Live survey definition, latest wave, and submission activity.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-ui-checks-grid text-primary"></i>
                        <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.0625rem;">Survey Overview</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(!$hasCompanyContext)
                        <div class="empty-state py-3">
                            <div class="empty-state-icon"><i class="bi bi-building"></i></div>
                            <h5 class="empty-state-title">No company context yet</h5>
                            <p class="empty-state-text">
                                Survey management becomes available once this manager account is attached to a company.
                            </p>
                        </div>
                    @elseif(!$survey || !$activeVersion)
                        <div class="empty-state py-3">
                            <div class="empty-state-icon"><i class="bi bi-file-earmark-text"></i></div>
                            <h5 class="empty-state-title">No live survey is ready</h5>
                            <p class="empty-state-text">
                                Publish an active survey version before dispatching waves or reviewing submissions.
                            </p>
                        </div>
                    @else
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="stat-card h-100">
                                    <div class="stat-label">Live Version</div>
                                    <div class="stat-value" style="font-size: 1.25rem;">{{ $activeVersion->version }}</div>
                                    <div class="stat-detail">{{ $activeVersion->title ?: 'Untitled survey version' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card h-100">
                                    <div class="stat-label">Structure</div>
                                    <div class="stat-value" style="font-size: 1.25rem;">{{ $summary['pages'] }} pages &middot; {{ $summary['sections'] }} sections</div>
                                    <div class="stat-detail">{{ $summary['items'] }} questions currently live</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card h-100">
                                    <div class="stat-label">Latest Wave</div>
                                    <div class="stat-value" style="font-size: 1.25rem;">{{ $latestWave?->label ?? 'No wave yet' }}</div>
                                    <div class="stat-detail">
                                        @if($latestWave)
                                            {{ ucfirst($latestWave->status) }} &middot; {{ optional($latestWave->due_at)->format('M d, Y') ?? 'No due date' }}
                                        @else
                                            Create your first wave to start collecting responses.
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <a href="{{ route('survey-waves.index') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="bi bi-calendar2-week me-1"></i>Manage Waves
                            </a>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                <i class="bi bi-speedometer2 me-1"></i>View Dashboard
                            </a>
                        </div>

                        <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #0c1222;">Survey Structure</h6>
                        <div class="list-group list-group-flush">
                            @foreach($activeVersion->pages as $page)
                                <div class="list-group-item px-0 border-bottom" style="border-color: #f1f5f9 !important;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark text-muted"></i>
                                        <div>
                                            <div class="fw-semibold">{{ $page->title ?: 'Untitled Page' }}</div>
                                            <div class="small text-muted">
                                                {{ $page->sections->count() }} sections &middot;
                                                {{ $page->items->count() + $page->sections->sum(fn ($section) => $section->items->count()) }} questions
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-inbox text-primary"></i>
                        <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.0625rem;">Latest Submissions</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th class="ps-4">User</th>
                                <th>Status</th>
                                <th>Wave</th>
                                <th class="pe-4">Submitted at</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td class="ps-4">{{ optional($assignment->user)->email ?? 'Removed user' }}</td>
                                    <td>
                                        @if($assignment->status === 'completed')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">Completed</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ $assignment->wave_label ?? '—' }}</td>
                                    <td class="pe-4 text-muted">{{ optional($assignment->response)->submitted_at?->format('M d, Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox d-block mb-2" style="font-size: 1.5rem; opacity: 0.4;"></i>
                                        @if(!$hasCompanyContext)
                                            Assign this manager to a company to start managing submissions.
                                        @elseif(!$activeVersion)
                                            Publish a survey version before creating assignments.
                                        @else
                                            No assignments found yet. Create a wave to start sending survey links.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($assignments->hasPages())
                    <div class="card-footer bg-white border-top py-3 px-4">
                        {{ $assignments->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-lightning-charge text-primary"></i>
                        <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.0625rem;">Snapshot</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(!$hasCompanyContext)
                        <p class="text-muted mb-0 small">This account needs a company assignment before survey activity can start.</p>
                    @elseif($analytics && !empty($analytics))
                        <div class="d-flex flex-column gap-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Completed</span>
                                <span class="fw-bold" style="font-family: 'Outfit', sans-serif;">{{ $summary['completed'] }}/{{ $summary['assignments'] }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary rounded-pill" style="width: {{ $summary['assignments'] > 0 ? round($summary['completed'] / $summary['assignments'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        <ul class="list-unstyled small mb-4 d-flex flex-column gap-2">
                            <li class="d-flex justify-content-between"><span class="text-muted">Pending assignments</span> <span class="fw-semibold">{{ $summary['pending'] }}</span></li>
                            <li class="d-flex justify-content-between"><span class="text-muted">Live questions</span> <span class="fw-semibold">{{ $summary['items'] }}</span></li>
                            <li class="d-flex justify-content-between"><span class="text-muted">Latest wave</span> <span class="fw-semibold">{{ $latestWave?->label ?? '—' }}</span></li>
                        </ul>
                    @else
                        <p class="text-muted small">Responses will appear here once teammates receive a wave and start submitting.</p>
                    @endif
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 w-100">
                        <i class="bi bi-speedometer2 me-1"></i>View Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
