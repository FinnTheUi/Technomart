<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\OnlinePaymentService;
use App\Services\Billing\PayMongoGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives PayMongo events. Requests are authenticated by HMAC signature, not session/CSRF.
 */
class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoGateway $gateway, OnlinePaymentService $payments): JsonResponse
    {
        $payload = $request->getContent();

        if (! $gateway->verifySignature($payload, $request->header('Paymongo-Signature'))) {
            Log::warning('Rejected PayMongo webhook with an invalid signature.', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $handled = $payments->handleEvent(json_decode($payload, true) ?? []);

        // Always 200 for authentic events so PayMongo doesn't retry ones we intentionally ignore.
        return response()->json(['received' => true, 'handled' => $handled]);
    }
}
