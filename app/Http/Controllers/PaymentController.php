<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class PaymentController extends Controller
{
    public function payment()
    {
        $stripeSecret = config('services.stripe.secret');
        
        if (empty($stripeSecret)) {
            Log::warning('Stripe payment attempted without API key configured', [
                'user_id' => Auth::id(),
            ]);
            return view('errors.payment')->with('message', 'Payment system is not configured.');
        }
        
        try {
            $stripe = new \Stripe\StripeClient($stripeSecret);
            
            $checkoutSessionManagement = $stripe->checkout->sessions->create([
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
            
            return view('payment', [
                'checkoutSessionId' => $checkoutSessionManagement->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe checkout session creation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return view('errors.payment')->with('message', 'Failed to initialize payment session.');
        }
    }

    public function payment_success()
    {
        try {
            $updateCompanyTariff = DB::table("users")->where("company_title", Auth::user()->company_title)->update(["tariff" => 1]);
            return redirect()->route('home');
        } catch(\Exception $e) {
            return view('errors.payment');
        }
    }

    public function payment_error() {
        return view('errors.payment');
    }

    public function responses_error() {
        return view('errors.payment');
    }
}
