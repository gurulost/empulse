@extends('layouts.app')

@section('title', 'Set up your Empulse account')

@section('content')
<div class="container py-5" style="max-width: 620px;">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
            <div class="small text-uppercase fw-semibold text-secondary mb-2">Secure invitation</div>
            <h1 class="h3 fw-bold mb-3">Set up your Empulse account</h1>
            <p class="text-muted">
                This one-time invitation is for <strong>{{ $invitation->email }}</strong>.
                Choose a password to activate your account. Empulse never sends temporary passwords by email.
            </p>

            <form method="POST" action="{{ route('invitations.accept', $token) }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input
                        class="form-control"
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button class="btn btn-primary rounded-pill px-4" type="submit">
                    Activate account
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
