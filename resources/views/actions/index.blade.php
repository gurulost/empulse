@extends('layouts.app')

@section('title', 'Leadership Action Workspace')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-semibold text-primary mb-2">Findings → action → learning</div>
            <h1 class="h2 mb-2">Leadership action workspace</h1>
            <p class="text-muted mb-0">Every action remains tied to frozen evidence, an owner, success criteria, employee follow-through, and a comparable future measure.</p>
        </div>
        <a href="{{ route('dashboard.analytics') }}" class="btn btn-outline-secondary">Review analytics</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if(Auth::user()->hasCapability('actions.manage'))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5">Capture a reliable finding</h2>
                <p class="small text-muted">Values are recalculated server-side; this form cannot submit or alter evidence.</p>
                <form method="POST" action="{{ route('actions.findings.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label for="finding-wave" class="form-label">Completed wave</label>
                        <select id="finding-wave" name="wave_id" class="form-select" required>
                            <option value="">Choose a wave</option>
                            @foreach($waves as $wave)<option value="{{ $wave->id }}">{{ $wave->label ?: "Wave {$wave->id}" }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="finding-metric" class="form-label">Metric ID</label>
                        <input id="finding-metric" name="metric_id" class="form-control" placeholder="opportunity.WCA_REL" required>
                        <div class="form-text">Examples: opportunity.WCA_REL, indicator.growth, culture.team_core</div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Verify and capture</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if(Auth::user()->hasCapability('advisor-access.manage'))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5">Customer-approved advisor access</h2>
                <p class="small text-muted">Grant a named WorkFit advisor time-bounded access to this organization’s action workspace. You can revoke access at any time.</p>
                <form method="POST" action="{{ route('actions.advisors.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label for="advisor-user" class="form-label">Advisor</label>
                        <select id="advisor-user" name="advisor_user_id" class="form-select" required>
                            <option value="">Choose an advisor</option>
                            @foreach($availableAdvisors as $advisor)
                                <option value="{{ $advisor->id }}">{{ $advisor->name }} ({{ $advisor->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="advisor-purpose" class="form-label">Purpose</label>
                        <input id="advisor-purpose" name="purpose" class="form-control" minlength="10" maxlength="2000" required placeholder="Why this advisor needs access">
                    </div>
                    <div class="col-md-2">
                        <label for="advisor-until" class="form-label">Expires</label>
                        <input id="advisor-until" name="valid_until" type="date" min="{{ now()->addDay()->toDateString() }}" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-outline-primary w-100">Grant access</button>
                    </div>
                </form>
                @foreach($advisorGrants as $grant)
                    <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3 gap-3">
                        <div class="small">
                            <strong>{{ $grant->advisor?->name }}</strong> · {{ $grant->status }}
                            @if($grant->valid_until) · through {{ $grant->valid_until->toDateString() }} @endif
                            <div class="text-muted">{{ $grant->purpose }}</div>
                        </div>
                        @if($grant->status === 'active' && !$grant->revoked_at)
                            <form method="POST" action="{{ route('actions.advisors.destroy', $grant) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Revoke</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Evidence decisions</h2></div>
                <div class="card-body">
                    @forelse($findings as $finding)
                        <article class="border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $finding->metric_id }}</strong>
                                <span class="badge {{ $finding->status === 'accepted' ? 'bg-success' : ($finding->status === 'dismissed' ? 'bg-secondary' : 'bg-warning text-dark') }}">{{ $finding->status }}</span>
                            </div>
                            <p class="small mt-2">{{ $finding->interpretation }}</p>
                            <p class="small text-muted">{{ $finding->limits }}</p>
                            <div class="small text-muted mb-2">
                                Valid N: {{ data_get($finding->evidence_snapshot, 'sample.valid_n') }} ·
                                Registry: {{ Str::limit(data_get($finding->evidence_snapshot, 'metric_definition_hash'), 12, '') }}
                            </div>
                            @if(Auth::user()->hasCapability('actions.manage') && $finding->status === 'proposed')
                                <form method="POST" action="{{ route('actions.findings.decide', $finding) }}" class="mt-3">
                                    @csrf
                                    <label for="finding-rationale-{{ $finding->id }}" class="form-label small">Decision rationale</label>
                                    <textarea id="finding-rationale-{{ $finding->id }}" name="rationale" class="form-control mb-2" rows="2" required></textarea>
                                    <button name="decision" value="accepted" class="btn btn-sm btn-success">Accept for action</button>
                                    <button name="decision" value="dismissed" class="btn btn-sm btn-outline-secondary">Dismiss</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="text-muted">No reliable finding has been captured yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Leadership actions</h2></div>
                <div class="card-body">
                    @forelse($actions as $action)
                        <article class="border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $action->title }}</strong>
                                <span class="badge bg-primary">{{ str_replace('_', ' ', $action->status) }}</span>
                            </div>
                            <p class="small mt-2 mb-1"><strong>Planned change:</strong> {{ $action->planned_change }}</p>
                            <p class="small text-muted"><strong>Hypothesis:</strong> {{ $action->hypothesis }}</p>
                            <div class="small">Follow-up plans: {{ $action->measurementPlans->count() }}</div>

                            @if(Auth::user()->hasCapability('actions.manage') && $action->status === 'draft')
                                <form method="POST" action="{{ route('actions.measurement.store', $action) }}" class="row g-2 mt-2">
                                    @csrf
                                    <div class="col-md-5">
                                        <label class="form-label small" for="direction-{{ $action->id }}">Expected direction</label>
                                        <select id="direction-{{ $action->id }}" name="target_direction" class="form-select form-select-sm" required>
                                            <option value="increase">Increase</option>
                                            <option value="decrease">Decrease</option>
                                            <option value="change">Any meaningful change</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small" for="threshold-{{ $action->id }}">Minimum change</label>
                                        <input id="threshold-{{ $action->id }}" name="minimum_meaningful_change" type="number" min="0" max="10" step="0.1" class="form-control form-control-sm" value="0.5">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end"><button class="btn btn-sm btn-outline-primary w-100">Plan follow-up</button></div>
                                </form>
                            @endif

                            @foreach($action->measurementPlans as $plan)
                                <div class="bg-light border rounded-3 p-3 mt-3">
                                    <div class="d-flex justify-content-between gap-2">
                                        <strong class="small">{{ $plan->metric_id }}</strong>
                                        <span class="badge bg-secondary">{{ $plan->status }}</span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        Expected {{ $plan->target_direction }} · minimum meaningful change {{ $plan->minimum_meaningful_change ?? 'not set' }}
                                    </div>
                                    @if(Auth::user()->hasCapability('actions.manage') && !$plan->followup_wave_id)
                                        <form method="POST" action="{{ route('actions.followup-wave.create', $plan) }}" class="row g-2 mt-2">
                                            @csrf
                                            <div class="col-12">
                                                <label class="form-label small" for="followup-label-{{ $plan->id }}">Governed Pulse label</label>
                                                <input id="followup-label-{{ $plan->id }}" name="label" class="form-control form-control-sm" value="Follow-up: {{ $action->title }}" required>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small" for="followup-opens-{{ $plan->id }}">Opens</label>
                                                <input id="followup-opens-{{ $plan->id }}" name="opens_at" type="date" class="form-control form-control-sm" value="{{ now()->addDays(30)->toDateString() }}" required>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small" for="followup-due-{{ $plan->id }}">Due</label>
                                                <input id="followup-due-{{ $plan->id }}" name="due_at" type="date" class="form-control form-control-sm" value="{{ now()->addDays(44)->toDateString() }}" required>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button class="btn btn-sm btn-outline-primary w-100">Create</button>
                                            </div>
                                        </form>
                                    @elseif($plan->followup_wave_id)
                                        <div class="small mt-2">Follow-up wave #{{ $plan->followup_wave_id }} is linked and preserves the predeclared metric.</div>
                                        <form method="POST" action="{{ route('actions.evaluate', $plan) }}" class="mt-2">
                                            @csrf
                                            <input type="hidden" name="followup_wave_id" value="{{ $plan->followup_wave_id }}">
                                            <button class="btn btn-sm btn-outline-secondary">Evaluate available follow-up evidence</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach

                            @if(Auth::user()->hasCapability('actions.manage') && $action->status === 'draft' && $action->measurementPlans->isNotEmpty())
                                <form method="POST" action="{{ route('actions.transition', $action) }}" class="mt-3">
                                    @csrf
                                    <input type="hidden" name="status" value="committed">
                                    <button class="btn btn-sm btn-primary">Commit with this measurement plan</button>
                                </form>
                            @elseif(Auth::user()->hasCapability('actions.manage') && in_array($action->status, ['committed', 'in_progress']))
                                <form method="POST" action="{{ route('actions.communications.publish', $action) }}" class="mt-3">
                                    @csrf
                                    <label class="form-label small" for="communication-{{ $action->id }}">Employee follow-through message</label>
                                    <input type="hidden" name="audience" value="All participating employees">
                                    <textarea id="communication-{{ $action->id }}" name="message" class="form-control form-control-sm" rows="3" required placeholder="What we heard, what leadership will test, and when employees will hear what was learned."></textarea>
                                    <button class="btn btn-sm btn-outline-primary mt-2">Record as published</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="text-muted">No action is recorded yet.</p>
                    @endforelse

                    @php($accepted = $findings->first(fn ($finding) => $finding->status === 'accepted' && $finding->actions->isEmpty()))
                    @if(Auth::user()->hasCapability('actions.manage') && $accepted)
                        <hr>
                        <h3 class="h6">Draft an action for {{ $accepted->metric_id }}</h3>
                        <form method="POST" action="{{ route('actions.plans.store') }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="diagnostic_finding_id" value="{{ $accepted->id }}">
                            <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Owner</label><select name="owner_user_id" class="form-select" required>@foreach($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach</select></div>
                            <div class="col-12">
                                <label class="form-label" for="intervention-playbook">Optional WorkFit playbook</label>
                                <select id="intervention-playbook" name="intervention_playbook_version_id" class="form-select">
                                    <option value="">Create a custom, governed action</option>
                                    @foreach($playbooks as $playbook)
                                        <option value="{{ $playbook->id }}">{{ $playbook->title }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Playbooks are versioned starting points, not promises that a change will cause an outcome.</div>
                            </div>
                            <div class="col-12"><label class="form-label">Hypothesis</label><textarea name="hypothesis" class="form-control" rows="2" required></textarea></div>
                            <div class="col-12"><label class="form-label">Planned change</label><textarea name="planned_change" class="form-control" rows="3" required></textarea></div>
                            <div class="col-12"><label class="form-label">Success criteria</label><textarea name="success_criteria" class="form-control" rows="2" required></textarea></div>
                            <div class="col-md-6"><label class="form-label">Start date</label><input name="starts_on" type="date" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Target date</label><input name="target_date" type="date" class="form-control"></div>
                            <div class="col-12"><button class="btn btn-primary">Create draft action</button></div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
