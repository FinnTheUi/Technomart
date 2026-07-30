<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->role === 'tenant') {
            $payments = \App\Models\Payment::where('tenant_id', $user->id)->get();
        } elseif ($user->role === 'manager') {
            $payments = \App\Models\Payment::where('manager_id', $user->id)->get();
        } else {
            $payments = \App\Models\Payment::all();
        }

        return view('payments.index', compact('payments'));
    }

    public function pay(\App\Models\Payment $payment)
    {
        if (auth()->user()->id !== $payment->tenant_id) {
            abort(403, 'Unauthorized action.');
        }

        $payment->update([
            'status' => 'paid',
            'paid_date' => now(),
            'receipt_url' => 'receipts/receipt_' . $payment->id . '_' . time() . '.pdf',
        ]);

        return redirect()->route('payments.receipt', $payment->id)->with('success', 'Payment successful!');
    }

    public function receipt(\App\Models\Payment $payment)
    {
        if (auth()->user()->id !== $payment->tenant_id && auth()->user()->id !== $payment->manager_id && auth()->user()->role !== 'admin') {
            abort(403);
        }

        return view('payments.receipt', compact('payment'));
    }
}
