<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('stall_id')->constrained('stalls')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('terms')->nullable();
            $table->enum('status', ['active', 'expired', 'terminated', 'pending'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
