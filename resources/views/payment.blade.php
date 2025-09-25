@extends('layouts.app')
@section('title')
    Our offers
@endsection
@section('content')
    <?php

    require_once __DIR__.'/../../../vendor/autoload.php';

    $stripe_secret = env('STRIPE_SECRET');
    $stripe = new \Stripe\StripeClient("$stripe_secret");

    $checkout_session_management = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Management Subscription',
                ],
                'unit_amount' => 19900,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => route('payment-success'),
        'cancel_url' => route('payment_error'),
    ]);

    ?>



            <!-- content -->
    <div>
        <div class="row row-cols-1row-cols-md-2 text-center p-5">

            <div class="col">
                <div class="payment-card card mb-4 border rounded-3 shadow-sm border-white border">
                    <div class="card-header py-3">
                        <h4 class="my-0 fw-normal">Management Subscription</h4>
                        {{--                            <h5 class="my-0 fw-normal">10 assessments/month</h5>--}}
                    </div>
                    <div class="card-body">
                        {{--
                                                    <h1 class="card-title pricing-card-title">$199<small class="text-muted fw-light">/mo</small></h1>
                        --}}
                        <h1 class="card-title pricing-card-title">$199</h1>
                        {{--                            <ul class="list-unstyled mt-3 mb-4">
                                                        <li>Up to 5 WorkFit DxR assessments per month (an 80% discount)</li>
                                                        <li>Additional assessments at only $10</li>
                                                        <li>3 comparison reports per month</li>
                                                        <li>Additional reports are only $10</li>
                                                        <li>1 Position-Fit Matrix per month</li>
                                                        <li>Additional PFM are only $45</li>
                                                        <li>Total Savings: $160 every month</li>
                                                    </ul>--}}
                        <ul class="list-unstyled mt-3 mb-4">
                            <li>Ability to see company productivity through graphs</li>
                            <li></li>
                            <li></li>
                            <li></li>
                            <li></li>
                            <li></li>
                            <li></li>
                        </ul>
                        @guest

                            @if(Route::has('login') && Route::has('register'))
                                <button type="button" class="w-100 btn btn-lg btn-success checkout-management" disabled>Pay right now!</button>
                            @endif
                        @else
                            <button type="button" class="w-100 btn btn-lg btn-warning checkout-management">Pay right now!</button>

                        @endguest
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="payment-card card mb-4 border rounded-3 shadow-sm border-white border">
                    <div class="card-header py-3">
                        <h4 class="my-0 fw-normal">Enterprise Subscription</h4>
                        {{--                            <h5 class="my-0 fw-normal">100+ assessments/month</h5>--}}
                    </div>
                    <div class="card-body">
                        <h1 class="card-title pricing-card-title">Need more?</h1>
                        <ul class="list-unstyled mt-3 mb-4">
                            <li>Contact us for even lower prices based on your needs</li>
                        </ul>
                        <a href="/contuctUs" type="button" class="w-100 btn btn-lg btn-light">Contact us!</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- end content -->



    <!-- stripe -->

    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">

        $(document).ready(function() {

            const stripe = Stripe("{{env('STRIPE_KEY')}}");
            $(".checkout-management").on("click", function(e) {
                e.preventDefault();
                stripe.redirectToCheckout({
                    sessionId: "<?php echo $checkout_session_management->id ?>"
                });
            });
        });

    </script>

    <!-- end stripe -->

@endsection
