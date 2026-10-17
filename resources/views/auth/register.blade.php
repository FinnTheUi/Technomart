<x-guest-layout>
    <h2>Create your account</h2>
    <p class="auth-subtitle">Get started with Technomart for free</p>

    @include('auth.partials.google-button', ['label' => 'Sign up with Google'])

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Name --}}
        <div class="form-group">
            <label for="name" class="form-label">Full Name</label>
            <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Doe">
            @error('name')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Email --}}
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="you@example.com">
            @error('email')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="password-wrapper">
                <input id="password" class="form-input" type="password" name="password" required autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="password-toggle" onclick="togglePassword('password', 'eye-open-1', 'eye-closed-1')" aria-label="Toggle password visibility">
                    <svg id="eye-open-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:block;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eye-closed-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div class="password-wrapper">
                <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', 'eye-open-2', 'eye-closed-2')" aria-label="Toggle password visibility">
                    <svg id="eye-open-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:block;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eye-closed-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password_confirmation')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Submit --}}
        <div class="form-group">
            <label style="display:flex;gap:10px;align-items:flex-start;font-size:13px;line-height:1.5;color:#475569;">
                <input type="checkbox" name="privacy_consent" value="1" required style="margin-top:3px;">
                <span>I have read and agree to the <a href="{{ route('privacy') }}" target="_blank" class="auth-link">Privacy Notice</a>.</span>
            </label>
            @error('privacy_consent')<div class="form-error">You must accept the Privacy Notice to register.</div>@enderror
        </div>

        <button type="submit" class="btn-submit">Create Account</button>

        {{-- Links --}}
        <div class="auth-links">
            <a href="{{ route('login') }}" class="auth-link">Already have an account? <strong>Sign in</strong></a>
        </div>
    </form>

    <script>
        function togglePassword(inputId, openId, closedId) {
            const input = document.getElementById(inputId);
            const eyeOpen = document.getElementById(openId);
            const eyeClosed = document.getElementById(closedId);

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            } else {
                input.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            }
        }
    </script>
</x-guest-layout>
