@php
    $gcashManagers = collect($managers)
        ->filter(fn ($manager) => $manager && $manager->isManager() && $manager->is_active)
        ->sortBy(fn ($manager) => $manager->gcash_qr_path ? 0 : 1)->values();
@endphp
<section class="gcash-receiving-details" aria-label="GCash receiving account">
    <p class="gcash-section-label">Scan to pay</p>
    @if($gcashManagers->count() > 1)
        <label class="gcash-section-label" for="recipient-{{ $gcashKey }}">Receiving Manager</label>
        <select id="recipient-{{ $gcashKey }}" class="gcash-manager-select" data-gcash-recipient-select>
            @foreach($gcashManagers as $manager)
                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
            @endforeach
        </select>
    @endif
    @forelse($gcashManagers as $receivingManager)
        <div class="gcash-recipient" data-gcash-recipient="{{ $receivingManager->id }}" @if(!$loop->first) hidden @endif>
            @if($receivingManager->gcashQrUrl())
                <div class="gcash-qr-stage">
                    <a href="{{ $receivingManager->gcashQrUrl() }}" target="_blank" rel="noopener" aria-label="Enlarge GCash QR for {{ $receivingManager->name }}">
                        <img src="{{ $receivingManager->gcashQrUrl() }}" alt="GCash QR code for {{ $receivingManager->name }}" loading="lazy">
                    </a>
                </div>
                <div class="gcash-image-actions">
                    <a href="{{ $receivingManager->gcashQrUrl() }}" target="_blank" rel="noopener"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/></svg>Enlarge QR</a>
                    <a href="{{ $receivingManager->gcashQrUrl() }}" download><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m-4-4 4 4 4-4M4 17v4h16v-4"/></svg>Save image</a>
                </div>
            @else
                <div class="gcash-empty">This Manager has not uploaded a QR code yet. Contact them for payment instructions.</div>
            @endif
            <dl class="gcash-recipient-details">
                <div><dt>Receiving Manager</dt><dd>{{ $receivingManager->name }}</dd></div>
                @if($receivingManager->gcash_account_name)
                    <div><dt>Account name</dt><dd>{{ $receivingManager->gcash_account_name }}</dd></div>
                @endif
                @if($receivingManager->gcash_account_number)
                    <div><dt>GCash number</dt><dd>{{ $receivingManager->gcash_account_number }}</dd></div>
                @endif
            </dl>
        </div>
    @empty
        <div class="gcash-empty">Contact Technomart for the GCash receiving details.</div>
    @endforelse
    <p class="gcash-recipient-note">Check the recipient in GCash before sending. On the same phone? Save the image and upload it in GCash's Scan QR feature.@if($gcashManagers->count() > 1) Send payment to one Manager only.@endif</p>
</section>
