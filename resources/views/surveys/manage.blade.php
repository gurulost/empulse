@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Survey Overview</h5>
                        <small class="text-muted">Live survey definition, latest wave, and submission activity.</small>
                    </div>
                </div>
                <div class="card-body">
                    @if(!$hasCompanyContext)
                        <div class="py-3">
                            <h6 class="mb-2">No company context yet</h6>
                            <p class="text-muted mb-0">
                                Survey management becomes available once this manager account is attached to a company. Team imports, wave dispatch, and reporting all depend on that company context.
                            </p>
                        </div>
                    @elseif(!$survey || !$activeVersion)
                        <div class="py-3">
                            <h6 class="mb-2">No live survey is ready</h6>
                            <p class="text-muted mb-0">
                                Publish an active survey version before dispatching waves or reviewing submissions. This page will automatically show the live structure once a version is available.
                            </p>
                        </div>
                    @else
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small text-uppercase">Live Version</div>
                                    <div class="fs-5 fw-semibold">{{ $activeVersion->version }}</div>
                                    <div class="small text-muted">{{ $activeVersion->title ?: 'Untitled survey version' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small text-uppercase">Structure</div>
                                    <div class="fs-5 fw-semibold">{{ $summary['pages'] }} pages · {{ $summary['sections'] }} sections</div>
                                    <div class="small text-muted">{{ $summary['items'] }} questions currently live</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small text-uppercase">Latest Wave</div>
                                    <div class="fs-5 fw-semibold">{{ $latestWave?->label ?? 'No wave yet' }}</div>
                                    <div class="small text-muted">
                                        @if($latestWave)
                                            {{ ucfirst($latestWave->status) }} · {{ optional($latestWave->due_at)->format('M d, Y') ?? 'No due date' }}
                                        @else
                                            Create your first wave to start collecting responses.
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ route('survey-waves.index') }}" class="btn btn-primary btn-sm">Manage Waves</a>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">View Dashboard</a>
                        </div>

                        <div class="list-group list-group-flush">
                            @foreach($activeVersion->pages as $page)
                                <div class="list-group-item px-0">
                                    <div class="fw-semibold">{{ $page->title ?: 'Untitled Page' }}</div>
                                    <div class="small text-muted">
                                        {{ $page->sections->count() }} sections ·
                                        {{ $page->items->count() + $page->sections->sum(fn ($section) => $section->items->count()) }} questions
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Latest submissions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>User</th>
                                <th>Status</th>
                                <th>Wave</th>
                                <th>Submitted at</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>{{ optional($assignment->user)->email ?? 'Removed user' }}</td>
                                    <td>
                                        @if($assignment->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $assignment->wave_label ?? '—' }}</td>
                                    <td>{{ optional($assignment->response)->submitted_at?->format('M d, Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3">
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
                <div class="card-footer">
                    {{ $assignments->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Snapshot</h5>
                </div>
                <div class="card-body">
                    @if(!$hasCompanyContext)
                        <p class="text-muted mb-0">This account needs a company assignment before survey activity, analytics, and reporting can start.</p>
                    @elseif($analytics && !empty($analytics))
                        <p class="text-muted">{{ $summary['completed'] }} completed submissions out of {{ $summary['assignments'] }} assignments.</p>
                        <p class="small text-muted mb-3">The dashboard now reads from the internal survey store and the live survey version.</p>
                        <ul class="list-unstyled small mb-3">
                            <li>Pending assignments: {{ $summary['pending'] }}</li>
                            <li>Live question count: {{ $summary['items'] }}</li>
                            <li>Latest wave: {{ $latestWave?->label ?? '—' }}</li>
                        </ul>
                    @else
                        <p class="text-muted">Responses will appear here once teammates receive a wave and start submitting the live survey.</p>
                    @endif
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">View dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
