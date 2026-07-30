<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->timestamp('signed_at')->nullable()->after('status');
            $table->string('signed_ip')->nullable()->after('signed_at');
            $table->text('signed_user_agent')->nullable()->after('signed_ip');
            $table->string('document_hash')->nullable()->after('signed_user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['signed_at', 'signed_ip', 'signed_user_agent', 'document_hash']);
        });
    }
};
