<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Mark the entire company as paid when invoice payment succeeds.
     */
    public function handleInvoicePaymentSucceeded(array $payload)
    {
        $customerId = data_get($payload, 'data.object.customer');
        if (!$customerId) {
            return $this->successMethod();
        }

        $user = User::where('stripe_id', $customerId)->first();
        if (!$user || !$user->company_id) {
            return $this->successMethod();
        }

        DB::table('users')->where('company_id', $user->company_id)->update(['tariff' => 1]);
        return $this->successMethod();
    }
}

