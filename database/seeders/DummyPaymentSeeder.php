<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DummyPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = User::where('role', 'tenant')->get();
        $stalls = Stall::all();
        $managers = User::where('role', 'manager')->get();

        if ($tenants->isEmpty() || $stalls->isEmpty() || $managers->isEmpty()) {
            echo "Not enough data to seed payments.\n";

            return;
        }

        foreach ($tenants as $tenant) {
            $stall = $stalls->random();
            $numPayments = rand(10, 30);

            // Randomly decide if this tenant is a "good", "average", or "bad" payer
            $payerProfile = rand(1, 10);

            for ($i = $numPayments; $i >= 1; $i--) {
                $dueDate = Carbon::now()->subMonths($i)->startOfMonth()->addDays(rand(1, 5));
                $status = 'paid';
                $paidDate = null;

                // Determine payment status based on profile
                if ($payerProfile > 8) {
                    // Bad payer: high chance of overdue
                    if (rand(1, 100) > 40) {
                        $status = 'overdue';
                    } else {
                        $paidDate = $dueDate->copy()->addDays(rand(5, 20));
                    }
                } elseif ($payerProfile > 3) {
                    // Average payer: small chance of overdue
                    if (rand(1, 100) > 85) {
                        $status = 'overdue';
                    } else {
                        $paidDate = $dueDate->copy()->addDays(rand(-2, 10));
                    }
                } else {
                    // Good payer: almost never overdue
                    if (rand(1, 100) > 95) {
                        $status = 'overdue';
                    } else {
                        $paidDate = $dueDate->copy()->subDays(rand(1, 3));
                    }
                }

                Payment::create([
                    'tenant_id' => $tenant->id,
                    'manager_id' => $stall->manager_id,
                    'stall_id' => $stall->id,
                    'amount' => $stall->price,
                    'due_date' => $dueDate,
                    'paid_date' => $paidDate,
                    'status' => $status,
                    'created_at' => $dueDate->copy()->subDays(15),
                    'updated_at' => $paidDate ?? Carbon::now(),
                ]);
            }
        }
    }
}
