<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $intent = $user->createSetupIntent();
        $activeSubscription = $user->subscriptions()->active()->first();

        return view('billing.index', [
            'user' => $user,
            'intent' => $intent,
            'subscription' => $activeSubscription,
        ]);
    }

    public function updatePaymentMethod(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        $request->user()->updateDefaultPaymentMethod($request->token);
        return back()->with('status', 'Payment method updated.');
    }

    public function cancel(Request $request)
    {
        $sub = $request->user()->subscriptions()->active()->first();
        if ($sub) {
            $sub->cancel();
        }
        return back()->with('status', 'Subscription cancelled.');
    }

    public function resume(Request $request)
    {
        $sub = $request->user()->subscriptions()->onGracePeriod()->first();
        if ($sub) {
            $sub->resume();
        }
        return back()->with('status', 'Subscription resumed.');
    }

    public function portal(Request $request)
    {
        // Redirect to Stripe Billing Portal
        return $request->user()->redirectToBillingPortal(route('billing.index'));
    }
}

