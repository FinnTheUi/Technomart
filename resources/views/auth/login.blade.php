<x-guest-layout>
    {{-- Session Status --}}
    @if (session('status'))
        <div class="session-status">{{ session('status') }}</div>
    @endif

    <h2>Welcome back</h2>
    <p class="auth-subtitle">Sign in to your Tecnomart account</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@example.com">
            @error('email')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="password-wrapper">
                <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                    {{-- Eye icon (visible state) --}}
                    <svg id="eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:block;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{-- Eye-off icon (hidden state) --}}
                    <svg id="eye-closed" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember Me --}}
        <div class="form-check">
            <input id="remember_me" type="checkbox" name="remember">
            <label for="remember_me">Remember me</label>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-submit">Sign In</button>

        {{-- Links --}}
        <div class="auth-links">
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="auth-link">Don't have an account? <strong>Sign up</strong></a>
            @endif
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
            @endif
        </div>
    </form>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

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
