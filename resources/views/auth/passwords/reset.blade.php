@extends('layouts.app')

@section('title')
    Reset Password - Empulse
@endsection

@section('content')
<div class="auth-page min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card border-0 shadow-2xl rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h2 class="fw-bold mb-2">Reset Password</h2>
                            <p class="text-muted">Enter your new password below</p>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input id="email" 
                                       type="email" 
                                       class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                       name="email" 
                                       value="{{ $email ?? old('email') }}" 
                                       required 
                                       autocomplete="email" 
                                       autofocus
                                       placeholder="your@email.com">
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">New Password</label>
                                <input id="password" 
                                       type="password" 
                                       class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                       name="password" 
                                       required 
                                       autocomplete="new-password"
                                       placeholder="Minimum 8 characters">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="password-confirm" class="form-label fw-semibold">Confirm New Password</label>
                                <input id="password-confirm" 
                                       type="password" 
                                       class="form-control form-control-lg" 
                                       name="password_confirmation" 
                                       required 
                                       autocomplete="new-password"
                                       placeholder="Re-enter your password">
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill mb-3 fw-bold">
                                Reset Password
                            </button>

                            <div class="text-center">
                                <a href="{{ route('login') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-arrow-left"></i>
                                    <span>Back to Login</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="text-white text-decoration-none opacity-75 hover-opacity-100">
                        <i class="bi bi-arrow-left me-2"></i>Back to Home
                    </a>
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
    .hover-opacity-100:hover {
        opacity: 1 !important;
    }
</style>
@endsection
