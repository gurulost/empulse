@extends('layouts.app')

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
                <h1 class="display-6 fw-bold">Plans & Billing</h1>
                <p class="text-muted mb-0">
                    Choose the subscription that matches how you want to run Empulse in production and demos.
                </p>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if(!$billingAvailable)
            <div class="alert alert-warning">
                Billing is unavailable in this environment because Stripe is not configured. You can still review plan details and demo the billing pages safely.
            </div>
        @endif

        <div class="row g-4 align-items-stretch">
            @foreach($plans as $plan)
                @php
                    $meta = $planMetaBySlug[$plan->slug] ?? [];
                @endphp
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="small text-uppercase text-muted fw-bold mb-2">{{ $meta['eyebrow'] ?? 'Subscription plan' }}</div>
                            <h2 class="h4 fw-bold mb-2">{{ $plan->name }}</h2>
                            <div class="display-6 fw-bold mb-2">${{ $formatPrice($plan->price) }}</div>
                            <div class="text-muted mb-4">per month</div>

                            <p class="text-muted">{{ $meta['summary'] ?? $plan->description }}</p>

                            <ul class="list-unstyled flex-grow-1 mb-4">
                                @foreach(($meta['features'] ?? []) as $feature)
                                    <li class="mb-2">
                                        <i class="bi bi-check2-circle text-success me-2"></i>{{ $feature }}
                                    </li>
                                @endforeach
                            </ul>

                            <a
                                href="{{ route('plans.show', $plan) }}"
                                class="btn {{ $billingAvailable ? 'btn-primary' : 'btn-outline-secondary' }} w-100"
                            >
                                {{ $billingAvailable ? ($meta['cta'] ?? 'Continue') : 'Review Details' }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
