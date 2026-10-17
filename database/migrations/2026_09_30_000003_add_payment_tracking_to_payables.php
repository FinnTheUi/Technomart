<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment verification, gateway checkout, late fees and official receipt numbers
 * for every payable document (rent bills, electric bills, supply orders).
 */
return new class extends Migration
{
    private array $payables = ['payments', 'electric_bills', 'orders'];

    public function up(): void
    {
        foreach ($this->payables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if ($table !== 'orders') {
                    $blueprint->decimal('late_fee', 10, 2)->default(0);
                }
                $blueprint->string('payment_method', 30)->nullable();      // gcash, cash, online
                $blueprint->string('reference_number', 100)->nullable();   // GCash ref / gateway payment id
                $blueprint->string('proof_path')->nullable();              // uploaded screenshot
                $blueprint->string('gateway_checkout_id')->nullable()->index();
                $blueprint->timestamp('submitted_at')->nullable();
                $blueprint->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $blueprint->timestamp('verified_at')->nullable();
                $blueprint->string('rejection_reason')->nullable();
                $blueprint->string('receipt_number', 30)->nullable()->unique();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });

        Schema::table('rental_applications', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->change();
            $table->string('gateway_checkout_id')->nullable()->index();
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('key', 30)->primary(); // e.g. "OR-2026"
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');

        Schema::table('rental_applications', function (Blueprint $table) {
            $table->dropIndex(['gateway_checkout_id']);
            $table->dropColumn('gateway_checkout_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'delivered_at', 'cancelled_at']);
        });

        foreach ($this->payables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropConstrainedForeignId('verified_by');
                $blueprint->dropUnique(['receipt_number']);
                $blueprint->dropIndex(['gateway_checkout_id']);
                $columns = ['payment_method', 'reference_number', 'proof_path', 'gateway_checkout_id',
                    'submitted_at', 'verified_at', 'rejection_reason', 'receipt_number'];
                if ($table !== 'orders') {
                    $columns[] = 'late_fee';
                }
                $blueprint->dropColumn($columns);
            });
        }
    }
};
