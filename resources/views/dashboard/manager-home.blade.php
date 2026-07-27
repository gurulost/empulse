@extends('layouts.app')

@section('title', 'Leadership Loop')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-semibold text-primary mb-2">Empulse operating loop</div>
            <h1 class="h2 mb-2">Turn listening into accountable action</h1>
            <p class="text-muted mb-0">Review eligible evidence, choose a response, communicate it, and learn from the next comparable wave.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('actions.index') }}" class="btn btn-primary">Open action workspace</a>
            <a href="{{ route('dashboard.analytics') }}" class="btn btn-outline-secondary">Review eligible analytics</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted">Reliable findings awaiting a decision</div>
                    <div class="display-5 fw-bold my-2">{{ $findings->where('status', 'proposed')->count() }}</div>
                    <p class="small text-muted mb-0">A first response never counts as a finding; only privacy-eligible evidence can enter this queue.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted">Actions needing ownership or progress</div>
                    <div class="display-5 fw-bold my-2">{{ $actions->count() }}</div>
                    <p class="small text-muted mb-0">Commitment requires an owner, success criteria, guardrails, and a predeclared follow-up measurement.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted">Ready for follow-up</div>
                    <div class="display-5 fw-bold my-2">{{ $actions->filter(fn ($action) => $action->measurementPlans()->whereNotNull('followup_wave_id')->exists())->count() }}</div>
                    <p class="small text-muted mb-0">Outcome language remains descriptive; Empulse does not claim an action caused observed movement.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Next evidence decisions</h2></div>
                <div class="card-body">
                    @forelse($findings as $finding)
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $finding->metric_id }}</strong>
                                <span class="badge {{ $finding->status === 'accepted' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $finding->status }}</span>
                            </div>
                            <p class="small text-muted mt-2 mb-0">{{ $finding->interpretation }}</p>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No eligible findings are waiting. Continue collection or review the analytics sample state.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Owned leadership work</h2></div>
                <div class="card-body">
                    @forelse($actions as $action)
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong>{{ $action->title }}</strong>
                                <span class="badge bg-primary">{{ str_replace('_', ' ', $action->status) }}</span>
                            </div>
                            <div class="small text-muted mt-2">
                                Target: {{ optional($action->target_date)->format('M j, Y') ?? 'not set' }}
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No active action is recorded yet. Accept an eligible finding before drafting one.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
