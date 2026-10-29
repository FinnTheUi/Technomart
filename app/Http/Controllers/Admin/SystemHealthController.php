<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\EncryptedSecret;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\Process\Process;
use Throwable;

class SystemHealthController extends Controller
{
    public function __invoke(): View
    {
        $checks = [];
        try {
            DB::select('select 1');
            $checks[] = $this->check('Database', true, config('database.default').' connection is working');
        } catch (Throwable $e) {
            $checks[] = $this->check('Database', false, $e->getMessage());
        }

        $link = public_path('storage');
        $checks[] = $this->check('Public uploads', is_link($link) || is_dir($link), 'public/storage link');
        $checks[] = $this->check('Private storage', is_dir(storage_path('app/private')) && is_writable(storage_path('app/private')), 'Payment evidence storage');
        $checks[] = $this->check('Mail', filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password')), 'SMTP credentials configured (delivery is not tested here)');
        $checks[] = $this->check('Google sign-in', filled(config('services.google.client_id')) && EncryptedSecret::isSet('services.google.client_secret_encrypted'), 'OAuth credentials');
        $checks[] = $this->check('PayMongo', EncryptedSecret::isSet('services.paymongo.secret_key_encrypted') && EncryptedSecret::isSet('services.paymongo.webhook_secret_encrypted'), 'Optional online payments', true);

        $python = new Process([config('services.ml.python'), '--version'], base_path(), null, null, 5);
        try {
            $python->run();
            $checks[] = $this->check('Python', $python->isSuccessful(), trim($python->getOutput().$python->getErrorOutput()));
        } catch (Throwable $e) {
            $checks[] = $this->check('Python', false, $e->getMessage());
        }
        $checks[] = $this->check('Risk model', is_file(base_path('ml/risk_model.pkl')), 'ml/risk_model.pkl');

        $checks[] = $this->check('PHP runtime', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION);
        $freeBytes = disk_free_space(storage_path());
        $checks[] = $this->check('Storage space', $freeBytes !== false && $freeBytes >= 1024 ** 3, $freeBytes === false ? 'Unable to read disk space' : number_format($freeBytes / 1024 ** 3, 1).' GB free');

        $backups = collect(File::glob(config('filesystems.backup_path').'/technomart-*.zip'));
        $latestBackup = $backups->sortByDesc(fn (string $path) => File::lastModified($path))->first();
        $backupFresh = $latestBackup && File::lastModified($latestBackup) >= now()->subDays(2)->timestamp;
        $checks[] = $this->check('Backup freshness', (bool) $backupFresh, $latestBackup ? 'Latest: '.date('Y-m-d H:i:s', File::lastModified($latestBackup)) : 'No backup found');

        $heartbeat = cache()->get('system.scheduler_last_seen');
        $fresh = $heartbeat && now()->diffInMinutes($heartbeat) <= 3;
        $checks[] = $this->check('Scheduler', $fresh, $heartbeat ? 'Last seen '.$heartbeat : 'No heartbeat yet; run php artisan schedule:work');
        $failedJobs = DB::table('failed_jobs')->count();
        $checks[] = $this->check('Queue', $failedJobs === 0, DB::table('jobs')->count().' pending / '.$failedJobs.' failed (worker activity cannot be proven while idle)', true);

        return view('admin.system-health', ['checks' => $checks]);
    }

    private function check(string $name, bool $ok, string $detail, bool $optional = false): array
    {
        return compact('name', 'ok', 'detail', 'optional');
    }
}

