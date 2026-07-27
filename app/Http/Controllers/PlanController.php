<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use App\Models\Plan;
use App\Models\User;
use App\Services\OrganizationEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    public function __construct(protected OrganizationEntitlementService $entitlements) {}

    public function stripePay()
    {
        $user = auth()->user();
        $this->billingCompany($user);
        $plans = Plan::query()->orderBy('price')->get();

        return view('stripe.plans', [
            'plans' => $plans,
            'planMetaBySlug' => config('billing.plan_marketing', []),
            'billingAvailable' => $this->stripeIsConfigured(),
        ]);
    }

    public function show(Plan $plan, Request $request)
    {
        $user = auth()->user();
        $company = $this->billingCompany($user);
        $intent = null;
        $billingAvailable = $this->stripeIsConfigured();
        $planPurchasable = $billingAvailable && filled($plan->stripe_plan);
        $hasActiveSubscription = (bool) optional($company->subscription('default'))->valid();

        if ($planPurchasable && ! $hasActiveSubscription) {
            try {
                $intent = $company->createSetupIntent();
            } catch (\Throwable $exception) {
                Log::warning('Stripe setup intent could not be created', [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'error' => $exception->getMessage(),
                ]);
                $billingAvailable = false;
            }
        }

        return view('subscription.subscription', [
            'plan' => $plan,
            'intent' => $intent,
            'billingAvailable' => $billingAvailable,
            'planPurchasable' => $planPurchasable,
            'hasActiveSubscription' => $hasActiveSubscription,
            'planMeta' => config("billing.plan_marketing.{$plan->slug}", []),
        ]);
    }

    public function subscription(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $company = $this->billingCompany($user);
        $request->validate([
            'plan' => 'required|exists:plans,id',
            'token' => 'required|string',
        ]);

        $plan = Plan::findOrFail($request->plan);

        if (! $this->stripeIsConfigured() || ! $plan->stripe_plan) {
            return redirect()
                ->route('plans.show', $plan)
                ->withErrors('Billing is unavailable in this environment until Stripe is configured.');
        }

        if (optional($company->subscription('default'))->valid()) {
            return redirect()
                ->route('billing.index')
                ->with('status', 'An active subscription is already on this account. Manage it from the billing center.');
        }

        try {
            $catalogPlan = config("billing.catalog.{$plan->slug}");
            if (! is_array($catalogPlan)
                || blank($catalogPlan['stripe_price'] ?? null)
                || $catalogPlan['stripe_price'] !== $plan->stripe_plan) {
                return redirect()
                    ->route('plans.show', $plan)
                    ->withErrors('This plan is not reconciled with the production catalog.');
            }
            $company->newSubscription('default', $plan->stripe_plan)->create($request->token);
        } catch (\Throwable $exception) {
            Log::warning('Stripe subscription checkout failed', [
                'user_id' => $request->user()->id,
                'plan_id' => $plan->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('plans.show', $plan)
                ->withErrors('Checkout could not be completed right now. Please verify your payment details or use the billing portal.');
        }

        return redirect()
            ->route('billing.index')
            ->with('status', 'Checkout completed. Billing access and plan status will update after Stripe confirms the subscription.');
    }

    protected function stripeIsConfigured(): bool
    {
        return filled(config('services.stripe.key')) && filled(config('services.stripe.secret'));
    }

    protected function billingCompany(?User $user): Companies
    {
        abort_unless($user && $user->company_id !== null, 403);
        $company = Companies::findOrFail($user->company_id);
        abort_unless($this->entitlements->isBillingAdmin($user, $company), 403);

        return $company;
    }
}
