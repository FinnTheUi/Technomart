<?php

namespace App\Services\Billing;

use App\Contracts\Payable;
use App\Enums\PaymentStatus;
use App\Models\RentalApplication;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Connects PayMongo checkout sessions to Rentelligence documents.
 * Payment is confirmed only by the signed webhook, never by the browser redirect.
 */
class OnlinePaymentService
{
    public const EVENT_PAID = 'checkout_session.payment.paid';

    public function __construct(
        private readonly PayMongoGateway $gateway,
        private readonly PaymentService $payments,
        private readonly PayableResolver $resolver,
    ) {}

    public function isEnabled(): bool
    {
        return $this->gateway->isEnabled();
    }

    /**
     * @param  Model&Payable  $payable
     */
    public function checkoutForPayable(Payable $payable, string $successUrl, string $cancelUrl): string
    {
        if (! $payable->canBePaid()) {
            throw new RuntimeException('This bill cannot be paid right now.');
        }

        $type = $payable::payableType();
        $session = $this->gateway->createCheckoutSession(
            $payable->amountDue(),
            $payable->paymentDescription(),
            "{$type}-{$payable->getKey()}",
            ['payable_type' => $type, 'payable_id' => (string) $payable->getKey()],
            $successUrl,
            $cancelUrl,
        );

        $payable->forceFill(['gateway_checkout_id' => $session['id']])->save();

        return $session['checkout_url'];
    }

    public function checkoutForApplication(RentalApplication $application, string $successUrl, string $cancelUrl): string
    {
        if (! $application->awaitingPayment()) {
            throw new RuntimeException('This application has already been paid or processed.');
        }

        $application->loadMissing('stall');

        $session = $this->gateway->createCheckoutSession(
            (float) $application->stall->price,
            'First month rent - '.$application->stall->name,
            "rental_application-{$application->id}",
            ['payable_type' => 'rental_application', 'payable_id' => (string) $application->id],
            $successUrl,
            $cancelUrl,
        );

        $application->forceFill(['gateway_checkout_id' => $session['id']])->save();

        return $session['checkout_url'];
    }

    /**
     * Handle a verified webhook event. Returns true when a document was updated.
     */
    public function handleEvent(array $event): bool
    {
        if (data_get($event, 'data.attributes.type') !== self::EVENT_PAID) {
            return false;
        }

        $session = data_get($event, 'data.attributes.data');
        $sessionId = (string) data_get($session, 'id');
        $type = (string) data_get($session, 'attributes.metadata.payable_type');
        $id = (string) data_get($session, 'attributes.metadata.payable_id');
        $paymentId = (string) (data_get($session, 'attributes.payments.0.id') ?? $sessionId);
        $paidCentavos = (int) collect(data_get($session, 'attributes.payments', []))->sum('attributes.amount');

        if ($type === 'rental_application') {
            return $this->settleApplication($id, $sessionId, $paymentId, $paidCentavos);
        }

        $payable = $this->resolver->find($type, $id);

        if (! $payable || $payable->gateway_checkout_id !== $sessionId) {
            Log::warning('PayMongo event does not match a checkout we created.', compact('type', 'id', 'sessionId'));

            return false;
        }

        if ($paidCentavos < (int) round($payable->amountDue() * 100)) {
            Log::warning('PayMongo paid amount is lower than the amount due.', compact('type', 'id', 'paidCentavos'));

            return false;
        }

        return $this->payments->settle($payable, PaymentService::METHOD_ONLINE, $paymentId);
    }

    private function settleApplication(string $id, string $sessionId, string $paymentId, int $paidCentavos): bool
    {
        return DB::transaction(function () use ($id, $sessionId, $paymentId, $paidCentavos) {
            $application = RentalApplication::with('stall')->lockForUpdate()->find($id);

            if (! $application || $application->gateway_checkout_id !== $sessionId) {
                return false;
            }

            if ($application->payment_status === PaymentStatus::Paid) {
                return false;
            }

            if ($paidCentavos < (int) round($application->stall->price * 100)) {
                Log::warning('PayMongo paid amount is lower than the stall price.', ['application' => $id, 'paid' => $paidCentavos]);

                return false;
            }

            $application->update([
                'payment_method' => PaymentService::METHOD_ONLINE,
                'payment_status' => PaymentStatus::Paid,
                'gcash_reference' => $paymentId,
            ]);

            ActivityLogger::log('application_paid_online', $application->name.' paid the first month online for "'.$application->stall->name.'"', $application);

            return true;
        });
    }
}
