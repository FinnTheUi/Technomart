<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\RenewContractRequest;
use App\Models\Contract;
use App\Services\Rental\LeaseService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

class ContractController extends Controller
{
    use ActivityLogTrait;

    public function index(Request $request): View
    {
        return view('tenant.contracts', [
            'contracts' => Contract::with('stall')->where('tenant_id', $request->user()->id)->latest()->get(),
        ]);
    }

    /** Contract details with e-sign status. */
    public function show(Contract $contract): View
    {
        Gate::authorize('sign', $contract);

        return view('tenant.contract', ['contract' => $contract->load('stall', 'tenant')]);
    }

    /** Record the tenant's digital acceptance. */
    public function sign(Request $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('sign', $contract);

        if ($contract->isSigned()) {
            return back()->with('error', 'This contract has already been signed.');
        }

        $signedAt = now();

        // Document hash for integrity verification.
        $documentHash = hash('sha256', json_encode([
            'contract_id' => $contract->id,
            'tenant_id' => $contract->tenant_id,
            'stall_id' => $contract->stall_id,
            'terms' => $contract->terms,
            'signed_at' => $signedAt->toIso8601String(),
        ]));

        $contract->update([
            'signed_at' => $signedAt,
            'signed_ip' => $request->ip(),
            'signed_user_agent' => $request->userAgent(),
            'document_hash' => $documentHash,
        ]);

        $this->logActivity('signed_contract', $request->user()->name.' digitally signed contract #'.$contract->id, $contract);

        return back()->with('success', 'Contract signed successfully! You can download the PDF below.');
    }

    /** Print-optimized view for PDF download. */
    public function pdf(Contract $contract): View
    {
        Gate::authorize('view', $contract);

        return view('tenant.contract-pdf', ['contract' => $contract->load('stall', 'tenant')]);
    }

    public function requestRenewal(RenewContractRequest $request, Contract $contract, LeaseService $leases): RedirectResponse
    {
        Gate::authorize('requestRenewal', $contract);

        try {
            $leases->requestRenewal($contract, $request->integer('months'), $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Renewal request sent to your manager.');
    }
}
