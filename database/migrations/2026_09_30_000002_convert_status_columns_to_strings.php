<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status values are now defined by PHP enums (app/Enums). The columns become plain
 * strings so new states (e.g. "for_verification") don't require altering DB enums.
 */
return new class extends Migration
{
    private array $columns = [
        'payments' => ['status' => 'pending'],
        'electric_bills' => ['status' => 'pending'],
        'stalls' => ['status' => 'available'],
        'contracts' => ['status' => 'pending'],
        'maintenance_requests' => ['status' => 'pending'],
        'rental_applications' => ['status' => 'pending_approval', 'payment_status' => 'pending'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => $default) {
                    $blueprint->string($column, 30)->default($default)->change();
                }
            });
        }

        // Orders: "completed" (paid on the spot, no delivery tracking) becomes "delivered".
        DB::table('orders')->where('status', 'completed')->update(['status' => 'delivered']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'delivered')->update(['status' => 'completed']);
        DB::table('payments')->where('status', 'for_verification')->update(['status' => 'pending']);
        DB::table('electric_bills')->where('status', 'for_verification')->update(['status' => 'pending']);
        DB::table('rental_applications')->where('payment_status', 'for_verification')->update(['payment_status' => 'pending']);

        $enums = [
            'payments' => ['status' => [['pending', 'paid', 'overdue'], 'pending']],
            'electric_bills' => ['status' => [['pending', 'paid', 'overdue'], 'pending']],
            'stalls' => ['status' => [['available', 'occupied', 'maintenance'], 'available']],
            'contracts' => ['status' => [['active', 'expired', 'terminated', 'pending'], 'pending']],
            'maintenance_requests' => ['status' => [['pending', 'in_progress', 'resolved', 'closed'], 'pending']],
            'rental_applications' => [
                'status' => [['pending_approval', 'approved', 'rejected'], 'pending_approval'],
                'payment_status' => [['pending', 'paid'], 'pending'],
            ],
        ];

        foreach ($enums as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => [$values, $default]) {
                    $blueprint->enum($column, $values)->default($default)->change();
                }
            });
        }
    }
};
