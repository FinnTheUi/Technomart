<?php

namespace App\Console\Commands;

/**
 * Shortcut for `php artisan secret:encrypt google`.
 */
class EncryptGoogleSecretCommand extends EncryptSecretCommand
{
    protected $signature = 'google:encrypt-secret
                            {--show : Print the encrypted value instead of writing it to .env}';

    protected $description = 'Encrypt the Google OAuth client secret with APP_KEY and store it in .env';

    protected function target(): string
    {
        return 'google';
    }
}
