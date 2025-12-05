@extends('layouts.app')
@section('title')
    Payment Error
@endsection
@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="mb-3">Payment Issue</h2>
                    <p class="text-muted mb-4">
                        @if(isset($message))
                            {{ $message }}
                        @else
                            We encountered an issue processing your payment. This could be due to:
                        @endif
                    </p>
                    <ul class="list-unstyled text-muted mb-4">
                        <li class="mb-2"><i class="bi bi-credit-card me-2"></i>Payment was cancelled or declined</li>
                        <li class="mb-2"><i class="bi bi-wifi-off me-2"></i>A temporary connection issue</li>
                        <li class="mb-2"><i class="bi bi-clock me-2"></i>Session timeout</li>
                    </ul>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('plans.index') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Try Again
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-2"></i>Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
