@extends('layouts.app')

@section('title')
    Verify Email - Empulse
@endsection

@section('content')
<div class="auth-page min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card border-0 shadow-2xl rounded-4">
                    <div class="card-body p-4 p-lg-5 text-center">
                        <div class="mb-4">
                            <i class="bi bi-envelope-check-fill text-primary" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h2 class="fw-bold mb-3">Verify Your Email Address</h2>

                        @if (session('resent'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                A fresh verification link has been sent to your email address.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <p class="text-muted mb-4">
                            Before proceeding, please check your email for a verification link.
                        </p>

                        <div class="bg-light rounded-3 p-4 mb-4">
                            <p class="mb-0 text-muted">
                                <strong>Didn't receive the email?</strong><br>
                                Check your spam folder or click below to request another verification email.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold mb-3">
                                <i class="bi bi-arrow-repeat me-2"></i>
                                Resend Verification Email
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="{{ url('/') }}" class="text-decoration-none">
                                <i class="bi bi-house-door-fill me-2"></i>
                                Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .auth-page {
        position: relative;
    }
    .auth-page::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></svg>');
        background-size: 50px 50px;
    }
    .shadow-2xl {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
</style>
@endsection
