{{--
    Payer actions for any payable document (rent bill, electric bill, supply order).
    @param  \App\Contracts\Payable  $payable
    @param  bool  $onlinePayments  PayMongo checkout available
--}}
@php
    $params = ['type' => $payable::payableType(), 'id' => $payable->getKey()];
    $gcash = config('billing.gcash');
@endphp

@if($payable->isPaid() && $payable->receipt_number)
    <a href="{{ route('receipts.show', $params) }}" class="btn-sm btn-view">Receipt {{ $payable->receipt_number }}</a>
@elseif($payable->isPaid())
    <span class="detail-sub">Paid · Receipt is being prepared</span>
@elseif($payable->isAwaitingVerification())
    <span class="detail-sub">Awaiting verification<br>Ref {{ $payable->reference_number }}</span>
@elseif($payable->canBePaid())
    <div class="pay-box">
        @if($payable->rejection_reason)
            <div class="pay-rejected">Previous payment not verified: {{ $payable->rejection_reason }}</div>
        @endif

        @if($onlinePayments ?? false)
            <form method="POST" action="{{ route('billing.checkout', $params) }}">
                @csrf
                <button type="submit" class="btn-pay">Pay online &middot; ₱{{ number_format($payable->amountDue(), 2) }}</button>
            </form>
        @endif

        <details class="pay-details">
            <summary>Paid via GCash? Submit reference</summary>
            @if($gcash['account_number'])
                <p class="detail-sub">Send ₱{{ number_format($payable->amountDue(), 2) }} to <strong>{{ $gcash['account_name'] }}</strong> ({{ $gcash['account_number'] }}), then enter the 13-digit reference number.</p>
            @endif
            <form method="POST" action="{{ route('billing.submit', $params) }}" enctype="multipart/form-data">
                @csrf @method('PATCH')
                <input type="text" name="reference_number" class="form-input" inputmode="numeric" placeholder="13-digit reference no." required maxlength="20">
                <label class="detail-sub">Screenshot (optional) <input type="file" name="proof" accept="image/*"></label>
                <button type="submit" class="btn-sm btn-approve">Submit for verification</button>
            </form>
        </details>
    </div>
@else
    <span class="detail-sub">&mdash;</span>
@endif

@once
    <style>
        .pay-box { display: flex; flex-direction: column; gap: 8px; min-width: 220px; }
        .pay-details summary { cursor: pointer; font-size: 13px; color: #4f46e5; font-weight: 600; }
        .pay-details form { display: flex; flex-direction: column; gap: 6px; margin-top: 8px; }
        .pay-rejected { font-size: 12px; color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 6px 8px; }
    </style>
@endonce
