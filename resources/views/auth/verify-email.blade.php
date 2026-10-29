<x-guest-layout>
    <h2>Verify your email</h2>
    <p class="auth-subtitle">
        Confirm your email address using the verification link sent to <strong>{{ auth()->user()->email }}</strong>.
        Didn't get it? Check your spam folder, or send a new one.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="session-status">A new verification link has been sent to your email address.</div>
    @endif
    @if (session('status') == 'account-details-updated-and-sent')
        <div class="session-status">Your signup details were updated and a new verification link was sent.</div>
    @elseif (session('status') == 'account-details-updated')
        <div class="session-status">Your signup details were updated.</div>
    @endif

    @error('mail')
        <div class="form-error form-error-block">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn-submit">Resend verification email</button>
    </form>

    <div class="auth-links">
        @if(auth()->user()->canEditPendingSignup())
            <a href="{{ route('verification.account.edit') }}" class="auth-link">Wrong details? Go back and edit your signup information</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-link" style="background:none;border:none;cursor:pointer;font-family:inherit;">Log out</button>
        </form>
    </div>
</x-guest-layout>
