<?php

namespace App\Services\Auth;

use App\Support\EncryptedSecret;

/**
 * Google OAuth client credentials. The client secret is kept encrypted at rest
 * and decrypted only when an OAuth request is built (see App\Support\EncryptedSecret).
 */
class GoogleOAuthCredentials
{
    private const SECRET_KEY = 'services.google.client_secret_encrypted';

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && EncryptedSecret::isSet(self::SECRET_KEY);
    }

    public function clientId(): string
    {
        return (string) config('services.google.client_id');
    }

    public function clientSecret(): string
    {
        return EncryptedSecret::decrypt(self::SECRET_KEY);
    }

    public function redirectUrl(): string
    {
        return url(config('services.google.redirect'));
    }

    /**
     * @return array<int, string>
     */
    public function allowedDomains(): array
    {
        return array_map('strtolower', (array) config('services.google.allowed_domains', []));
    }
}
