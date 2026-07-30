<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stall_id')->constrained('stalls')->onDelete('cascade');
            $table->string('email');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->enum('status', ['waiting', 'notified', 'expired'])->default('waiting');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['stall_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlists');
    }
};
