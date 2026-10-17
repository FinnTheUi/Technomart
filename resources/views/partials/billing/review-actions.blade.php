{{--
    Staff actions for any payable document: verify / reject a submitted reference,
    record a cash payment, mark overdue, view receipt.
    @param  \App\Contracts\Payable  $payable
    @param  string|null  $overdueUrl  route to mark this bill overdue (bills only)
--}}
@php $params = ['type' => $payable::payableType(), 'id' => $payable->getKey()]; @endphp

@if($payable->isPaid())
    <div class="detail-sub">{{ strtoupper($payable->payment_method ?? '') }}{{ $payable->reference_number ? ' · '.$payable->reference_number : '' }}</div>
    @if($payable->receipt_number)
        <a href="{{ route('receipts.show', $params) }}" class="btn-sm btn-view">Receipt {{ $payable->receipt_number }}</a>
    @endif
@elseif($payable->isAwaitingVerification())
    <div class="review-box">
        <div class="detail-sub">GCash ref <strong>{{ $payable->reference_number }}</strong> &middot; {{ $payable->submitted_at?->diffForHumans() }}</div>
        @if($payable->proof_path)
            <a href="{{ route('billing.proof', $params) }}" target="_blank" class="detail-sub">View screenshot</a>
        @endif
        <div class="action-cell">
            <form method="POST" action="{{ route('billing.verify', $params) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-sm btn-approve" onclick="return confirm('Confirm you received ₱{{ number_format($payable->amountDue(), 2) }} with reference {{ $payable->reference_number }}?')">Verify</button>
            </form>
            <details>
                <summary class="btn-sm btn-reject">Reject</summary>
                <form method="POST" action="{{ route('billing.reject', $params) }}" class="review-reject">
                    @csrf @method('PATCH')
                    <input type="text" name="reason" class="form-input" placeholder="Reason (shown to tenant)" required maxlength="255">
                    <button type="submit" class="btn-sm btn-reject">Send</button>
                </form>
            </details>
        </div>
    </div>
@elseif($payable->canBePaid())
    <div class="action-cell">
        <form method="POST" action="{{ route('billing.recordCash', $params) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn-sm btn-paid" onclick="return confirm('Record a cash payment of ₱{{ number_format($payable->amountDue(), 2) }}?')">Record cash</button>
        </form>
        @if(($overdueUrl ?? null) && $payable->status === \App\Enums\PaymentStatus::Pending)
            <form method="POST" action="{{ $overdueUrl }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-sm btn-overdue" onclick="return confirm('Mark this bill overdue and apply the configured late fee?')">Mark overdue</button>
            </form>
        @endif
    </div>
@else
    <span class="detail-sub">&mdash;</span>
@endif

@once
    <style>
        .review-box { display: flex; flex-direction: column; gap: 6px; }
        .review-box details summary { list-style: none; cursor: pointer; }
        .review-reject { display: flex; gap: 6px; margin-top: 6px; }
    </style>
@endonce
