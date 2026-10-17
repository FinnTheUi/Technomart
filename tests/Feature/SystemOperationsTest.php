<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TenantRiskScore;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class SystemOperationsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backup_command_creates_readable_archive_with_database(): void
    {
        $root = storage_path('framework/testing/backup-'.uniqid());
        File::ensureDirectoryExists($root);
        $database = $root.'/database.sqlite';
        File::put($database, 'sqlite-test-content');
        $originalDatabase = config('database.connections.sqlite.database');
        $originalBackupPath = config('filesystems.backup_path');
        $originalBackupPassword = config('filesystems.backup_password');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $database,
            'filesystems.backup_path' => $root.'/archives',
            'filesystems.backup_password' => '',
        ]);

        try {
            $this->artisan('app:backup')->assertSuccessful();
            $archives = File::glob($root.'/archives/*.zip');
            $this->assertCount(1, $archives);
            $this->assertFileExists($archives[0].'.sha256');
            $this->assertStringStartsWith(hash_file('sha256', $archives[0]), File::get($archives[0].'.sha256'));

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($archives[0]) === true);
            $this->assertNotFalse($zip->locateName('database/database.sqlite'));
            $zip->close();
        } finally {
            config([
                'database.connections.sqlite.database' => $originalDatabase,
                'filesystems.backup_path' => $originalBackupPath,
                'filesystems.backup_password' => $originalBackupPassword,
            ]);
            File::deleteDirectory($root);
        }
    }

    public function test_backup_can_encrypt_and_verify_the_database(): void
    {
        $root = storage_path('framework/testing/encrypted-backup-'.uniqid());
        File::ensureDirectoryExists($root);
        $database = $root.'/database.sqlite';
        File::put($database, 'sensitive-content');
        $original = [
            'database' => config('database.connections.sqlite.database'),
            'path' => config('filesystems.backup_path'),
            'password' => config('filesystems.backup_password'),
        ];
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $database,
            'filesystems.backup_path' => $root.'/archives',
            'filesystems.backup_password' => 'test-backup-password',
        ]);

        try {
            $this->artisan('app:backup')->assertSuccessful();
            $archive = File::glob($root.'/archives/*.zip')[0];
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($archive) === true);
            $this->assertFalse($zip->getFromName('database/database.sqlite'));
            $zip->setPassword('test-backup-password');
            $this->assertSame('sensitive-content', $zip->getFromName('database/database.sqlite'));
            $zip->close();
        } finally {
            config([
                'database.connections.sqlite.database' => $original['database'],
                'filesystems.backup_path' => $original['path'],
                'filesystems.backup_password' => $original['password'],
            ]);
            File::deleteDirectory($root);
        }
    }

    public function test_backup_command_fails_safely_for_unsupported_database(): void
    {
        $original = config('database.default');
        try {
            config(['database.default' => 'mysql']);
            $this->artisan('app:backup')
                ->expectsOutputToContain('supports SQLite only')
                ->assertFailed();
        } finally {
            config(['database.default' => $original]);
        }
    }

    public function test_backup_rotation_retains_only_requested_number_of_archives(): void
    {
        $root = storage_path('framework/testing/rotation-'.uniqid());
        File::ensureDirectoryExists($root.'/archives');
        $database = $root.'/database.sqlite';
        File::put($database, 'sqlite-test-content');
        $originalDatabase = config('database.connections.sqlite.database');
        $originalBackupPath = config('filesystems.backup_path');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $database,
            'filesystems.backup_path' => $root.'/archives',
        ]);

        try {
            foreach ([1, 2, 3] as $index) {
                $old = $root.'/archives/technomart-20200101-00000'.$index.'.zip';
                File::put($old, 'old');
                touch($old, time() - (100 + $index));
            }

            $this->artisan('app:backup', ['--keep' => 2])->assertSuccessful();
            $this->assertCount(2, File::glob($root.'/archives/technomart-*.zip'));
        } finally {
            config([
                'database.connections.sqlite.database' => $originalDatabase,
                'filesystems.backup_path' => $originalBackupPath,
            ]);
            File::deleteDirectory($root);
        }
    }

    public function test_system_doctor_reports_a_healthy_test_environment(): void
    {
        $this->artisan('app:doctor')
            ->expectsOutputToContain('All system integrity checks passed')
            ->assertSuccessful();
    }

    public function test_release_safeguards_are_present(): void
    {
        $javascript = File::get(resource_path('js/app.js'));
        $phpunit = File::get(base_path('phpunit.xml'));
        $templates = collect(File::allFiles(resource_path('views')))
            ->map(fn ($file) => File::get($file->getPathname()))
            ->implode("\n");

        $this->assertStringContainsString("form.dataset.submitting === 'true'", $javascript);
        $this->assertStringContainsString('DB_DATABASE" value=":memory:"', $phpunit);
        $this->assertStringNotContainsString('fonts.googleapis.com', $templates);
        $this->assertStringNotContainsString('fonts.gstatic.com', $templates);
    }

    public function test_demo_seeder_is_idempotent_and_builds_complete_scenarios(): void
    {
        $originalEnvironment = $this->app->environment();
        $this->app->detectEnvironment(fn () => 'local');
        config(['services.ml.python' => 'missing-python-for-test']);

        try {
            $this->seed(DemoDataSeeder::class);
            $counts = [
                'users' => User::count(),
                'contracts' => Contract::count(),
                'payments' => Payment::count(),
                'products' => Product::count(),
                'orders' => Order::count(),
            ];

            $this->seed(DemoDataSeeder::class);

            $this->assertSame($counts['users'], User::count());
            $this->assertSame($counts['contracts'], Contract::count());
            $this->assertSame($counts['payments'], Payment::count());
            $this->assertSame($counts['products'], Product::count());
            $this->assertSame($counts['orders'], Order::count());
            $this->assertSame(30, Payment::count());
            $this->assertSame(3, TenantRiskScore::count());
        } finally {
            $this->app->detectEnvironment(fn () => $originalEnvironment);
        }
    }
}
