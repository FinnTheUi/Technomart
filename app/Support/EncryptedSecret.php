<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * Third-party secrets (OAuth client secrets, payment gateway keys) are stored encrypted
 * with APP_KEY and decrypted in memory only at the moment they are used.
 */
class EncryptedSecret
{
    /**
     * Secrets that `php artisan secret:encrypt {target}` can manage: target => [env var, config key].
     */
    public const TARGETS = [
        'google' => ['GOOGLE_CLIENT_SECRET_ENCRYPTED', 'services.google.client_secret_encrypted'],
        'paymongo-secret' => ['PAYMONGO_SECRET_KEY_ENCRYPTED', 'services.paymongo.secret_key_encrypted'],
        'paymongo-webhook' => ['PAYMONGO_WEBHOOK_SECRET_ENCRYPTED', 'services.paymongo.webhook_secret_encrypted'],
    ];

    public static function encrypt(string $plain): string
    {
        return Crypt::encryptString($plain);
    }

    public static function isSet(string $configKey): bool
    {
        return filled(config($configKey));
    }

    public static function decrypt(string $configKey): string
    {
        $encrypted = config($configKey);

        if (blank($encrypted)) {
            throw new RuntimeException("Secret [{$configKey}] is not configured.");
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            throw new RuntimeException(
                "Unable to decrypt [{$configKey}]. It was likely encrypted with a different APP_KEY; "
                .'re-run `php artisan secret:encrypt`.'
            );
        }
    }
}
