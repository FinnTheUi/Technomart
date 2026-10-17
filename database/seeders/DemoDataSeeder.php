<?php

namespace Database\Seeders;

use App\Enums\ContractStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StallStatus;
use App\Enums\StockMovementType;
use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stall;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Billing\ReceiptBackfillService;
use App\Services\Rental\StallLayoutService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command?->error('Demo data is restricted to the local environment.');

            return;
        }

        DB::transaction(function (): void {
            $manager = User::updateOrCreate(['email' => 'manager.demo@gmail.com'], [
                'name' => 'Demo Manager', 'password' => 'Password123!', 'role' => User::ROLE_MANAGER,
                'is_active' => true, 'email_verified_at' => now(), 'privacy_consented_at' => now(),
            ]);

            app(StallLayoutService::class)->sync($manager);
            $stalls = Stall::where('manager_id', $manager->id)->orderBy('id')->take(3)->get();
            if ($stalls->count() < 3) {
                $stalls = Stall::orderBy('id')->take(3)->get();
            }

            foreach (['Ana Santos', 'Ben Reyes', 'Carla Cruz'] as $index => $name) {
                $tenant = User::updateOrCreate(['email' => 'tenant'.($index + 1).'.demo@gmail.com'], [
                    'name' => $name, 'password' => 'Password123!', 'role' => User::ROLE_TENANT,
                    'is_active' => true, 'email_verified_at' => now(), 'privacy_consented_at' => now(),
                ]);
                $stall = $stalls[$index];
                $stall->update(['manager_id' => $manager->id, 'status' => StallStatus::Occupied]);
                Contract::updateOrCreate(['tenant_id' => $tenant->id, 'stall_id' => $stall->id], [
                    'start_date' => today()->subMonths(6), 'end_date' => today()->addMonths(6),
                    'terms' => 'Standard Technomart demonstration lease.', 'status' => ContractStatus::Active,
                    'signed_at' => now()->subMonths(6),
                ]);

                for ($month = 9; $month >= 0; $month--) {
                    $due = today()->subMonths($month)->startOfMonth()->addDays(4);
                    $late = ($index === 1 && $month % 2 === 0) || ($index === 2 && $month >= 4);
                    $overdue = $month === 0 && $index === 2;
                    Payment::updateOrCreate(
                        ['tenant_id' => $tenant->id, 'stall_id' => $stall->id, 'due_date' => $due],
                        ['manager_id' => $manager->id, 'amount' => $stall->price, 'late_fee' => $overdue ? 250 : 0,
                            'status' => $overdue ? PaymentStatus::Overdue : PaymentStatus::Paid,
                            'paid_date' => $overdue ? null : $due->copy()->addDays($late ? 8 : -1),
                            'payment_method' => $overdue ? null : 'cash'],
                    );
                }

                MaintenanceRequest::updateOrCreate(['tenant_id' => $tenant->id, 'stall_id' => $stall->id, 'subject' => 'Demo maintenance request'], [
                    'description' => 'Example request for the capstone demonstration.', 'priority' => ['low', 'high', 'urgent'][$index],
                    'status' => ['resolved', 'in_progress', 'pending'][$index],
                ]);
            }

            foreach ([['LED Bulb', 120, 25, 10], ['Extension Cord', 350, 8, 10], ['Cleaning Kit', 220, 18, 5]] as [$name, $price, $stock, $reorder]) {
                $product = Product::updateOrCreate(['name' => $name], [
                    'description' => 'Demonstration supply item', 'price' => $price, 'stock' => $stock,
                    'category' => 'Stall Supplies', 'is_active' => true, 'reorder_level' => $reorder,
                ]);
                StockMovement::firstOrCreate(['product_id' => $product->id, 'type' => StockMovementType::Initial], [
                    'quantity' => $stock, 'balance_after' => $stock, 'user_id' => $manager->id, 'note' => 'Demo opening balance',
                ]);
            }

            $tenant = User::where('email', 'tenant1.demo@gmail.com')->first();
            foreach (Product::whereIn('name', ['LED Bulb', 'Extension Cord'])->get() as $product) {
                Order::updateOrCreate(['user_id' => $tenant->id, 'product_id' => $product->id], [
                    'quantity' => 2, 'total_price' => (float) $product->price * 2,
                    'status' => OrderStatus::Delivered, 'paid_at' => now()->subDays(4), 'delivered_at' => now()->subDays(2),
                    'payment_method' => 'cash',
                ]);
            }
        });

        app(ReceiptBackfillService::class)->run();
        Artisan::call('ml:score');
        $this->command?->info('Demo accounts use password: Password123!');
    }
}
