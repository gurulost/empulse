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
                <form method="POST" action="{{ route('actions.findings.store', ['company_id' => $companyId]) }}" class="row g-3">
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

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h2 class="h5 mb-1">Advisor workspace notes</h2>
                    <p class="small text-muted mb-0">
                        Customer-shared notes are visible to authorized customer workspace users and the approved advisor.
                        WorkFit-internal notes are visible only to a currently approved WorkFit advisor.
                    </p>
                </div>
                @if($isCustomerApprovedAdvisor)
                    <span class="badge bg-primary">Customer-approved advisor context</span>
                @endif
            </div>

            @if(Auth::user()->hasCapability('actions.manage'))
                <form method="POST" action="{{ route('actions.notes.store') }}" class="row g-3 mt-1">
                    @csrf
                    <input type="hidden" name="company_id" value="{{ $companyId }}">
                    <div class="col-md-3">
                        <label for="advisor-note-visibility" class="form-label">Visibility</label>
                        <select id="advisor-note-visibility" name="visibility" class="form-select" required>
                            <option value="customer_shared">Customer shared</option>
                            @if($isCustomerApprovedAdvisor)
                                <option value="workfit_internal">WorkFit internal</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label for="advisor-note-body" class="form-label">Note</label>
                        <textarea id="advisor-note-body" name="body" class="form-control" rows="2" minlength="2" maxlength="10000" required></textarea>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-outline-primary w-100">Record note</button>
                    </div>
                </form>
            @endif

            <div class="mt-3">
                @forelse($workspaceNotes as $note)
                    <article class="border-top py-3">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <strong class="small">{{ $note->author?->name ?: 'Former user' }}</strong>
                            <span class="badge {{ $note->visibility === 'workfit_internal' ? 'bg-dark' : 'bg-success' }}">
                                {{ $note->visibility === 'workfit_internal' ? 'WorkFit internal' : 'Customer shared' }}
                            </span>
                        </div>
                        <p class="mb-1 mt-2">{{ $note->body }}</p>
                        <div class="small text-muted">{{ $note->created_at?->toDayDateTimeString() }}</div>
                    </article>
                @empty
                    <p class="small text-muted mt-3 mb-0">No workspace notes have been recorded.</p>
                @endforelse
            </div>
        </div>
    </div>

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
                                <form method="POST" action="{{ route('actions.findings.decide', ['finding' => $finding, 'company_id' => $companyId]) }}" class="mt-3">
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
                            <dl class="row small mb-2">
                                <dt class="col-sm-4">Owner</dt>
                                <dd class="col-sm-8">{{ $action->owner?->name ?: 'Former user' }}</dd>
                                <dt class="col-sm-4">Timing</dt>
                                <dd class="col-sm-8">{{ $action->starts_on->toFormattedDateString() }} → {{ $action->target_date->toFormattedDateString() }}</dd>
                                <dt class="col-sm-4">Success criteria</dt>
                                <dd class="col-sm-8">{{ data_get($action->success_criteria, 'statement') }}</dd>
                                <dt class="col-sm-4">Guardrails</dt>
                                <dd class="col-sm-8">
                                    <ul class="mb-0 ps-3">
                                        @foreach($action->guardrails ?? [] as $guardrail)<li>{{ $guardrail }}</li>@endforeach
                                    </ul>
                                </dd>
                            </dl>
                            <div class="small">Follow-up plans: {{ $action->measurementPlans->count() }}</div>

                            @if(Auth::user()->hasCapability('actions.manage') && $action->status === 'draft')
                                <form method="POST" action="{{ route('actions.measurement.store', ['action' => $action, 'company_id' => $companyId]) }}" class="row g-2 mt-2">
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
                                        <form method="POST" action="{{ route('actions.followup-wave.create', ['plan' => $plan, 'company_id' => $companyId]) }}" class="row g-2 mt-2">
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
                                        <div class="small mt-2">
                                            <strong>{{ $plan->followupWave?->label ?: "Follow-up wave #{$plan->followup_wave_id}" }}</strong>
                                            @if($plan->followupWave)
                                                · {{ $plan->followupWave->status }}
                                                · opens {{ $plan->followupWave->opens_at?->toFormattedDateString() ?: 'not scheduled' }}
                                                · due {{ $plan->followupWave->due_at?->toFormattedDateString() ?: 'not scheduled' }}
                                            @endif
                                            <div class="text-muted">Preserves the predeclared metric and cohort definition.</div>
                                        </div>
                                        @if($plan->outcomes->isEmpty())
                                            <form method="POST" action="{{ route('actions.evaluate', ['plan' => $plan, 'company_id' => $companyId]) }}" class="mt-2">
                                                @csrf
                                                <input type="hidden" name="followup_wave_id" value="{{ $plan->followup_wave_id }}">
                                                <button class="btn btn-sm btn-outline-secondary">Evaluate available follow-up evidence</button>
                                            </form>
                                        @endif
                                    @endif
                                    @foreach($plan->outcomes as $outcome)
                                        @php($snapshot = $outcome->evaluation_snapshot)
                                        <div class="border rounded-3 bg-white p-3 mt-3">
                                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                                <strong class="small">Recorded outcome</strong>
                                                <span class="badge {{ $outcome->result === 'movement_observed' ? 'bg-success' : (in_array($outcome->result, ['inconclusive', 'incompatible']) ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                    {{ str_replace('_', ' ', $outcome->result) }}
                                                </span>
                                            </div>
                                            <dl class="row small mt-2 mb-2">
                                                <dt class="col-sm-5">Baseline → follow-up</dt>
                                                <dd class="col-sm-7">{{ data_get($snapshot, 'baseline_value', 'unavailable') }} → {{ data_get($snapshot, 'followup_value', 'unavailable') }}</dd>
                                                <dt class="col-sm-5">Observed change</dt>
                                                <dd class="col-sm-7">{{ data_get($snapshot, 'change', 'unavailable') }}</dd>
                                                <dt class="col-sm-5">Valid sample</dt>
                                                <dd class="col-sm-7">{{ data_get($snapshot, 'sample.valid_n', 'unavailable') }}</dd>
                                                <dt class="col-sm-5">Comparable definition</dt>
                                                <dd class="col-sm-7">{{ data_get($snapshot, 'compatible') ? 'Yes' : 'No' }}</dd>
                                            </dl>
                                            <p class="small mb-1">{{ $outcome->interpretation }}</p>
                                            <p class="small text-muted mb-0"><strong>Causality limit:</strong> {{ $outcome->causality_limit }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                            @if(Auth::user()->hasCapability('actions.manage') && $action->status === 'draft' && $action->measurementPlans->isNotEmpty())
                                <form method="POST" action="{{ route('actions.transition', ['action' => $action, 'company_id' => $companyId]) }}" class="mt-3">
                                    @csrf
                                    <input type="hidden" name="status" value="committed">
                                    <button class="btn btn-sm btn-primary">Commit with this measurement plan</button>
                                </form>
                            @elseif(Auth::user()->hasCapability('actions.manage') && in_array($action->status, ['committed', 'in_progress']))
                                <form method="POST" action="{{ route('actions.communications.publish', ['action' => $action, 'company_id' => $companyId]) }}" class="mt-3">
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
                        <form method="POST" action="{{ route('actions.plans.store', ['company_id' => $companyId]) }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="diagnostic_finding_id" value="{{ $accepted->id }}">
                            <div class="col-md-6"><label class="form-label" for="action-title">Title</label><input id="action-title" name="title" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label" for="action-owner">Owner</label><select id="action-owner" name="owner_user_id" class="form-select" required>@foreach($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach</select></div>
                            <div class="col-12">
                                <label class="form-label" for="intervention-playbook">Optional WorkFit playbook</label>
                                <select id="intervention-playbook" name="intervention_playbook_version_id" class="form-select">
                                    <option value="">Create a custom, governed action</option>
                                    @foreach($playbooks as $playbook)
                                        <option value="{{ $playbook->id }}">{{ $playbook->title }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Playbooks are versioned starting points, not promises that a change will cause an outcome. Review the evidence label and limitations before selecting one.</div>
                                @foreach($playbooks as $playbook)
                                    <details class="border rounded-3 p-3 mt-2">
                                        <summary class="fw-semibold">{{ $playbook->title }} · {{ str_replace('-', ' ', $playbook->evidence_grade) }}</summary>
                                        <p class="small mt-3 mb-2">{{ $playbook->description }}</p>
                                        <dl class="small mb-0">
                                            <dt>Evidence source</dt>
                                            <dd>{{ $playbook->evidence_source }}</dd>
                                            <dt>Applicable when</dt>
                                            <dd>{{ $playbook->applicability }}</dd>
                                            <dt>Limitations</dt>
                                            <dd>{{ $playbook->limitations }}</dd>
                                            <dt>Claims limit</dt>
                                            <dd class="mb-0">{{ $playbook->claims_limit }}</dd>
                                        </dl>
                                    </details>
                                @endforeach
                            </div>
                            <div class="col-12"><label class="form-label" for="action-hypothesis">Hypothesis</label><textarea id="action-hypothesis" name="hypothesis" class="form-control" rows="2" required></textarea></div>
                            <div class="col-12"><label class="form-label" for="action-planned-change">Planned change</label><textarea id="action-planned-change" name="planned_change" class="form-control" rows="3" required></textarea></div>
                            <div class="col-12"><label class="form-label" for="action-success-criteria">Success criteria</label><textarea id="action-success-criteria" name="success_criteria" class="form-control" rows="2" required></textarea></div>
                            <div class="col-md-6"><label class="form-label" for="action-start-date">Start date</label><input id="action-start-date" name="starts_on" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                            <div class="col-md-6"><label class="form-label" for="action-target-date">Target date</label><input id="action-target-date" name="target_date" type="date" class="form-control" value="{{ now()->addDays(45)->toDateString() }}" required></div>
                            <div class="col-12"><button class="btn btn-primary">Create draft action</button></div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
