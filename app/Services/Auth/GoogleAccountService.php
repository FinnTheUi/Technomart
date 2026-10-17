<?php

namespace App\Services\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAccountService
{
    public function __construct(private readonly GoogleOAuthCredentials $credentials) {}

    /**
     * Find the local account for a Google identity, linking or registering it as needed.
     *
     * @throws GoogleSignInException
     */
    public function resolveUser(SocialiteUser $googleUser): User
    {
        $email = Str::lower((string) $googleUser->getEmail());

        $this->ensureEmailIsAcceptable($googleUser, $email);

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? $this->linkExistingAccount($googleUser, $email)
            ?? $this->register($googleUser, $email);

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

    private function register(SocialiteUser $googleUser, string $email): User
    {
        $user = DB::transaction(function () use ($googleUser, $email) {
            $user = new User([
                'name' => Str::limit($googleUser->getName() ?: Str::before($email, '@'), 20, ''), // matches the app-wide 20-char name rule
                'email' => $email,
                // Google users sign in without a password; a random one keeps the column
                // non-null and can be replaced later through "Forgot password".
                'password' => Str::password(32),
                'role' => User::ROLE_TENANT,
                'is_active' => true,
                'privacy_consented_at' => now(),
            ]);

            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ])->save();

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'registered_google',
                'description' => $user->name.' registered using Google',
                'subject_type' => User::class,
                'subject_id' => $user->id,
            ]);

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}
