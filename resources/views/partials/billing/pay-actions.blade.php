@php
    $params = ['type' => $payable::payableType(), 'id' => $payable->getKey()];
    $gcashKey = $payable::payableType().'-'.$payable->getKey();
    $dialogId = 'gcash-dialog-'.$gcashKey;
@endphp
@if($payable->isPaid() && $payable->receipt_number)
    <a href="{{ route('receipts.show', $params) }}" class="btn-sm btn-view">Receipt {{ $payable->receipt_number }}</a>
@elseif($payable->isPaid())
    <span class="detail-sub">Paid &middot; Receipt is being prepared</span>
@elseif($payable->isAwaitingVerification())
    <span class="detail-sub">Awaiting verification<br>Ref {{ $payable->reference_number }}</span>
@elseif($payable->canBePaid())
    <div class="pay-box">
        @if($payable->rejection_reason)
            <p class="pay-rejected">Previous payment not verified: {{ $payable->rejection_reason }}</p>
        @endif
        <button type="button" class="gcash-pay-trigger" data-gcash-open="{{ $dialogId }}" aria-haspopup="dialog" aria-controls="{{ $dialogId }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5M8 8h3v3H8V8zm5 5h3v3h-3v-3zm-5 1v2m6-8h2"/></svg>
            Pay with GCash
        </button>
        @if($onlinePayments ?? false)
            <form method="POST" action="{{ route('billing.checkout', $params) }}">
                @csrf
                <button type="submit" class="btn-sm btn-view">Pay online</button>
            </form>
        @endif
    </div>
    <dialog id="{{ $dialogId }}" class="gcash-dialog" aria-labelledby="{{ $dialogId }}-title" @if(old('gcash_payment_key') === $gcashKey) data-gcash-reopen @endif>
        <div class="gcash-payment">
            @include('partials.billing.gcash-header', ['gcashEyebrow' => $payable->paymentDescription(), 'gcashHeadingId' => $dialogId.'-title', 'gcashHeaderAmount' => $payable->amountDue(), 'closable' => true])
            <div class="gcash-payment-layout">
                @include('partials.billing.gcash-qr', ['managers' => $payable->reviewers()])
                @include('partials.billing.gcash-reference-form', ['gcashAmount' => $payable->amountDue(), 'gcashAction' => route('billing.submit', $params), 'gcashIsBill' => true])
            </div>
        </div>
    </dialog>
@else
    <span class="detail-sub">&mdash;</span>
@endif
