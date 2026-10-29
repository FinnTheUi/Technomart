<section class="gcash-reference-panel" aria-label="Submit payment reference">
    <div class="gcash-amount"><span>Total amount to send</span><strong>PHP {{ number_format($gcashAmount, 2) }}</strong></div>
    <p class="gcash-section-label">Already sent your payment?</p>
    @if(old('gcash_payment_key') === $gcashKey && $errors->any())
        <div class="gcash-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form class="gcash-form" method="POST" action="{{ $gcashAction }}" @if($gcashIsBill) enctype="multipart/form-data" @endif>
        @csrf
        @if($gcashIsBill) @method('PATCH') @endif
        <input type="hidden" name="gcash_payment_key" value="{{ $gcashKey }}">
        <div class="gcash-field">
            <label for="reference-{{ $gcashKey }}">GCash reference number</label>
            <input id="reference-{{ $gcashKey }}" type="text" name="reference_number" inputmode="numeric" placeholder="Enter 13-digit reference" required maxlength="20" autocomplete="off" value="{{ old('gcash_payment_key') === $gcashKey ? old('reference_number') : '' }}" aria-describedby="reference-help-{{ $gcashKey }}">
            <small id="reference-help-{{ $gcashKey }}">Find the 13-digit number on your GCash receipt.</small>
        </div>
        @if($gcashIsBill)
            <div class="gcash-field">
                <label for="proof-{{ $gcashKey }}">Payment receipt <span>(optional)</span></label>
                <div class="gcash-upload"><input id="proof-{{ $gcashKey }}" type="file" name="proof" accept="image/png,image/jpeg,image/webp" aria-describedby="proof-help-{{ $gcashKey }}"></div>
                <small id="proof-help-{{ $gcashKey }}">Attach a screenshot, up to 5 MB.</small>
            </div>
        @endif
        <button type="submit" class="gcash-primary"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>Submit for verification</button>
    </form>
    <p class="gcash-disclaimer"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3zm-4 9 3 3 5-6"/></svg>Your Manager will review the payment before it is marked as paid.</p>
</section>
