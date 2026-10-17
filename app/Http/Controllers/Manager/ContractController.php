<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\RenewContractRequest;
use App\Http\Requests\Manager\TerminateContractRequest;
use App\Models\Contract;
use App\Services\Rental\LeaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

/**
 * Leases on the manager's stalls: renew, terminate, print.
 */
class ContractController extends Controller
{
    public function __construct(private readonly LeaseService $leases) {}

    public function index(Request $request): View
    {
        $contracts = Contract::with(['tenant', 'stall', 'latestActivity'])
            ->whereHas('stall', fn ($q) => $q->where('manager_id', $request->user()->id))
            ->orderByRaw('CASE WHEN renewal_requested_at IS NOT NULL THEN 0 WHEN status = ? THEN 1 ELSE 2 END', [ContractStatus::Active->value])
            ->orderBy('end_date')
            ->paginate(10)
            ->withQueryString()
            ->fragment('lease-records');

        return view('manager.contracts', compact('contracts'));
    }

    public function renew(RenewContractRequest $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('manage', $contract);

        return $this->attempt(
            fn () => $this->leases->renew($contract, $request->integer('months'), $request->user()),
            'Lease renewed and rent bills created for the added months.',
        );
    }

    public function terminate(TerminateContractRequest $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('manage', $contract);

        return $this->attempt(
            fn () => $this->leases->terminate($contract, $request->reason, $request->user()),
            'Lease terminated. The stall is available again and its waitlist has been notified.',
        );
    }

    public function pdf(Contract $contract): View
    {
        Gate::authorize('manage', $contract);

        return view('tenant.contract-pdf', ['contract' => $contract->load('stall', 'tenant')]);
    }

    private function attempt(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
