<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the initial administrator account.
     *
     * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD. If no password is set,
     * a random one is generated and printed once.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@gmail.com');

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Admin account {$email} already exists; skipping.");

            return;
        }

        $password = env('ADMIN_PASSWORD') ?: Str::password(16);

        User::create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ])->forceFill(['email_verified_at' => now()])->save();

        $this->command?->info("Admin account created: {$email}");

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Generated password (shown once): {$password}");
        }
    }
}
