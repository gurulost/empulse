<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $intent = null;
        $activeSubscription = null;
        $checkoutAvailable = $this->stripeCheckoutConfigured();
        $portalAvailable = $this->stripeServerConfigured();

        try {
            if ($portalAvailable) {
                $activeSubscription = $user->subscriptions()->active()->first();
            }

            if ($checkoutAvailable) {
                $intent = $user->createSetupIntent();
            }
        } catch (\Exception $e) {
            Log::warning('Stripe billing setup failed', ['error' => $e->getMessage()]);
        }

        return view('billing.index', [
            'user' => $user,
            'intent' => $intent,
            'subscription' => $activeSubscription,
            'checkoutAvailable' => $checkoutAvailable,
            'portalAvailable' => $portalAvailable,
        ]);
    }

    public function updatePaymentMethod(Request $request)
    {
        if (!$this->stripeCheckoutConfigured()) {
            return back()->withErrors('Stripe is not configured for this environment.');
        }

        $request->validate(['token' => 'required|string']);

        try {
            $request->user()->updateDefaultPaymentMethod($request->token);
        } catch (\Throwable $exception) {
            Log::warning('Stripe payment method update failed', [
                'user_id' => $request->user()->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors('Payment method could not be updated right now. Please try again or use the billing portal.');
        }

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
        if (!$this->stripeServerConfigured()) {
            return back()->withErrors('Stripe billing portal is unavailable in this environment.');
        }

        try {
            $user = $request->user();
            $user->createOrGetStripeCustomer();

            return $user->redirectToBillingPortal(route('billing.index'));
        } catch (\Throwable $exception) {
            Log::warning('Stripe billing portal redirect failed', [
                'user_id' => $request->user()->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors('Billing portal could not be opened right now. Please try again in a moment.');
        }
    }

    protected function stripeCheckoutConfigured(): bool
    {
        return filled(config('services.stripe.key')) && filled(config('services.stripe.secret'));
    }

    protected function stripeServerConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }
}
