<?php

namespace App\Http\Controllers\Billing;

use App\Contracts\Payable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\RejectPaymentRequest;
use App\Http\Requests\Billing\SubmitPaymentRequest;
use App\Services\Billing\OnlinePaymentService;
use App\Services\Billing\PayableResolver;
use App\Services\Billing\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payment actions shared by rent bills, electric bills and supply orders.
 * Who may do what is decided by each model's policy (pay / review / view).
 */
class PaymentFlowController extends Controller
{
    public function __construct(
        private readonly PayableResolver $resolver,
        private readonly PaymentService $payments,
        private readonly OnlinePaymentService $online,
    ) {}

    /** Payer submits a GCash reference number for verification. */
    public function submit(SubmitPaymentRequest $request, string $type, int $id): RedirectResponse
    {
        $payable = $this->find($type, $id, 'pay');

        return $this->attempt(
            fn () => $this->payments->submitReference($payable, $request->user(), $request->reference_number, $request->file('proof')),
            'Payment submitted! We will notify you once it has been verified.',
        );
    }

    /** Payer is redirected to PayMongo's hosted checkout. */
    public function checkout(string $type, int $id): RedirectResponse
    {
        $payable = $this->find($type, $id, 'pay');

        if (! $this->online->isEnabled()) {
            return back()->with('error', 'Online payment is not available. Please pay via GCash reference.');
        }

        try {
            $returnUrl = route('billing.checkout.return', ['type' => $type, 'id' => $id]);
            $url = $this->online->checkoutForPayable($payable, $returnUrl.'?result=success', $returnUrl.'?result=cancelled');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /** Browser return from PayMongo. The webhook, not this redirect, confirms payment. */
    public function checkoutReturn(Request $request, string $type, int $id): RedirectResponse
    {
        $payable = $this->find($type, $id, 'pay');

        return redirect()->to(PayableResolver::payerUrl($payable))->with(
            ...($request->query('result') === 'success'
                ? ['success', 'Thank you! Your payment is being confirmed and your receipt will appear shortly.']
                : ['error', 'Online payment was cancelled.'])
        );
    }

    /** Staff confirm a submitted reference. */
    public function verify(Request $request, string $type, int $id): RedirectResponse
    {
        $payable = $this->find($type, $id, 'review');

        return $this->attempt(
            fn () => $this->payments->verify($payable, $request->user()),
            fn () => 'Payment verified. Receipt '.$payable->receipt_number.' issued.',
        );
    }

    /** Staff reject a submitted reference with a reason. */
    public function reject(RejectPaymentRequest $request, string $type, int $id): RedirectResponse
    {
        $payable = $this->find($type, $id, 'review');

        return $this->attempt(
            fn () => $this->payments->reject($payable, $request->user(), $request->reason),
            'Payment rejected. The tenant has been notified.',
        );
    }

    /** Staff record a cash payment made in person. */
    public function recordCash(Request $request, string $type, int $id): RedirectResponse
    {
        $payable = $this->find($type, $id, 'review');

        if ($payable->isPaid()) {
            return back()->with('error', 'This is already paid.');
        }

        $this->payments->settle($payable, PaymentService::METHOD_CASH, null, $request->user());

        return back()->with('success', 'Cash payment recorded. Receipt '.$payable->receipt_number.' issued.');
    }

    /** View the uploaded proof-of-payment image (private storage). */
    public function proof(string $type, int $id): StreamedResponse
    {
        $payable = $this->find($type, $id, 'view');

        abort_unless($payable->proof_path && Storage::disk('local')->exists($payable->proof_path), 404);

        return Storage::disk('local')->response($payable->proof_path);
    }

    /**
     * @return Model&Payable
     */
    private function find(string $type, int $id, string $ability): Payable
    {
        $payable = $this->resolver->findOrFail($type, $id);
        Gate::authorize($ability, $payable);

        return $payable;
    }

    private function attempt(callable $action, string|callable $success): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', is_callable($success) ? $success() : $success);
    }
}
