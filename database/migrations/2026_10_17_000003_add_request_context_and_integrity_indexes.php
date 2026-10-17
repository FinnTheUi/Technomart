<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->uuid('request_id')->nullable()->index()->after('properties');
            $table->string('ip_address', 45)->nullable()->after('request_id');
            $table->string('user_agent', 500)->nullable()->after('ip_address');
        });

        foreach (['payments', 'electric_bills', 'orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['status', 'created_at']);
                $table->index(['reference_number', 'status']);
            });
        }
    }

    public function down(): void
    {
        foreach (['payments', 'electric_bills', 'orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['status', 'created_at']);
                $table->dropIndex(['reference_number', 'status']);
            });
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropColumn(['request_id', 'ip_address', 'user_agent']);
        });
    }
};
