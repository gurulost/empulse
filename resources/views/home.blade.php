@extends('layouts.app')

@section('title')
    Dashboard
@endsection

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    @if((int)Auth::user()->role === 1 && (int)Auth::user()->company === 1 && (int)Auth::user()->tariff !== 1)
        <div class="container mt-3">
            <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
                <div>
                    Your subscription is inactive. Unlock full analytics for your company.
                </div>
                <a href="{{ route('plans.index') }}" class="btn btn-sm btn-primary">Upgrade now</a>
            </div>
        </div>
    @endif
    @if(Auth::user()->role !== 0)
        @if(!empty($work_attributes ?? []))
            <div class="container mt-3">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-0">Work Content Gap Monitor</h2>
                            <small class="text-muted">Updated from the latest survey submissions for your company.</small>
                        </div>
                        <span class="text-muted small">Top {{ min(count($work_attributes), 8) }} attributes</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Attribute</th>
                                <th class="text-center">Current</th>
                                <th class="text-center">Ideal</th>
                                <th class="text-center">Gap</th>
                                <th class="text-center">Desire</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach(collect($work_attributes)->take(8) as $attribute)
                                <tr>
                                    <td style="max-width: 320px;">
                                        <strong>{{ $attribute['label'] }}</strong>
                                    </td>
                                    <td class="text-center">{{ $attribute['current'] ? number_format($attribute['current'], 1) : '—' }}</td>
                                    <td class="text-center">{{ $attribute['ideal'] ? number_format($attribute['ideal'], 1) : '—' }}</td>
                                    @php $gap = $attribute['gap']; @endphp
                                    <td class="text-center">
                                        @if($gap !== null)
                                            <span class="badge {{ $gap >= 0 ? 'bg-danger' : 'bg-success' }}">
                                                {{ number_format($gap, 2) }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $attribute['desire'] ? number_format($attribute['desire'], 1) : '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-muted small">
                        Gap = Ideal − Current (positive = unmet need). Desire reflects “I wish…” intensity from the survey.
                    </div>
                </div>
            </div>
        @endif

        <div class="container mt-3">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h5 mb-0">Top Unmet Work Content Needs</h2>
                        <small class="text-muted">Gap = Ideal − Current (positive = unmet need).</small>
                    </div>
                    <span class="text-muted small">Showing {{ count($gap_chart ?? []) }} of {{ count($work_attributes ?? []) }}</span>
                </div>
                <div class="card-body">
                    <div id="gap-chart-root" data-items='@json($gap_chart ?? [])'></div>
                </div>
            </div>
        </div>
        <div class="container mt-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Department</label>
                            <select class="form-select form-select-sm" id="filter-department">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->department }}">{{ $dept->department }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Team / Supervisor</label>
                            <select class="form-select form-select-sm" id="filter-team">
                                <option value="">All Teams</option>
                                @foreach($teamleads as $lead)
                                    <option value="{{ $lead->name }}">{{ $lead->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Wave</label>
                            <select class="form-select form-select-sm" id="filter-wave">
                                <option value="">All Waves</option>
                                @foreach($available_waves ?? [] as $waveKey => $waveLabel)
                                    <option value="{{ $waveKey }}">{{ $waveLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex flex-column align-items-md-end gap-2">
                                <div id="filter-status" class="text-muted small d-none">Loading...</div>
                                <div>
                                    <button class="btn btn-sm btn-primary" id="apply-filters">Apply Filters</button>
                                    <button class="btn btn-sm btn-outline-secondary" id="reset-filters">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($indicator_scores ?? []) || !empty($weighted_indicator) || !empty($temperature_index))
            <div class="container mt-3">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-0">Indicator Satisfaction & Temperature</h2>
                            <small class="text-muted">Mirrors the upcoming indicator chart and temperature gauge.</small>
                        </div>
                        @if(!empty($weighted_indicator))
                            <div class="text-center" style="min-width: 120px;">
                                <small class="text-muted d-block">Weighted Indicator</small>
                                <div class="progress" style="height: 8px;">
                                    @php $weightedPercent = min(max(($weighted_indicator / 10) * 100, 0), 100); @endphp
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $weightedPercent }}%" aria-valuenow="{{ $weighted_indicator }}" aria-valuemin="0" aria-valuemax="10"></div>
                                </div>
                                <span class="fw-semibold">{{ number_format($weighted_indicator, 1) }}/10</span>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div id="indicator-list-root" data-items='@json($indicator_scores ?? [])'></div>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($team_scatter ?? []))
            <div class="container mt-3">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-0">Team Satisfaction vs Culture Map</h2>
                            <small class="text-muted">Each row summarizes the new analytics service output that will replace the legacy bubble chart.</small>
                        </div>
                        @if(!empty($team_culture_evaluation))
                            <div class="text-center" style="min-width: 140px;">
                                <small class="text-muted d-block">Team Culture Eval</small>
                                <div class="progress" style="height: 8px;">
                                    @php $culturePercent = min(max((($team_culture_evaluation + 9) / 18) * 100, 0), 100); @endphp
                                    <div class="progress-bar {{ $team_culture_evaluation >= 0 ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ $culturePercent }}%" aria-valuenow="{{ $team_culture_evaluation }}" aria-valuemin="-9" aria-valuemax="9"></div>
                                </div>
                                <span class="fw-semibold">{{ number_format($team_culture_evaluation, 2) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div id="team-scatter-root" data-items='@json($team_scatter ?? [])'></div>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($team_culture ?? []))
            @php
                $teamCultureItems = collect($team_culture['items'] ?? []);
                $negativeItems = $teamCultureItems->where('polarity', 'negative')->sortByDesc('value')->take(3);
                $positiveItems = $teamCultureItems->where('polarity', 'positive')->sortByDesc('value')->take(3);
            @endphp
            <div class="container mt-3">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-0">Team Culture Pulse</h2>
                            <small class="text-muted">Net score = Positive averages − Negative averages (higher = healthier).</small>
                        </div>
                        @if(!is_null($team_culture['score'] ?? null))
                            <span class="badge {{ ($team_culture['score'] ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }}">
                                {{ number_format($team_culture['score'], 2) }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <h6 class="text-muted text-uppercase small">Positive Signals</h6>
                                <p class="display-6 mb-1">{{ $team_culture['positive'] ? number_format($team_culture['positive'], 1) : '—' }}</p>
                                <small class="text-muted">Average of trust, clarity, respect statements.</small>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <h6 class="text-muted text-uppercase small">Areas of Friction</h6>
                                <p class="display-6 mb-1">{{ $team_culture['negative'] ? number_format($team_culture['negative'], 1) : '—' }}</p>
                                <small class="text-muted">Average of conflict, bureaucracy, pressure statements.</small>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted text-uppercase small">Net Culture Score</h6>
                                @php $score = $team_culture['score'] ?? null; @endphp
                                <div class="progress" style="height: 8px;">
                                    @php
                                        $percent = $score !== null ? min(max((($score + 9) / 18) * 100, 0), 100) : 0;
                                    @endphp
                                    <div class="progress-bar {{ $score >= 0 ? 'bg-success' : 'bg-danger' }}" role="progressbar"
                                         style="width: {{ $score !== null ? $percent : 0 }}%"></div>
                                </div>
                                <small class="text-muted">
                                    {{ $score !== null ? ($score >= 0 ? 'Above waterline' : 'Needs attention') : 'No data yet' }}
                                </small>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-2">Top Tension Drivers</h6>
                                <ul class="list-unstyled mb-0">
                                    @forelse($negativeItems as $item)
                                        <li class="d-flex justify-content-between">
                                            <span>{{ $item['qid'] }}</span>
                                            <span class="badge bg-danger">{{ number_format($item['value'], 1) }}</span>
                                        </li>
                                    @empty
                                        <li class="text-muted">No responses yet.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-2">Positive Reinforcers</h6>
                                <ul class="list-unstyled mb-0">
                                    @forelse($positiveItems as $item)
                                        <li class="d-flex justify-content-between">
                                            <span>{{ $item['qid'] }}</span>
                                            <span class="badge bg-success">{{ number_format($item['value'], 1) }}</span>
                                        </li>
                                    @empty
                                        <li class="text-muted">No responses yet.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($impact_series ?? []))
            <div class="container mt-3">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-0">Impact on Society Snapshot</h2>
                            <small class="text-muted">How employees rate their impact today vs. the importance and unmet desire.</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <p class="text-uppercase small text-muted mb-1">Current Impact</p>
                                <p class="display-6 mb-0">{{ $impact_series['positive'] ? number_format($impact_series['positive'], 1) : '—' }}</p>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <p class="text-uppercase small text-muted mb-1">Importance in Ideal Role</p>
                                <p class="display-6 mb-0">{{ $impact_series['importance'] ? number_format($impact_series['importance'], 1) : '—' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="text-uppercase small text-muted mb-1">Desire Gap</p>
                                <p class="display-6 mb-0">{{ $impact_series['desire'] ? number_format($impact_series['desire'], 1) : '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    <script src="{{asset('/js/home.js')}}" type="module"></script>
    <script src="{{asset('/js/modal.js')}}" type="module"></script>
@endsection
