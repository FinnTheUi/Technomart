<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackupApplicationCommand extends Command
{
    protected $signature = 'app:backup {--keep=10 : Number of newest backups to retain}';

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
        $password = (string) config('filesystems.backup_password', '');
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
        if ($password !== '') {
            $zip->setPassword($password);
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if ($name !== false && ! $zip->setEncryptionName($name, ZipArchive::EM_AES_256, $password)) {
                    $zip->close();
                    File::delete($path);
                    $this->components->error('Backup encryption failed; the incomplete archive was removed.');

                    return self::FAILURE;
                }
            }
        }
        $zip->close();

        if (! $this->archiveIsReadable($path, $password)) {
            File::delete($path);
            $this->components->error('Backup verification failed; the incomplete archive was removed.');

            return self::FAILURE;
        }

        File::put($path.'.sha256', hash_file('sha256', $path).'  '.basename($path).PHP_EOL);

        $removed = $this->rotateBackups($directory, max(1, (int) $this->option('keep')));

        $this->components->info('Backup created: '.$path);
        $this->components->info("Backup verified. Removed {$removed} expired archive(s).");

        return self::SUCCESS;
    }

    private function archiveIsReadable(string $path, string $password): bool
    {
        $zip = new ZipArchive;
        $opened = $zip->open($path);
        if ($opened !== true) {
            return false;
        }

        if ($password !== '') {
            $zip->setPassword($password);
        }
        $hasDatabase = $zip->locateName('database/database.sqlite') !== false
            && $zip->getFromName('database/database.sqlite') !== false;
        $zip->close();

        return $hasDatabase;
    }

    private function rotateBackups(string $directory, int $keep): int
    {
        $archives = collect(File::glob($directory.'/technomart-*.zip'))
            ->sortByDesc(fn (string $path) => File::lastModified($path))
            ->values();
        $expired = $archives->slice($keep);

        foreach ($expired as $path) {
            File::delete($path);
            File::delete($path.'.sha256');
        }

        return $expired->count();
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
