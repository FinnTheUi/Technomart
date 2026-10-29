<?php

namespace App\Http\Controllers\Manager;

use App\Enums\PaymentStatus;
use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Contract;
use App\Notifications\BillIssuedNotification;
use App\Services\Billing\PaymentService;
use App\Services\NavigationBadgeService;
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

    public function index(Request $request, NavigationBadgeService $badges): View
    {
        $managerId = $request->user()->id;
        $badges->markPayableRead($request->user(), 'payment');
        $activeContracts = Contract::with(['tenant:id,name', 'stall:id,name,price,manager_id'])
            ->where('status', ContractStatus::Active)
            ->whereHas('stall', fn ($query) => $query->where('manager_id', $managerId))
            ->latest('id')->get()->unique('stall_id');

        return view('manager.payments', [
            'payments' => Payment::with(['tenant', 'stall', 'latestActivity'])
                ->where('manager_id', $managerId)
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [PaymentStatus::ForVerification->value])
                ->orderByDesc('due_date')
                ->paginate(10)
                ->withQueryString()
                ->fragment('payment-records'),
            'tenants' => $activeContracts->pluck('tenant')->filter()->unique('id')->sortBy('name')->values(),
            'stalls' => $activeContracts->pluck('stall')->filter()->unique('id')->sortBy('name')->values(),
            'tenantStallAssignments' => $activeContracts->map(fn (Contract $contract) => [
                'tenant_id' => $contract->tenant_id, 'stall_id' => $contract->stall_id,
            ])->values(),
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
        $payment->tenant?->notify(new BillIssuedNotification($payment));

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
