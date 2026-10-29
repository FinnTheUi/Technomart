<x-guest-layout>
    @if($verified)
        <h2>Email verified</h2>
        <p class="auth-subtitle">Your email address has been confirmed. You can now sign in to your Technomart account.</p>
        @auth
            <p class="auth-subtitle">This browser is currently signed into a different account. Sign out first to use the account you just verified.</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-submit">Sign out</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn-submit" style="display:block;text-align:center;text-decoration:none;">Sign in</a>
        @endauth
    @else
        <h2>Verification link unavailable</h2>
        <p class="auth-subtitle">This link has expired or is no longer valid. Open the newest verification email, or sign in and request a fresh verification link.</p>
        @auth
            @if(!auth()->user()->hasVerifiedEmail())
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn-submit">Send a fresh verification email</button>
                </form>
            @endif
            <div class="auth-links"><a href="{{ route('dashboard') }}" class="auth-link">Continue to your account</a></div>
        @else
            <a href="{{ route('login') }}" class="btn-submit" style="display:block;text-align:center;text-decoration:none;">Sign in to request a new link</a>
        @endauth
    @endif
</x-guest-layout>
