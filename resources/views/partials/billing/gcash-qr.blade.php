@php
    $gcashManagers = collect($managers)->filter(fn ($manager) => $manager && $manager->isManager() && $manager->is_active);
@endphp
<div class="gcash-receiving-details" style="margin:12px 0;">
    @if($gcashManagers->count() > 1)
        <p style="font-size:13px;color:#475569;">Pay only once to one of the receiving Managers below, then submit your GCash reference.</p>
    @endif
    @forelse($gcashManagers as $receivingManager)
        <div style="padding:12px;border:1px solid #cbd5e1;border-radius:10px;margin-bottom:10px;background:#fff;">
            <p style="font-size:13px;color:#334155;margin:0 0 8px;">Receiving Manager: <strong>{{ $receivingManager->name }}</strong></p>
            @if($receivingManager->gcashQrUrl())
                <a href="{{ $receivingManager->gcashQrUrl() }}" target="_blank" rel="noopener" aria-label="Open {{ $receivingManager->name }} GCash QR at full size">
                    <img src="{{ $receivingManager->gcashQrUrl() }}" alt="GCash QR code for {{ $receivingManager->name }}" style="display:block;width:260px;max-width:100%;height:auto;background:#fff;">
                </a>
                <p style="font-size:12px;color:#64748b;margin-top:8px;">Scan this QR in GCash, or open and save it to upload using GCash's Scan QR feature.</p>
            @else
                <p style="font-size:13px;color:#64748b;">This Manager has not uploaded a QR code yet. Contact them for payment instructions.</p>
            @endif
            @if($receivingManager->gcash_account_name)
                <p style="font-size:13px;">GCash account: <strong>{{ $receivingManager->gcash_account_name }}</strong></p>
            @endif
            @if($receivingManager->gcash_account_number)
                <p style="font-size:13px;">GCash number: <strong>{{ $receivingManager->gcash_account_number }}</strong></p>
            @endif
        </div>
    @empty
        <p style="font-size:13px;color:#64748b;">Contact Technomart for the GCash receiving details.</p>
    @endforelse
    <p style="font-size:12px;color:#64748b;">Check the recipient and amount in GCash before sending. Submit the 13-digit reference after payment; the Manager will verify it.</p>
</div>
