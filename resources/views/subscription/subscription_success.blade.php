@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Subscription Received</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            Checkout completed. Billing status will finalize after Stripe confirms the subscription webhook.
                        </div>
                        <p class="text-muted">
                            You can review your payment method, cancel or resume service, and open the Stripe billing portal from the billing center.
                        </p>
                        <div class="d-flex gap-2">
                            <a href="{{ route('billing.index') }}" class="btn btn-primary">Open Billing Center</a>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary">Return to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
