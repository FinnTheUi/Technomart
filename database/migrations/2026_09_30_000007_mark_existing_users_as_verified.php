<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email verification is now enforced. Accounts that existed before the rollout are
 * grandfathered in as verified so nobody is locked out; new accounts must verify.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Irreversible data backfill.
    }
};
