<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAccountService
{
    public function __construct(private readonly GoogleOAuthCredentials $credentials) {}

    /**
     * Find and link an existing local account. Tenant accounts are created only
     * through the manager-approved rental application flow.
     *
     * @throws GoogleSignInException
     */
    public function resolveUser(SocialiteUser $googleUser): User
    {
        $email = Str::lower((string) $googleUser->getEmail());

        $this->ensureEmailIsAcceptable($googleUser, $email);

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? $this->linkExistingAccount($googleUser, $email);

        if (! $user) {
            throw new GoogleSignInException('No account exists for this email. Choose an available stall and submit a rental application first.');
        }

        if (! $user->is_active) {
            throw new GoogleSignInException('This account is inactive. Please contact the administrator.');
        }

        return $user;
    }

    private function ensureEmailIsAcceptable(SocialiteUser $googleUser, string $email): void
    {
        if ($email === '' || ! ($googleUser->getRaw()['email_verified'] ?? false)) {
            throw new GoogleSignInException('Your Google account email is not verified.');
        }

        $domain = Str::after($email, '@');
        $allowed = $this->credentials->allowedDomains();

        if ($allowed !== [] && ! in_array($domain, $allowed, true)) {
            throw new GoogleSignInException('Only '.implode(', ', array_map(fn ($d) => '@'.$d, $allowed)).' accounts can be used.');
        }
    }

    /**
     * An account registered with email/password under the same (Google-verified) address
     * is linked to the Google identity rather than duplicated.
     */
    private function linkExistingAccount(SocialiteUser $googleUser, string $email): ?User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return null;
        }

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar_url' => $user->avatar_url ?? $googleUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'privacy_consented_at' => $user->privacy_consented_at ?? now(),
        ])->save();

        return $user;
    }
}
