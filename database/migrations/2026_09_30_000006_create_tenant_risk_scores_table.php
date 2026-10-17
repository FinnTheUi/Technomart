<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');   // 0-100
            $table->string('level', 10);            // Low / Medium / High
            $table->string('source', 10);           // model | history
            $table->json('factors')->nullable();    // human-readable reasons
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'manager_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_risk_scores');
    }
};
