<?php

namespace App\Services\Billing;

use App\Support\EncryptedSecret;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for the PayMongo Checkout API (GCash, Maya, cards).
 *
 * @see https://developers.paymongo.com/reference/checkout-session-resource
 */
class PayMongoGateway
{
    private const SECRET_KEY = 'services.paymongo.secret_key_encrypted';

    private const WEBHOOK_KEY = 'services.paymongo.webhook_secret_encrypted';

    public function isEnabled(): bool
    {
        return EncryptedSecret::isSet(self::SECRET_KEY) && EncryptedSecret::isSet(self::WEBHOOK_KEY);
    }

    /**
     * Create a hosted checkout session.
     *
     * @param  array<string, string>  $metadata
     * @return array{id: string, checkout_url: string}
     */
    public function createCheckoutSession(
        float $amount,
        string $name,
        string $reference,
        array $metadata,
        string $successUrl,
        string $cancelUrl,
    ): array {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Online payments are not configured.');
        }

        try {
            $response = Http::withBasicAuth(EncryptedSecret::decrypt(self::SECRET_KEY), '')
                ->acceptJson()
                ->timeout(15)
                ->post(rtrim(config('services.paymongo.base_url'), '/').'/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'line_items' => [[
                                'currency' => 'PHP',
                                'amount' => (int) round($amount * 100), // centavos
                                'name' => mb_substr($name, 0, 255),
                                'quantity' => 1,
                            ]],
                            'payment_method_types' => array_values(config('services.paymongo.payment_methods')),
                            'description' => mb_substr($name, 0, 255),
                            'reference_number' => $reference,
                            'metadata' => $metadata,
                            'success_url' => $successUrl,
                            'cancel_url' => $cancelUrl,
                            'send_email_receipt' => false,
                            'show_description' => true,
                            'show_line_items' => true,
                        ],
                    ],
                ])
                ->throw();
        } catch (RequestException $e) {
            report($e);

            throw new RuntimeException('Could not start the online payment. Please try again or pay via GCash reference.');
        }

        return [
            'id' => (string) $response->json('data.id'),
            'checkout_url' => (string) $response->json('data.attributes.checkout_url'),
        ];
    }

    /**
     * Verify the Paymongo-Signature header: "t=<timestamp>,te=<test sig>,li=<live sig>".
     */
    public function verifySignature(string $payload, ?string $header): bool
    {
        if (! $this->isEnabled() || blank($header)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            $parts[$key] = $value;
        }

        $timestamp = (int) ($parts['t'] ?? 0);

        if ($timestamp === 0 || abs(time() - $timestamp) > config('services.paymongo.webhook_tolerance', 300)) {
            return false;
        }

        $livemode = (bool) data_get(json_decode($payload, true), 'data.attributes.livemode', false);
        $signature = $parts[$livemode ? 'li' : 'te'] ?? '';
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, EncryptedSecret::decrypt(self::WEBHOOK_KEY));

        return $signature !== '' && hash_equals($expected, $signature);
    }
}
