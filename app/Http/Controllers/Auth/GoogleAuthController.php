<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAccountService;
use App\Services\Auth\GoogleOAuthCredentials;
use App\Services\Auth\GoogleSignInException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleOAuthCredentials $credentials,
        private readonly GoogleAccountService $accounts,
    ) {}

    /**
     * Send the user to Google's consent screen.
     */
    public function redirect(): RedirectResponse|SymfonyRedirect
    {
        if (! $this->credentials->isConfigured()) {
            return $this->failed('Google sign-in is not available right now.');
        }

        return $this->provider()
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Handle Google's callback: register or sign in the user.
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! $this->credentials->isConfigured()) {
            return $this->failed('Google sign-in is not available right now.');
        }

        if ($request->filled('error')) {
            return $this->failed('Google sign-in was cancelled.');
        }

        try {
            $user = $this->accounts->resolveUser($this->provider()->user());
        } catch (GoogleSignInException $e) {
            return $this->failed($e->getMessage());
        } catch (InvalidStateException) {
            return $this->failed('Your Google sign-in session expired. Please try again.');
        } catch (Throwable $e) {
            Log::error('Google sign-in failed', ['exception' => $e]);

            return $this->failed('Google sign-in failed. Please try again.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function provider(): GoogleProvider
    {
        /** @var GoogleProvider */
        return Socialite::buildProvider(GoogleProvider::class, [
            'client_id' => $this->credentials->clientId(),
            'client_secret' => $this->credentials->clientSecret(),
            'redirect' => $this->credentials->redirectUrl(),
        ]);
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => $message]);
    }
}
