<?php

namespace App\Console\Commands;

use App\Support\EncryptedSecret;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EncryptSecretCommand extends Command
{
    protected $signature = 'secret:encrypt
                            {target? : google | paymongo-secret | paymongo-webhook}
                            {--show : Print the encrypted value instead of writing it to .env}';

    protected $description = 'Encrypt a third-party secret with APP_KEY and store it in .env';

    public function handle(): int
    {
        if (blank(config('app.key'))) {
            $this->error('APP_KEY is not set. Run `php artisan key:generate` first.');

            return self::FAILURE;
        }

        $target = $this->target();

        if (! isset(EncryptedSecret::TARGETS[$target])) {
            $this->error("Unknown target [{$target}]. Use one of: ".implode(', ', array_keys(EncryptedSecret::TARGETS)));

            return self::FAILURE;
        }

        [$envKey] = EncryptedSecret::TARGETS[$target];

        $secret = trim((string) $this->secret($this->promptLabel($target)));

        if ($secret === '') {
            $this->error('No secret provided.');

            return self::FAILURE;
        }

        $encrypted = EncryptedSecret::encrypt($secret);

        if ($this->option('show')) {
            $this->line($envKey.'='.$encrypted);

            return self::SUCCESS;
        }

        $envPath = $this->laravel->environmentFilePath();

        if (! File::exists($envPath)) {
            $this->error("Environment file not found at {$envPath}.");

            return self::FAILURE;
        }

        $this->writeEnvValue($envPath, $envKey, $encrypted);

        $this->components->info("Encrypted secret saved to .env ({$envKey}).");
        $this->components->warn('Remove any plain-text copy of this secret. If APP_KEY is rotated, re-run this command.');

        if ($this->laravel->configurationIsCached()) {
            $this->components->warn('Configuration is cached; run `php artisan config:cache` to pick up the new value.');
        }

        return self::SUCCESS;
    }

    protected function target(): string
    {
        return $this->argument('target')
            ?? $this->choice('Which secret?', array_keys(EncryptedSecret::TARGETS));
    }

    protected function promptLabel(string $target): string
    {
        return match ($target) {
            'google' => 'Google OAuth client secret (input hidden)',
            'paymongo-secret' => 'PayMongo secret key, sk_... (input hidden)',
            'paymongo-webhook' => 'PayMongo webhook signing secret, whsk_... (input hidden)',
        };
    }

    private function writeEnvValue(string $path, string $key, string $value): void
    {
        $contents = File::get($path);
        $line = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        $contents = preg_match($pattern, $contents)
            ? preg_replace_callback($pattern, fn () => $line, $contents)
            : rtrim($contents).PHP_EOL.$line.PHP_EOL;

        File::put($path, $contents);
    }
}
