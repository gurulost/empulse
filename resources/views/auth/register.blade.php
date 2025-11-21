@extends('layouts.app')

@section('title')
    Sign Up - Empulse
@endsection

@section('content')
<div class="auth-page min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11 col-lg-9 col-xl-7">
                <div class="card border-0 shadow-2xl rounded-4 overflow-hidden">
                    <div class="row g-0">
                        <!-- Left Side - Branding -->
                        <div class="col-md-5 d-none d-md-flex flex-column justify-content-center align-items-center text-white p-5" style="background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);">
                            <div class="mb-4">
                                <i class="bi bi-person-plus-fill" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="fw-bold text-center mb-3">Join Empulse</h3>
                            <p class="text-center small opacity-90">Start transforming your workplace culture today</p>
                            <div class="mt-4 text-center">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span class="small">14-day free trial</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span class="small">No credit card required</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span class="small">Cancel anytime</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side - Registration Form -->
                        <div class="col-md-7">
                            <div class="card-body p-4 p-lg-5">
                                <h2 class="fw-bold mb-2">Create Account</h2>
                                <p class="text-muted mb-4">Get started with your free trial</p>

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

                                <form method="POST" action="{{ route('register') }}">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="name" class="form-label fw-semibold">Full Name</label>
                                        <input id="name" 
                                               type="text" 
                                               class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                               name="name" 
                                               value="{{ old('name') }}" 
                                               required 
                                               autocomplete="name" 
                                               autofocus
                                               placeholder="John Doe">
                                        @error('name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-semibold">Email Address</label>
                                        <input id="email" 
                                               type="email" 
                                               class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                               name="email" 
                                               value="{{ old('email') }}" 
                                               required 
                                               autocomplete="email"
                                               placeholder="your@email.com">
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="company_title" class="form-label fw-semibold">Company Name</label>
                                        <input id="company_title" 
                                               type="text" 
                                               class="form-control form-control-lg @error('company_title') is-invalid @enderror" 
                                               name="company_title" 
                                               value="{{ old('company_title') }}" 
                                               required
                                               placeholder="Your Company Inc.">
                                        @error('company_title')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-semibold">Password</label>
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
                                        <label for="password-confirm" class="form-label fw-semibold">Confirm Password</label>
                                        <input id="password-confirm" 
                                               type="password" 
                                               class="form-control form-control-lg" 
                                               name="password_confirmation" 
                                               required 
                                               autocomplete="new-password"
                                               placeholder="Re-enter your password">
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill mb-3 fw-bold">
                                        Create Account
                                    </button>

                                    @if(Route::has('auth.google'))
                                        <div class="text-center my-3">
                                            <span class="text-muted small">OR</span>
                                        </div>

                                        <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary btn-lg w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                            </svg>
                                            Continue with Google
                                        </a>
                                    @endif

                                    <div class="text-center mt-4">
                                        <p class="text-muted mb-0">
                                            Already have an account?
                                            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Log in</a>
                                        </p>
                                    </div>

                                    <div class="text-center mt-3">
                                        <p class="small text-muted">
                                            By signing up, you agree to our 
                                            <a href="https://workfitdx.com/terms-and-conditions/" target="_blank" class="text-decoration-none">Terms</a> and 
                                            <a href="https://workfitdx.com/privacy-policy-2/" target="_blank" class="text-decoration-none">Privacy Policy</a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
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
