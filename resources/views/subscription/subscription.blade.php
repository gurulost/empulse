@extends('layouts.app')

@section('content')
    @php
        $price = (float) $plan->price;
        if ($price >= 1000) {
            $price = $price / 100;
        }
    @endphp

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-1">Confirm {{ $plan->name }}</h4>
                        <p class="text-muted mb-0">Billing is handled through Stripe and synced back through Cashier webhook events.</p>
                    </div>

                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="mb-4">
                            <div class="small text-uppercase text-muted fw-bold">{{ $planMeta['eyebrow'] ?? 'Selected plan' }}</div>
                            <div class="display-6 fw-bold">${{ number_format($price, $price == floor($price) ? 0 : 2) }}/month</div>
                            <p class="text-muted mb-0">{{ $planMeta['summary'] ?? $plan->description }}</p>
                        </div>

                        @if($hasActiveSubscription)
                            <div class="alert alert-info">
                                This account already has an active subscription. Manage upgrades, payment methods, and cancellations from the billing center.
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('billing.index') }}" class="btn btn-primary">Open Billing Center</a>
                                <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">Back to Plans</a>
                            </div>
                        @elseif(!$planPurchasable || !$intent)
                            <div class="alert alert-warning">
                                @if(!$billingAvailable)
                                    Billing is unavailable in this environment because Stripe is not configured. The rest of the app remains demo-safe, but checkout cannot be completed here.
                                @else
                                    This plan is not connected to a Stripe price yet, so checkout cannot be completed from this page.
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">Back to Plans</a>
                                <a href="{{ route('billing.index') }}" class="btn btn-primary">Open Billing Center</a>
                            </div>
                        @else
                            <form id="payment-form" action="{{ route('subscription.create') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan" id="plan" value="{{ $plan->id }}">

                                <div class="row">
                                    <div class="col-xl-6 col-lg-6">
                                        <div class="form-group">
                                            <label for="">Name</label>
                                            <input type="text" name="name" id="card-holder-name" class="form-control" value="" placeholder="Name on the card">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-xl-8 col-lg-8">
                                        <div class="form-group">
                                            <label for="">Card details</label>
                                            <div id="card-element"></div>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12">
                                        <hr>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="submit" class="btn btn-primary" id="card-button" data-secret="{{ $intent->client_secret }}">Confirm Subscription</button>
                                            <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">Back to Plans</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($billingAvailable && $intent)
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripe = Stripe('{{ config('services.stripe.key') }}')

            const elements = stripe.elements()
            const cardElement = elements.create('card')

            cardElement.mount('#card-element')

            const form = document.getElementById('payment-form')
            const cardBtn = document.getElementById('card-button')
            const cardHolderName = document.getElementById('card-holder-name')

            form.addEventListener('submit', async (e) => {
                e.preventDefault()

                cardBtn.disabled = true
                const { setupIntent, error } = await stripe.confirmCardSetup(
                    cardBtn.dataset.secret,
                    {
                        payment_method: {
                            card: cardElement,
                            billing_details: {
                                name: cardHolderName.value
                            }
                        }
                    }
                )

                if (error) {
                    alert(error.message)
                    cardBtn.disabled = false
                    return
                }

                const token = document.createElement('input')
                token.setAttribute('type', 'hidden')
                token.setAttribute('name', 'token')
                token.setAttribute('value', setupIntent.payment_method)
                form.appendChild(token)
                form.submit()
            })
        </script>
    @endif
@endsection
