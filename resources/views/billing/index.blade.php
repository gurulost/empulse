@extends('layouts.app')
@section('title')
    Account & Billing
@endsection

@section('content')
    <div class="container py-4">
        <div class="page-header">
            <h1 class="page-title">Account & Billing</h1>
            <p class="page-subtitle">Manage your subscription and payment details.</p>
        </div>

        @php
            $stripeKey = config('services.stripe.key');
            $canUpdateCard = !empty($stripeKey) && $intent;
        @endphp

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

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-credit-card-2-back text-primary"></i>
                            <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.0625rem;">Subscription</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        @php
                            $sub = $subscription;
                            $active = $sub && $sub->valid();
                            $onGrace = $sub && $sub->onGracePeriod();
                        @endphp

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="stat-label mb-0">Status</span>
                            <span class="badge rounded-pill px-3 py-2 {{ $active ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' }}">
                                {{ $active ? 'Active' : 'Not Active' }}
                            </span>
                            @if($onGrace)
                                <span class="badge rounded-pill px-3 py-2 bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Grace Period</span>
                            @endif
                        </div>

                        <div class="d-flex flex-column gap-2 mb-4">
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Stripe Price</span>
                                <span class="fw-semibold">{{ $sub?->stripe_price ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Renews</span>
                                <span class="fw-semibold">{{ $sub?->ends_at ? $sub->ends_at->toDateString() : 'Auto-renew' }}</span>
                            </div>
                        </div>

                        @if($portalAvailable)
                            <form action="{{ route('billing.portal') }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="submit">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Open Stripe Portal
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning py-2 px-3 small mb-0 rounded-3 border-0">
                                <i class="bi bi-info-circle me-1"></i>Billing portal unavailable until Stripe is configured.
                            </div>
                        @endif

                        <hr class="my-3" style="border-color: #f1f5f9;">

                        @if($active && !$onGrace)
                            <form action="{{ route('billing.cancel') }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm rounded-pill px-3" type="submit">
                                    <i class="bi bi-x-circle me-1"></i>Cancel Subscription
                                </button>
                            </form>
                        @elseif($onGrace)
                            <form action="{{ route('billing.resume') }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-primary btn-sm rounded-pill px-3" type="submit">
                                    <i class="bi bi-play-fill me-1"></i>Resume Subscription
                                </button>
                            </form>
                        @else
                            <a href="{{ route('plans.index') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="bi bi-arrow-right me-1"></i>Choose a Plan
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-wallet2 text-primary"></i>
                            <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.0625rem;">Payment Method</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="background: #f8fafc;">
                            <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 48px; height: 48px; background: #fff; border: 1px solid #e2e8f0;">
                                <i class="bi bi-credit-card fs-5 text-muted"></i>
                            </div>
                            <div>
                                @if($user->pm_last_four)
                                    <div class="fw-semibold">{{ strtoupper($user->pm_type ?? 'card') }} &bull;&bull;&bull;&bull; {{ $user->pm_last_four }}</div>
                                    <div class="small text-muted">Default payment method</div>
                                @else
                                    <div class="fw-semibold text-muted">No card on file</div>
                                    <div class="small text-muted">Add a payment method below</div>
                                @endif
                            </div>
                        </div>

                        @if($canUpdateCard)
                            <form id="payment-method-form" action="{{ route('billing.payment_method') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Update Card</label>
                                    <div id="card-element" class="form-control py-3" style="min-height: 44px;"></div>
                                </div>
                                <button id="card-button" class="btn btn-primary btn-sm rounded-pill px-3" type="submit" data-secret="{{ $intent->client_secret }}">
                                    <i class="bi bi-shield-check me-1"></i>Save Payment Method
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0 rounded-3 border-0 small">
                                <i class="bi bi-info-circle me-1"></i>Card updates unavailable until Stripe is configured.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canUpdateCard)
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripe = Stripe('{{ $stripeKey }}')
            const elements = stripe.elements()
            const cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '15px',
                        fontFamily: '"DM Sans", sans-serif',
                        color: '#334155',
                        '::placeholder': { color: '#94a3b8' }
                    }
                }
            })
            cardElement.mount('#card-element')

            const form = document.getElementById('payment-method-form')
            const cardBtn = document.getElementById('card-button')
            form.addEventListener('submit', async (e) => {
                e.preventDefault()
                cardBtn.disabled = true
                const { setupIntent, error } = await stripe.confirmCardSetup(
                    cardBtn.dataset.secret, { payment_method: { card: cardElement } }
                )
                if (error) {
                    alert(error.message)
                    cardBtn.disabled = false
                    return
                }
                let token = document.createElement('input')
                token.setAttribute('type', 'hidden')
                token.setAttribute('name', 'token')
                token.setAttribute('value', setupIntent.payment_method)
                form.appendChild(token)
                form.submit()
            })
        </script>
    @endif
@endsection
