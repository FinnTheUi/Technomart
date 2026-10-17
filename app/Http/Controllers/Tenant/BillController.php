<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ElectricBill;
use App\Models\Payment;
use App\Services\Billing\OnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The tenant's rent and electric bills. Paying happens through Billing\PaymentFlowController.
 */
class BillController extends Controller
{
    public function __construct(private readonly OnlinePaymentService $online) {}

    public function index(Request $request): View
    {
        return view('tenant.bills', [
            'bills' => Payment::with(['stall', 'manager'])->where('tenant_id', $request->user()->id)->orderByDesc('due_date')->get(),
            'onlinePayments' => $this->online->isEnabled(),
        ]);
    }

    public function electricBills(Request $request): View
    {
        return view('tenant.electric-bills', [
            'bills' => ElectricBill::with(['stall', 'manager'])->where('tenant_id', $request->user()->id)->orderByDesc('billing_date')->get(),
            'onlinePayments' => $this->online->isEnabled(),
        ]);
    }
}
