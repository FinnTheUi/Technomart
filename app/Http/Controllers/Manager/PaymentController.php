<?php

namespace App\Http\Controllers\Manager;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use App\Services\Billing\PaymentService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Rent bills for the manager's stalls. Verifying / rejecting / recording cash
 * goes through Billing\PaymentFlowController.
 */
class PaymentController extends Controller
{
    use ActivityLogTrait;

    public function index(Request $request): View
    {
        $managerId = $request->user()->id;

        return view('manager.payments', [
            'payments' => Payment::with(['tenant', 'stall', 'latestActivity'])
                ->where('manager_id', $managerId)
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [PaymentStatus::ForVerification->value])
                ->orderByDesc('due_date')
                ->paginate(10)
                ->withQueryString()
                ->fragment('payment-records'),
            'tenants' => User::where('role', User::ROLE_TENANT)->orderBy('name')->get(),
            'stalls' => Stall::where('manager_id', $managerId)->get(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = Payment::create([
            ...$request->validated(),
            'manager_id' => $request->user()->id,
            'status' => PaymentStatus::Pending,
        ]);

        $this->logActivity('created_payment', $request->user()->name.' created a PHP '.number_format($payment->amount, 2).' bill for tenant', $payment);

        return back()->with('success', 'Payment bill created successfully.');
    }

    public function markOverdue(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('review', $payment);

        if (! $payment->canBePaid()) {
            return back()->with('error', 'Only unpaid bills can be marked overdue.');
        }

        $payments->markOverdue($payment, $request->user());

        return back()->with('success', 'Payment marked as overdue'.($payment->lateFee() > 0 ? ' (late fee PHP '.number_format($payment->lateFee(), 2).' added).' : '.'));
    }
}
