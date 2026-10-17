<?php

namespace Tests\Feature\Manager;

use App\Models\User;
use App\Models\Stall;
use App\Models\ElectricBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Enums\PaymentStatus;

class ManagerElectricBillTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $tenant;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $this->stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Stall A',
            'price' => 5000,
            'status' => 'occupied',
        ]);
    }

    public function test_manager_can_create_an_electric_bill(): void
    {
        $response = $this->actingAs($this->manager)->post('/manager/electric-bills', [
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'meter_number' => 'MTR-001',
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'billing_date' => '2026-08-01',
            'reading_present' => 500.00,
            'reading_previous' => 400.00,
            'rate_per_kwh' => 12.50,
            'due_date' => '2026-08-15',
            'prepared_by' => 'Manager',
            'noted_by' => 'Admin',
        ]);

        $response->assertSessionHas('success');

        // kWh used = 500 - 400 = 100, total = 100 * 12.50 = 1250.00
        $this->assertDatabaseHas('electric_bills', [
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'kwh_used' => 100.00,
            'total_amount' => 1250.00,
            'status' => 'pending',
        ]);
    }

    public function test_manager_cannot_create_electric_bill_with_missing_fields(): void
    {
        $response = $this->actingAs($this->manager)->post('/manager/electric-bills', [
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertSessionHasErrors([
            'stall_id', 'period_from', 'period_to', 'billing_date',
            'reading_present', 'reading_previous', 'rate_per_kwh', 'due_date',
        ]);
    }

    public function test_manager_can_record_a_cash_payment_for_an_electric_bill(): void
    {
        $bill = ElectricBill::create([
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'manager_id' => $this->manager->id,
            'meter_number' => 'MTR-001',
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'billing_date' => '2026-08-01',
            'reading_present' => 500,
            'reading_previous' => 400,
            'kwh_used' => 100,
            'rate_per_kwh' => 12.50,
            'total_amount' => 1250,
            'due_date' => '2026-08-15',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->patch("/billing/electric_bill/{$bill->id}/record-cash");

        $response->assertSessionHas('success');
        $bill->refresh();
        $this->assertSame(PaymentStatus::Paid, $bill->status);
        $this->assertNotNull($bill->paid_date);
        $this->assertNotNull($bill->receipt_number);
    }

    public function test_manager_cannot_mark_another_managers_electric_bill(): void
    {
        $otherManager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $bill = ElectricBill::create([
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'manager_id' => $otherManager->id,
            'meter_number' => 'MTR-002',
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'billing_date' => '2026-08-01',
            'reading_present' => 600,
            'reading_previous' => 500,
            'kwh_used' => 100,
            'rate_per_kwh' => 12.50,
            'total_amount' => 1250,
            'due_date' => '2026-08-15',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->patch("/billing/electric_bill/{$bill->id}/record-cash");

        $response->assertStatus(403);
    }
}
