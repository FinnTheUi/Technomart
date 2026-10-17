<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electric_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('stall_id')->constrained('stalls')->onDelete('cascade');
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
            $table->string('meter_number')->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->date('billing_date');
            $table->decimal('reading_present', 12, 2);
            $table->decimal('reading_previous', 12, 2);
            $table->decimal('kwh_used', 12, 2);
            $table->decimal('rate_per_kwh', 10, 2)->default(11.36);
            $table->decimal('total_amount', 12, 2);
            $table->date('due_date');
            $table->string('prepared_by')->nullable();
            $table->string('noted_by')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->datetime('paid_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electric_bills');
    }
};
