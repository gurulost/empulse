<?php

namespace App\Http\Controllers;

use App\Models\BillingAdminTransferRequest;
use App\Models\Companies;
use App\Models\OrganizationBillingAdmin;
use App\Models\User;
use App\Services\OrganizationEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(protected OrganizationEntitlementService $entitlements)
    {
        $this->middleware('auth');
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
            Log::warning('Stripe billing setup failed', ['exception_class' => $e::class]);
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
            'billingAdmins' => OrganizationBillingAdmin::with('user')
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->orderByRaw("CASE role WHEN 'owner' THEN 0 ELSE 1 END")
                ->get(),
            'billingAdminCandidates' => User::where('company_id', $company->id)
                ->where('status', 'active')
                ->whereNotIn(
                    'id',
                    OrganizationBillingAdmin::where('company_id', $company->id)
                        ->where('status', 'active')
                        ->whereNull('revoked_at')
                        ->pluck('user_id')
                )
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
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
                'exception_class' => $exception::class,
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
                'exception_class' => $exception::class,
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
        try {
            $transfer = $this->entitlements->initiateBillingOwnerTransfer(
                $company,
                $request->user(),
                User::findOrFail($data['to_user_id']),
                $data['reason']
            );
        } catch (\DomainException) {
            abort(403, 'Billing owner authorization is required.');
        }

        return back()->with(
            'status',
            "Transfer requested. The proposed owner must accept by {$transfer->expires_at->toDayDateTimeString()}."
        );
    }

    public function addBillingAdmin(Request $request)
    {
        $company = $this->billingCompany($request->user());
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        try {
            $this->entitlements->grantBillingAdmin(
                $company,
                $request->user(),
                User::findOrFail($data['user_id'])
            );
        } catch (\DomainException) {
            abort(403, 'Billing owner authorization is required.');
        }

        return back()->with('status', 'Billing administrator approved.');
    }

    public function removeBillingAdmin(
        Request $request,
        OrganizationBillingAdmin $billingAdmin
    ) {
        $company = $this->billingCompany($request->user());
        try {
            $this->entitlements->revokeBillingAdmin(
                $company,
                $request->user(),
                $billingAdmin
            );
        } catch (\DomainException) {
            abort(403, 'Billing owner authorization is required.');
        }

        return back()->with('status', 'Billing administrator access revoked.');
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
        try {
            $transfer = $this->entitlements->decideBillingOwnerTransfer(
                $transfer,
                $request->user(),
                $data['decision'] === 'accept'
            );
        } catch (\DomainException) {
            abort(403, 'This billing ownership transfer is not available.');
        }

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
        abort_unless($user && $user->company_id !== null, 403);
        $company = Companies::findOrFail($user->company_id);
        abort_unless($this->entitlements->isBillingAdmin($user, $company), 403);

        return $company;
    }
}
