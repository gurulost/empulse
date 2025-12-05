<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use App\Models\User;


class PlanController extends Controller
{
    public function stripePay()
    {
        $user = auth()->user();
        abort_unless($user && (int)$user->role === 1 && $user->company_id !== null, 403);
        $plans = Plan::get();

        return view("stripe.plans", compact("plans"));
    }

    public function show(Plan $plan, Request $request)
    {
        $user = auth()->user();
        abort_unless($user && (int)$user->role === 1 && $user->company_id !== null, 403);
        $intent = $user->createSetupIntent();

        return view("subscription.subscription", compact("plan", "intent"));
    }

    public function subscription(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && (int)$user->role === 1 && $user->company_id !== null, 403);
        $plan = Plan::find($request->plan);

        // Create or update the user's default subscription
        $subscription = $request->user()->newSubscription('default', $plan->stripe_plan)->create($request->token);

        return view("subscription.subscription_success");
    }
}
