<header class="gcash-heading">
    <div class="gcash-brand" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H5a2 2 0 00-2 2v3m13-5h3a2 2 0 012 2v3M3 16v3a2 2 0 002 2h3m13-5v3a2 2 0 01-2 2h-3M8 8h3v3H8V8zm5 5h3v3h-3v-3zm-5 1v2m6-8h2m-5 8v1m6-5v5"/></svg></div>
    <div class="gcash-heading-text">
        <p class="gcash-eyebrow">{{ $gcashEyebrow }}</p>
        <h2 id="{{ $gcashHeadingId }}">Pay with GCash</h2>
        <p class="gcash-mobile-amount">PHP {{ number_format($gcashHeaderAmount, 2) }}</p>
    </div>
    @if($closable ?? false)
        <button type="button" class="gcash-close" data-gcash-close aria-label="Close payment dialog"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="m6 6 12 12M6 18 18 6"/></svg></button>
    @endif
</header>
<div class="gcash-steps" aria-label="Payment steps">
    <span class="gcash-step"><b>1</b> Scan QR &amp; send payment</span>
    <span class="gcash-step"><b>2</b> Submit your reference</span>
</div>
