@if($user->isManager())
<div class="panel" id="gcash-payment-details" style="margin-top:24px;">
    <h3 class="panel-title">GCash payment details</h3>
    <p style="color:#64748b;margin:8px 0 16px;">Upload your GCash receiving QR. Users will see it when paying your rental applications, rent and electric bills. Your QR is also available for supply-order payments.</p>
    @if(session('status') === 'gcash-updated')
        <div class="alert alert-success">GCash payment details updated.</div>
    @endif
    @if($user->gcashQrUrl())
        <img src="{{ $user->gcashQrUrl() }}" alt="Your GCash receiving QR code" style="display:block;width:260px;max-width:100%;height:auto;background:white;margin-bottom:16px;">
    @endif
    <form method="POST" action="{{ route('manager.gcash.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="form-group">
            <label class="form-label" for="gcash_account_name">GCash account name</label>
            <input id="gcash_account_name" name="gcash_account_name" class="form-input" maxlength="100" value="{{ old('gcash_account_name', $user->gcash_account_name) }}" placeholder="Name shown in GCash">
        </div>
        <div class="form-group">
            <label class="form-label" for="gcash_account_number">GCash mobile number</label>
            <input id="gcash_account_number" name="gcash_account_number" class="form-input" maxlength="13" value="{{ old('gcash_account_number', $user->gcash_account_number) }}" placeholder="09XXXXXXXXX" inputmode="tel">
        </div>
        <div class="form-group">
            <label class="form-label" for="gcash_qr">GCash QR image</label>
            <input id="gcash_qr" name="gcash_qr" class="form-input" type="file" accept="image/png,image/jpeg,image/webp">
            <p style="color:#64748b;font-size:12px;">PNG, JPG or WebP, up to 5 MB. Use a clear receiving QR without cropping its edges.</p>
        </div>
        @if($user->gcash_qr_path)
            <label style="display:block;margin:12px 0;"><input type="checkbox" name="remove_qr" value="1"> Remove current QR image</label>
        @endif
        <button class="btn-submit" type="submit">Save GCash details</button>
    </form>
</div>
@endif
