<?php

namespace App\Http\Controllers\Manager;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StoreElectricBillRequest;
use App\Models\ElectricBill;
use App\Models\Stall;
use App\Models\User;
use App\Services\Billing\PaymentService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ElectricBillController extends Controller
{
    use ActivityLogTrait;

    public function index(Request $request): View
    {
        $managerId = $request->user()->id;

        return view('manager.electric-bills', [
            'bills' => ElectricBill::with(['tenant', 'stall'])
                ->where('manager_id', $managerId)
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [PaymentStatus::ForVerification->value])
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->fragment('electric-bill-records'),
            'tenants' => User::where('role', User::ROLE_TENANT)->orderBy('name')->get(),
            'stalls' => Stall::where('manager_id', $managerId)->get(),
        ]);
    }

    public function store(StoreElectricBillRequest $request): RedirectResponse
    {
        $bill = ElectricBill::create([
            ...$request->validated(),
            'manager_id' => $request->user()->id,
            'kwh_used' => $request->kwhUsed(),
            'total_amount' => $request->totalAmount(),
            'status' => PaymentStatus::Pending,
        ]);

        $this->logActivity('created_electric_bill', $request->user()->name.' issued electric bill PHP '.number_format($bill->total_amount, 2).' to tenant', $bill);

        return back()->with('success', 'Electric bill created successfully! Total: PHP '.number_format($bill->total_amount, 2));
    }

    public function markOverdue(Request $request, ElectricBill $electricBill, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('review', $electricBill);

        if (! $electricBill->canBePaid()) {
            return back()->with('error', 'Only unpaid bills can be marked overdue.');
        }

        $payments->markOverdue($electricBill, $request->user());

        return back()->with('success', 'Electric bill marked as overdue.');
    }
}
