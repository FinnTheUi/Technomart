<?php

namespace Tests\Feature\Billing;

use App\Models\ElectricBill;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_issues_working_receipts_for_all_historical_paid_types(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'Receipt Stall', 'price' => 5000, 'status' => 'occupied']);
        $product = Product::create(['name' => 'Receipt Product', 'price' => 100, 'stock' => 10, 'is_active' => true]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id, 'manager_id' => $manager->id, 'stall_id' => $stall->id,
            'amount' => 5000, 'due_date' => today(), 'paid_date' => now(), 'status' => 'paid', 'payment_method' => 'cash',
        ]);
        $electricBill = ElectricBill::create([
            'tenant_id' => $tenant->id, 'manager_id' => $manager->id, 'stall_id' => $stall->id,
            'period_from' => today()->startOfMonth(), 'period_to' => today()->endOfMonth(), 'billing_date' => today(),
            'reading_previous' => 100, 'reading_present' => 110, 'kwh_used' => 10, 'rate_per_kwh' => 11.36,
            'total_amount' => 113.60, 'due_date' => today(), 'paid_date' => now(), 'status' => 'paid', 'payment_method' => 'cash',
        ]);
        $order = Order::create([
            'user_id' => $tenant->id, 'product_id' => $product->id, 'quantity' => 1, 'total_price' => 100,
            'status' => 'delivered', 'paid_at' => now(), 'delivered_at' => now(), 'payment_method' => 'cash',
        ]);

        $this->artisan('receipts:backfill')->assertSuccessful()->expectsOutputToContain('Issued 3');

        foreach ([
            'payment' => $payment,
            'electric_bill' => $electricBill,
            'order' => $order,
        ] as $type => $payable) {
            $receiptNumber = $payable->fresh()->receipt_number;
            $this->assertNotNull($receiptNumber);
            $this->actingAs($tenant)->get("/receipts/{$type}/{$payable->id}")
                ->assertOk()
                ->assertSee($receiptNumber);
        }
    }

    public function test_backfill_is_idempotent(): void
    {
        $this->artisan('receipts:backfill')->assertSuccessful()->expectsOutputToContain('All paid records already have receipt numbers.');
    }
}
