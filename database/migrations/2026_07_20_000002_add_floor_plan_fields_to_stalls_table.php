<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stalls', function (Blueprint $table) {
            $table->integer('floor_x')->nullable()->after('status');
            $table->integer('floor_y')->nullable()->after('floor_x');
            $table->string('floor_label', 10)->nullable()->after('floor_y');
        });
    }

    public function down(): void
    {
        Schema::table('stalls', function (Blueprint $table) {
            $table->dropColumn(['floor_x', 'floor_y', 'floor_label']);
        });
    }
};
