<x-guest-layout>
    <h2>Edit signup details</h2>
    <p class="auth-subtitle">Correct your information before verifying your account.</p>

    <form method="POST" action="{{ route('verification.account.update') }}">
        @csrf
        @method('PATCH')

        <div class="form-group">
            <label for="name" class="form-label">Full Name</label>
            <input id="name" class="form-input" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Gmail Address</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
            @error('email')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="phone_number" class="form-label">Phone Number</label>
            <input id="phone_number" class="form-input" type="tel" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" autocomplete="tel" placeholder="09XXXXXXXXX">
            @error('phone_number')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="address" class="form-label">Address</label>
            <textarea id="address" class="form-input" name="address" rows="3" autocomplete="street-address">{{ old('address', $user->address) }}</textarea>
            @error('address')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <p class="auth-subtitle" style="margin-top:24px;">Leave the password fields empty to keep your current password.</p>

        <div class="form-group">
            <label for="current_password" class="form-label">Current Password</label>
            <input id="current_password" class="form-input" type="password" name="current_password" autocomplete="current-password">
            @error('current_password')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">New Password</label>
            <input id="password" class="form-input" type="password" name="password" autocomplete="new-password">
            @error('password')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" autocomplete="new-password">
        </div>

        <button type="submit" class="btn-submit">Save and return to verification</button>

        <div class="auth-links">
            <a href="{{ route('verification.notice') }}" class="auth-link">Cancel</a>
        </div>
    </form>
</x-guest-layout>
