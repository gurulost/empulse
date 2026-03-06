@extends('layouts.app')

@section('title')
    Plans & Billing - Empulse
@endsection

@section('content')
    @php
        $formatPrice = function ($rawPrice) {
            $price = (float) $rawPrice;
            if ($price >= 1000) {
                $price = $price / 100;
            }
            return number_format($price, $price == floor($price) ? 0 : 2);
        };
    @endphp

    <div class="container py-5">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <h1 class="page-title text-center" style="font-size: 2rem;">Plans & Billing</h1>
                <p class="page-subtitle text-center mx-auto" style="max-width: 480px;">
                    Choose the subscription that matches how you want to run Empulse.
                </p>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(!$billingAvailable)
            <div class="alert alert-warning rounded-3 border-0 mb-4">
                <i class="bi bi-info-circle me-1"></i>
                Billing is unavailable in this environment because Stripe is not configured. You can still review plan details.
            </div>
        @endif

        <div class="row g-4 align-items-stretch justify-content-center">
            @foreach($plans as $index => $plan)
                @php
                    $meta = $planMetaBySlug[$plan->slug] ?? [];
                    $isPopular = $index === 1;
                @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm plan-card {{ $isPopular ? 'plan-card-popular' : '' }}">
                        @if($isPopular)
                            <div class="plan-popular-badge">Most Popular</div>
                        @endif
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="stat-label">{{ $meta['eyebrow'] ?? 'Subscription plan' }}</div>
                            <h2 class="h4 fw-bold mb-2" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">{{ $plan->name }}</h2>
                            <div class="d-flex align-items-baseline gap-1 mb-1">
                                <span style="font-family: 'Outfit', sans-serif; font-size: 2.5rem; font-weight: 800; color: #0c1222; letter-spacing: -0.03em;">${{ $formatPrice($plan->price) }}</span>
                            </div>
                            <div class="text-muted small mb-4">per month</div>

                            <p class="text-muted mb-4">{{ $meta['summary'] ?? $plan->description }}</p>

                            <ul class="list-unstyled flex-grow-1 mb-4">
                                @foreach(($meta['features'] ?? []) as $feature)
                                    <li class="d-flex align-items-start gap-2 mb-2">
                                        <i class="bi bi-check-circle-fill text-success mt-1" style="font-size: 0.875rem;"></i>
                                        <span class="small">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ route('plans.show', $plan) }}"
                               class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} w-100 rounded-pill fw-semibold {{ !$billingAvailable ? 'btn-outline-secondary' : '' }}">
                                {{ $billingAvailable ? ($meta['cta'] ?? 'Continue') : 'Review Details' }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .plan-card {
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            position: relative;
            overflow: hidden;
        }
        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1) !important;
        }
        .plan-card-popular {
            border: 2px solid #4f46e5 !important;
        }
        .plan-popular-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.375rem 0.75rem;
            border-radius: 50rem;
        }
    </style>
@endsection
