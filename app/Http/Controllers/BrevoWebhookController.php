<?php

namespace App\Http\Controllers;

use App\Services\DeliveryTrustService;
use Illuminate\Http\Request;

class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request, DeliveryTrustService $delivery)
    {
        $expected = (string) config('services.brevo.webhook_token');
        $provided = (string) $request->bearerToken();
        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            abort(401);
        }

        $payloads = array_is_list($request->json()->all())
            ? $request->json()->all()
            : [$request->json()->all()];
        foreach ($payloads as $payload) {
            if (is_array($payload)) {
                $delivery->ingestProviderEvent($payload);
            }
        }

        return response()->json(['status' => 'accepted']);
    }
}
