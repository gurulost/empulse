@extends('layouts.app')

@section('title')
    Manager Dashboard - Empulse
@endsection

@section('content')
    <div class="container py-4">
        <div class="page-header">
            <h1 class="page-title">Welcome back, {{ Auth::user()->name }}</h1>
            <p class="page-subtitle">You're signed in as a Manager. Use the sidebar to navigate your workspace.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <a href="{{ route('surveys.manage') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex flex-column align-items-start">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(99,102,241,0.05));">
                                <i class="bi bi-ui-checks-grid text-primary fs-5"></i>
                            </div>
                            <h5 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif; color: #0c1222;">Surveys</h5>
                            <p class="text-muted small mb-0">Manage your active surveys, view submissions, and monitor progress.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('team.manage') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex flex-column align-items-start">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(5,150,105,0.1), rgba(16,185,129,0.05));">
                                <i class="bi bi-people fs-5 text-success"></i>
                            </div>
                            <h5 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif; color: #0c1222;">Team</h5>
                            <p class="text-muted small mb-0">Add team members, manage departments, and import from CSV.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('survey-waves.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body p-4 d-flex flex-column align-items-start">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(2,132,199,0.1), rgba(14,165,233,0.05));">
                                <i class="bi bi-calendar2-week fs-5 text-info"></i>
                            </div>
                            <h5 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif; color: #0c1222;">Waves</h5>
                            <p class="text-muted small mb-0">Schedule and dispatch survey waves to collect team feedback.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
@endsection
