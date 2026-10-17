<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->timestamp('renewal_requested_at')->nullable();
            $table->unsignedTinyInteger('renewal_months_requested')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['renewal_requested_at', 'renewal_months_requested', 'terminated_at', 'termination_reason']);
        });
    }
};
