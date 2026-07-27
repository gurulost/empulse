@extends('layouts.app')

@section('title', 'Billing Ownership Transfer')

@section('content')
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 680px;">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3">Billing ownership transfer</h1>
            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            <p class="text-muted">You have been asked to become the billing owner for organization {{ $transfer->company_id }}.</p>
            <dl class="row">
                <dt class="col-sm-4">Reason</dt><dd class="col-sm-8">{{ $transfer->reason }}</dd>
                <dt class="col-sm-4">Expires</dt><dd class="col-sm-8">{{ $transfer->expires_at->toDayDateTimeString() }}</dd>
                <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ $transfer->status }}</dd>
            </dl>
            @if($transfer->status === 'pending' && $transfer->expires_at->isFuture())
                <form method="POST" action="{{ route('billing.transfer.decide', $transfer) }}" class="d-flex gap-2">
                    @csrf
                    <button name="decision" value="accept" class="btn btn-primary">Accept ownership</button>
                    <button name="decision" value="reject" class="btn btn-outline-secondary">Reject</button>
                </form>
            @endif
        </div>
    </div>
@endsection
