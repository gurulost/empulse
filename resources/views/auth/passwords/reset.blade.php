@extends('layouts.app')

@section('title')
    Reset Password - Empulse
@endsection

@section('content')
<div class="auth-page min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(145deg, #0c1222 0%, #1a1f3a 50%, #1e293b 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card border-0 shadow-2xl rounded-4 animate-scale-in">
                    <div class="card-body p-4 p-lg-5">
                        <div class="text-center mb-4">
                            <div class="auth-icon-circle mb-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <h2 class="fw-bold mb-2" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">Reset Password</h2>
                            <p class="text-muted">Enter your new password below</p>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-exclamation-circle-fill mt-1"></i>
                                    <ul class="mb-0 ps-0 list-unstyled">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus placeholder="your@email.com">
                                @error('email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">New Password</label>
                                <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Minimum 8 characters">
                                @error('password')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="password-confirm" class="form-label fw-semibold">Confirm New Password</label>
                                <input id="password-confirm" type="password" class="form-control form-control-lg" name="password_confirmation" required autocomplete="new-password" placeholder="Re-enter your password">
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill mb-3 fw-bold">
                                Reset Password
                            </button>

                            <div class="text-center">
                                <a href="{{ route('login') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-arrow-left"></i><span>Back to Login</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="text-white text-decoration-none opacity-50 hover-opacity-100 d-inline-flex align-items-center gap-2 small">
                        <i class="bi bi-arrow-left"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
