<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // e.g. 'created', 'updated', 'deleted', 'approved', 'rejected'
            $table->text('description');
            $table->string('subject_type')->nullable(); // morphable model class
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable(); // extra context data
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
