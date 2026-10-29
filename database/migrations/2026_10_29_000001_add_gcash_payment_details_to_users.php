<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gcash_qr_path')->nullable();
            $table->string('gcash_account_name', 100)->nullable();
            $table->string('gcash_account_number', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['gcash_qr_path', 'gcash_account_name', 'gcash_account_number']));
    }
};
