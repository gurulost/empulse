@extends('layouts.app')

@section('title')
    Verify Email - Empulse
@endsection

@section('content')
<div class="auth-page min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(145deg, #0c1222 0%, #1a1f3a 50%, #1e293b 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card border-0 shadow-2xl rounded-4 animate-scale-in">
                    <div class="card-body p-4 p-lg-5 text-center">
                        <div class="auth-icon-circle mb-4 mx-auto">
                            <i class="bi bi-envelope-check-fill"></i>
                        </div>

                        <h2 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">Verify Your Email Address</h2>

                        @if (session('resent'))
                            <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                A fresh verification link has been sent to your email address.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <p class="text-muted mb-4">
                            Before proceeding, please check your email for a verification link.
                        </p>

                        <div class="bg-light rounded-3 p-4 mb-4 text-start">
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-info-circle text-primary mt-1"></i>
                                <div>
                                    <p class="mb-0 text-muted small">
                                        <strong>Didn't receive the email?</strong><br>
                                        Check your spam folder or click below to request another verification email.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold mb-3">
                                <i class="bi bi-arrow-repeat me-2"></i>
                                Resend Verification Email
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="{{ url('/') }}" class="text-decoration-none d-inline-flex align-items-center gap-2 small">
                                <i class="bi bi-house-door-fill"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
