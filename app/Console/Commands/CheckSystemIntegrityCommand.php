<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class CheckSystemIntegrityCommand extends Command
{
    protected $signature = 'app:doctor';

    protected $description = 'Check runtime, database, storage, migrations, and release prerequisites';

    public function handle(): int
    {
        $checks = [
            ['PHP >= 8.3', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION],
            ['Application key', filled(config('app.key')), filled(config('app.key')) ? 'configured' : 'missing'],
            ['ZIP extension', extension_loaded('zip'), extension_loaded('zip') ? 'loaded' : 'missing'],
            ['PDO extension', extension_loaded('pdo'), extension_loaded('pdo') ? 'loaded' : 'missing'],
            ['Storage writable', File::isWritable(storage_path()), storage_path()],
            ['Cache writable', File::isWritable(base_path('bootstrap/cache')), base_path('bootstrap/cache')],
        ];

        try {
            DB::connection()->getPdo();
            $checks[] = ['Database connection', true, DB::connection()->getDriverName()];

            if (DB::connection()->getDriverName() === 'sqlite') {
                $result = DB::selectOne('PRAGMA quick_check');
                $value = $result ? (array) $result : [];
                $integrity = reset($value);
                $checks[] = ['SQLite integrity', $integrity === 'ok', (string) $integrity];
            }
        } catch (Throwable $exception) {
            $checks[] = ['Database connection', false, $exception->getMessage()];
        }

        Artisan::call('migrate:status', ['--no-interaction' => true]);
        $migrationOutput = Artisan::output();
        $pending = str_contains($migrationOutput, 'Pending');
        $checks[] = ['Database migrations', ! $pending, $pending ? 'pending migrations found' : 'up to date'];

        $this->table(['Check', 'Result', 'Details'], array_map(
            fn (array $check) => [$check[0], $check[1] ? 'PASS' : 'FAIL', $check[2]],
            $checks
        ));

        if (collect($checks)->contains(fn (array $check) => ! $check[1])) {
            $this->components->error('System integrity checks failed.');

            return self::FAILURE;
        }

        $this->components->info('All system integrity checks passed.');

        return self::SUCCESS;
    }
}
