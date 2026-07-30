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
        Schema::create('rental_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stall_id')->constrained('stalls')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->text('address');
            $table->integer('rental_duration'); // months
            $table->enum('payment_method', ['gcash', 'in_person'])->nullable();
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('pending_approval');
            $table->string('gcash_reference')->nullable();
            $table->string('generated_username')->nullable();
            $table->string('generated_password')->nullable(); // plaintext for email, hashed in users table
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_applications');
    }
};
