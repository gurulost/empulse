@extends('layouts.app')
@section('title')
    Account & Billing
@endsection

@section('content')
    <div class="container py-4">
        <h3 class="mb-3">Account & Billing</h3>
        @php
            $stripeKey = config('services.stripe.key');
            $canUpdateCard = !empty($stripeKey) && $intent;
        @endphp

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Subscription</div>
                    <div class="card-body">
                        @php
                            $sub = $subscription;
                            $active = $sub && $sub->valid();
                            $onGrace = $sub && $sub->onGracePeriod();
                        @endphp

                        <p class="mb-1"><strong>Status:</strong>
                            <span class="badge {{ $active ? 'bg-success' : 'bg-danger' }}">
                                {{ $active ? 'Active' : 'Not Active' }}
                            </span>
                            @if($onGrace)
                                <span class="badge bg-warning text-dark">Grace Period</span>
                            @endif
                        </p>
                        <p class="mb-1"><strong>Stripe Price:</strong> {{ $sub?->stripe_price ?? '—' }}</p>
                        <p class="mb-1"><strong>Renews:</strong> {{ $sub?->ends_at ? $sub->ends_at->toDateString() : 'Auto-renew' }}</p>

                        <form action="{{ route('billing.portal') }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm" type="submit">Open Stripe Billing Portal</button>
                        </form>

                        <div class="mt-3">
                            @if($active && !$onGrace)
                                <form action="{{ route('billing.cancel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Cancel Subscription</button>
                                </form>
                            @elseif($onGrace)
                                <form action="{{ route('billing.resume') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-primary btn-sm" type="submit">Resume Subscription</button>
                                </form>
                            @else
                                <a href="{{ route('plans.index') }}" class="btn btn-primary btn-sm">Choose a Plan</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Payment Method</div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Card:</strong>
                            @if($user->pm_last_four)
                                {{ strtoupper($user->pm_type ?? 'card') }} •••• {{ $user->pm_last_four }}
                            @else
                                None on file
                            @endif
                        </p>

                        @if($canUpdateCard)
                            <form id="payment-method-form" action="{{ route('billing.payment_method') }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Update Card</label>
                                    <div id="card-element"></div>
                                </div>
                                <button id="card-button" class="btn btn-primary btn-sm" type="submit" data-secret="{{ $intent->client_secret }}">
                                    Save Payment Method
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">
                                Card updates are unavailable until Stripe is configured for this environment.
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
            const cardElement = elements.create('card')
            cardElement.mount('#card-element')

            const form = document.getElementById('payment-method-form')
            const cardBtn = document.getElementById('card-button')
            form.addEventListener('submit', async (e) => {
                e.preventDefault()
                cardBtn.disabled = true
                const { setupIntent, error } = await stripe.confirmCardSetup (
                    cardBtn.dataset.secret, {
                        payment_method: {
                            card: cardElement,
                        }
                    }
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
