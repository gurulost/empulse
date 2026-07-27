<?php

namespace App\Http\Controllers;

use App\Models\BillingAdminTransferRequest;
use App\Models\Companies;
use App\Models\User;
use App\Services\OrganizationEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(protected OrganizationEntitlementService $entitlements)
    {
        $this->middleware('auth');
        $this->middleware('capability:billing.manage')->except(['showTransfer', 'decideTransfer']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $company = $this->billingCompany($user);
        $intent = null;
        $activeSubscription = null;
        $checkoutAvailable = $this->stripeCheckoutConfigured();
        $portalAvailable = $this->stripeServerConfigured();

        try {
            if ($portalAvailable) {
                $activeSubscription = $company->subscriptions()->active()->first();
            }

            if ($checkoutAvailable) {
                $intent = $company->createSetupIntent();
            }
        } catch (\Exception $e) {
            Log::warning('Stripe billing setup failed', ['error' => $e->getMessage()]);
        }

        return view('billing.index', [
            'user' => $user,
            'company' => $company,
            'entitlement' => $this->entitlements->current($company),
            'intent' => $intent,
            'subscription' => $activeSubscription,
            'checkoutAvailable' => $checkoutAvailable,
            'portalAvailable' => $portalAvailable,
            'usage' => $this->entitlements->usageSummary($company),
            'billingOwner' => $this->entitlements->isBillingOwner($user, $company),
            'transferCandidates' => User::where('company_id', $company->id)
                ->where('status', 'active')
                ->whereKeyNot($user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'pendingTransfer' => BillingAdminTransferRequest::where('company_id', $company->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first(),
        ]);
    }

    public function updatePaymentMethod(Request $request)
    {
        if (! $this->stripeCheckoutConfigured()) {
            return back()->withErrors('Stripe is not configured for this environment.');
        }

        $request->validate(['token' => 'required|string']);

        try {
            $company = $this->billingCompany($request->user());
            $company->updateDefaultPaymentMethod($request->token);
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
        $company = $this->billingCompany($request->user());
        $sub = $company->subscriptions()->active()->first();
        if ($sub) {
            $sub->cancel();
        }

        return back()->with('status', 'Subscription cancelled.');
    }

    public function resume(Request $request)
    {
        $company = $this->billingCompany($request->user());
        $sub = $company->subscriptions()->onGracePeriod()->first();
        if ($sub) {
            $sub->resume();
        }

        return back()->with('status', 'Subscription resumed.');
    }

    public function portal(Request $request)
    {
        if (! $this->stripeServerConfigured()) {
            return back()->withErrors('Stripe billing portal is unavailable in this environment.');
        }

        try {
            $company = $this->billingCompany($request->user());
            $company->createOrGetStripeCustomer();

            return $company->redirectToBillingPortal(route('billing.index'));
        } catch (\Throwable $exception) {
            Log::warning('Stripe billing portal redirect failed', [
                'user_id' => $request->user()->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors('Billing portal could not be opened right now. Please try again in a moment.');
        }
    }

    public function initiateTransfer(Request $request)
    {
        $company = $this->billingCompany($request->user());
        $data = $request->validate([
            'to_user_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|max:2000',
        ]);
        $transfer = $this->entitlements->initiateBillingOwnerTransfer(
            $company,
            $request->user(),
            User::findOrFail($data['to_user_id']),
            $data['reason']
        );

        return back()->with(
            'status',
            "Transfer requested. The proposed owner must accept by {$transfer->expires_at->toDayDateTimeString()}."
        );
    }

    public function showTransfer(Request $request, BillingAdminTransferRequest $transfer)
    {
        abort_unless($transfer->to_user_id === $request->user()->id, 403);

        return view('billing.transfer', ['transfer' => $transfer]);
    }

    public function decideTransfer(Request $request, BillingAdminTransferRequest $transfer)
    {
        abort_unless($transfer->to_user_id === $request->user()->id, 403);
        $data = $request->validate(['decision' => 'required|in:accept,reject']);
        $transfer = $this->entitlements->decideBillingOwnerTransfer(
            $transfer,
            $request->user(),
            $data['decision'] === 'accept'
        );

        return redirect()->route('billing.transfer.show', $transfer)
            ->with('status', "Billing ownership transfer {$transfer->status}.");
    }

    protected function stripeCheckoutConfigured(): bool
    {
        return filled(config('services.stripe.key')) && filled(config('services.stripe.secret'));
    }

    protected function stripeServerConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    protected function billingCompany($user): Companies
    {
        $company = Companies::findOrFail($user->company_id);
        abort_unless($this->entitlements->isBillingAdmin($user, $company), 403);

        return $company;
    }
}
