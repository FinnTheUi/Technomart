<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackupApplicationCommand extends Command
{
    protected $signature = 'app:backup';

    protected $description = 'Create a local ZIP backup of the SQLite database, uploads, and risk model';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->components->error('This safe local backup command currently supports SQLite only. Use mysqldump after moving to MySQL.');

            return self::FAILURE;
        }

        $database = config('database.connections.sqlite.database');
        if (! is_file($database)) {
            $this->components->error('SQLite database not found.');

            return self::FAILURE;
        }

        $directory = config('filesystems.backup_path', storage_path('app/backups'));
        File::ensureDirectoryExists($directory);
        $path = $directory.'/technomart-'.now()->format('Ymd-His').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            $this->components->error('Could not create backup archive.');

            return self::FAILURE;
        }

        $zip->addFile($database, 'database/database.sqlite');
        $this->addDirectory($zip, storage_path('app/public'), 'storage/public');
        $this->addDirectory($zip, storage_path('app/private'), 'storage/private');
        if (is_file(base_path('ml/risk_model.pkl'))) {
            $zip->addFile(base_path('ml/risk_model.pkl'), 'ml/risk_model.pkl');
        }
        $zip->close();

        $this->components->info('Backup created: '.$path);

        return self::SUCCESS;
    }

    private function addDirectory(ZipArchive $zip, string $directory, string $prefix): void
    {
        if (! is_dir($directory)) {
            return;
        }
        foreach (File::allFiles($directory) as $file) {
            $zip->addFile($file->getPathname(), $prefix.'/'.str_replace('\\', '/', $file->getRelativePathname()));
        }
    }
}
