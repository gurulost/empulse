<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use App\Support\SurveyWaveAutomation;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function handleInvoicePaymentSucceeded(array $payload)
    {
        $this->syncCompanyTariffForCustomer(data_get($payload, 'data.object.customer'));
        return $this->successMethod();
    }

    public function handleInvoicePaymentFailed(array $payload)
    {
        $this->syncCompanyTariffForCustomer(data_get($payload, 'data.object.customer'));
        return $this->successMethod();
    }

    public function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);
        $this->syncCompanyTariffForCustomer(data_get($payload, 'data.object.customer'));
        return $response;
    }

    public function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        $this->syncCompanyTariffForCustomer(data_get($payload, 'data.object.customer'));
        return $response;
    }

    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);
        $this->syncCompanyTariffForCustomer(data_get($payload, 'data.object.customer'));
        return $response;
    }

    protected function syncCompanyTariffForCustomer(?string $customerId): void
    {
        if (!$customerId) {
            return;
        }

        $user = User::where('stripe_id', $customerId)->first();
        if (!$user || !$user->company_id) {
            return;
        }

        $activeStatuses = SurveyWaveAutomation::allowedBillingStatuses();
        $hasPaidSubscription = $user->subscriptions()
            ->whereIn('stripe_status', $activeStatuses)
            ->exists();

        DB::table('users')
            ->where('company_id', $user->company_id)
            ->update(['tariff' => $hasPaidSubscription ? 1 : 0]);
    }
}
