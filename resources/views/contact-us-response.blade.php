@extends('layouts.app')
@section('title')
    Message Sent - Empulse
@endsection
@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 text-center">
                    <div class="card-body py-5 px-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(5,150,105,0.1), rgba(16,185,129,0.05));">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
                        </div>
                        <h2 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">Message Sent!</h2>
                        <p class="text-muted mb-4" style="max-width: 360px; margin: 0 auto;">
                            Thank you for reaching out. We'll review your message and get back to you within 3 business days.
                        </p>
                        <a href="{{ route('home') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
