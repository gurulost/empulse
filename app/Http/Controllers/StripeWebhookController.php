<?php

namespace App\Http\Controllers;

use App\Models\BillingWebhookEvent;
use App\Models\Companies;
use App\Services\OrganizationEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload) || empty($payload['id']) || empty($payload['type'])) {
            return new Response('Invalid webhook payload.', 400);
        }

        $payloadHash = hash('sha256', $request->getContent());
        $customerId = data_get($payload, 'data.object.customer');
        $company = $customerId
            ? Companies::where('stripe_id', $customerId)->first()
            : null;
        $eventCreatedAt = isset($payload['created'])
            ? Carbon::createFromTimestamp((int) $payload['created'])
            : now();
        $event = BillingWebhookEvent::firstOrCreate(
            ['stripe_event_id' => $payload['id']],
            [
                'company_id' => $company?->id,
                'event_type' => $payload['type'],
                'payload_hash' => $payloadHash,
                'stripe_created_at' => $eventCreatedAt,
                'status' => 'processing',
            ]
        );

        if (! hash_equals($event->payload_hash, $payloadHash)) {
            return new Response('Webhook event identity conflict.', 409);
        }
        if (in_array($event->status, ['processed', 'ignored_stale'], true)) {
            return $this->successMethod();
        }

        if ($company) {
            $lastEventAt = app(OrganizationEntitlementService::class)
                ->current($company)?->last_stripe_event_at;
            if ($lastEventAt && $eventCreatedAt->lt($lastEventAt)) {
                $event->update([
                    'status' => 'ignored_stale',
                    'processed_at' => now(),
                ]);

                return $this->successMethod();
            }
        }

        try {
            $response = parent::handleWebhook($request);
            if ($company) {
                app(OrganizationEntitlementService::class)
                    ->syncFromStripe($company->fresh(), $eventCreatedAt);
            }
            $event->update([
                'company_id' => $company?->id,
                'status' => 'processed',
                'processed_at' => now(),
                'error' => null,
            ]);

            return $response;
        } catch (\Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            throw $exception;
        }
    }

    public function handleInvoicePaymentSucceeded(array $payload)
    {
        return $this->successMethod();
    }

    public function handleInvoicePaymentFailed(array $payload)
    {
        return $this->successMethod();
    }

    public function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        return $response;
    }

    public function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        return $response;
    }

    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        return $response;
    }
}
