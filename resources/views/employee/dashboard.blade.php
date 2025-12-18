@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Employee Dashboard</h1>
                <p class="text-muted mb-0">Your survey links and status.</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                @if(!$assignment)
                    <p class="mb-0">No survey assignment found yet.</p>
                @else
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="fw-semibold">Current Survey</div>
                            <div class="text-muted small">
                                Wave: {{ $assignment->wave_label ?? '—' }} |
                                Status: {{ ucfirst($assignment->status ?? 'pending') }}
                                @if($assignment->response && $assignment->response->submitted_at)
                                    | Submitted: {{ $assignment->response->submitted_at->format('M d, Y H:i') }}
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a
                                class="btn btn-primary"
                                href="{{ route('survey.take', $assignment->token) }}"
                                target="_blank"
                                rel="noreferrer"
                            >
                                Open Survey
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

