<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\PayableResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(PayableResolver $resolver, string $type, int $id): View
    {
        $payable = $resolver->findOrFail($type, $id);
        Gate::authorize('view', $payable);

        abort_unless($payable->isPaid() && $payable->receipt_number, 404, 'No receipt has been issued for this document yet.');

        return view('billing.receipt', [
            'payable' => $payable,
            'payer' => $payable->payer(),
        ]);
    }
}
