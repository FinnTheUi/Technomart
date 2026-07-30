<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    use ActivityLogTrait;

    /**
     * Display contract details with e-sign status.
     */
    public function show(Contract $contract)
    {
        if ($contract->tenant_id !== auth()->id()) abort(403);
        $contract->load('stall', 'tenant');

        return view('tenant.contract', compact('contract'));
    }

    /**
     * Record the tenant's digital acceptance.
     */
    public function sign(Request $request, Contract $contract)
    {
        if ($contract->tenant_id !== auth()->id()) abort(403);

        if ($contract->isSigned()) {
            return back()->with('error', 'This contract has already been signed.');
        }

        // Generate a document hash for integrity verification
        $documentHash = hash('sha256', json_encode([
            'contract_id' => $contract->id,
            'tenant_id' => $contract->tenant_id,
            'stall_id' => $contract->stall_id,
            'terms' => $contract->terms,
            'signed_at' => now()->toIso8601String(),
        ]));

        $contract->update([
            'signed_at' => now(),
            'signed_ip' => $request->ip(),
            'signed_user_agent' => $request->userAgent(),
            'document_hash' => $documentHash,
        ]);

        $this->logActivity('signed_contract', auth()->user()->name . ' digitally signed contract #' . $contract->id, $contract);

        return back()->with('success', 'Contract signed successfully! You can download the PDF below.');
    }

    /**
     * Serve a print-optimized view for PDF download.
     */
    public function downloadPdf(Contract $contract)
    {
        if ($contract->tenant_id !== auth()->id() && auth()->user()->role !== 'manager' && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $contract->load('stall', 'tenant');

        return view('tenant.contract-pdf', compact('contract'));
    }
}
