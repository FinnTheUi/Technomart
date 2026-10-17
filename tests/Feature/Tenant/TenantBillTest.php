<?php

namespace Tests\Feature\Tenant;

use App\Enums\PaymentStatus;
use App\Models\ElectricBill;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantBillTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;

    private User $manager;

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

    public function test_tenant_can_submit_a_gcash_reference_for_verification(): void
    {
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'manager_id' => $this->manager->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'due_date' => '2026-09-01',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->tenant)
            ->patch("/billing/payment/{$payment->id}/submit", ['reference_number' => '1234 567 890123']);

        $response->assertSessionHas('success');
        $payment->refresh();
        // Submitting a reference never marks the bill paid; a manager must verify it.
        $this->assertSame(PaymentStatus::ForVerification, $payment->status);
        $this->assertSame('1234567890123', $payment->reference_number);
        $this->assertNull($payment->paid_date);
        $this->assertNull($payment->receipt_number);
    }

    public function test_tenant_cannot_pay_another_tenants_bill(): void
    {
        $otherTenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $payment = Payment::create([
            'tenant_id' => $otherTenant->id,
            'manager_id' => $this->manager->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'due_date' => '2026-09-01',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->tenant)
            ->patch("/billing/payment/{$payment->id}/submit", ['reference_number' => '1234 567 890123']);

        $response->assertStatus(403);
    }

    public function test_tenant_cannot_pay_an_already_paid_bill(): void
    {
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'manager_id' => $this->manager->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'due_date' => '2026-09-01',
            'status' => 'paid',
            'paid_date' => now(),
        ]);

        $response = $this->actingAs($this->tenant)
            ->patch("/billing/payment/{$payment->id}/submit", ['reference_number' => '1234 567 890123']);

        $response->assertSessionHas('error', 'This bill is already paid.');
    }

    public function test_tenant_can_submit_a_gcash_reference_for_an_electric_bill(): void
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

        $response = $this->actingAs($this->tenant)
            ->patch("/billing/electric_bill/{$bill->id}/submit", ['reference_number' => '1234567890123']);

        $response->assertSessionHas('success');
        $bill->refresh();
        $this->assertSame(PaymentStatus::ForVerification, $bill->status);
        $this->assertNull($bill->paid_date);
    }

    public function test_tenant_cannot_pay_another_tenants_electric_bill(): void
    {
        $otherTenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $bill = ElectricBill::create([
            'tenant_id' => $otherTenant->id,
            'stall_id' => $this->stall->id,
            'manager_id' => $this->manager->id,
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

        $response = $this->actingAs($this->tenant)
            ->patch("/billing/electric_bill/{$bill->id}/submit", ['reference_number' => '1234567890123']);

        $response->assertStatus(403);
    }
}
