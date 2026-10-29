<?php

namespace Tests\Feature\Manager;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerPaymentTest extends TestCase
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
            'name' => 'Test Stall',
            'price' => 5000,
            'status' => 'occupied',
        ]);
        \App\Models\Contract::create([
            'tenant_id' => $this->tenant->id, 'stall_id' => $this->stall->id,
            'start_date' => today(), 'end_date' => today()->addYear(), 'status' => 'active',
        ]);
    }

    public function test_manager_can_create_a_payment_bill(): void
    {
        $response = $this->actingAs($this->manager)->post('/manager/payments', [
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'due_date' => '2026-09-01',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'manager_id' => $this->manager->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'status' => 'pending',
        ]);
    }

    public function test_manager_cannot_create_payment_with_missing_fields(): void
    {
        $response = $this->actingAs($this->manager)->post('/manager/payments', [
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertSessionHasErrors(['stall_id', 'amount', 'due_date']);
    }

    public function test_payment_options_are_linked_by_active_leases_and_scoped_to_the_manager(): void
    {
        $otherManager = User::factory()->create(['role' => 'manager']);
        $otherTenant = User::factory()->create(['role' => 'tenant']);
        $otherStall = Stall::create(['manager_id' => $otherManager->id, 'name' => 'Other Stall', 'price' => 1000, 'status' => 'occupied']);
        \App\Models\Contract::create(['tenant_id' => $otherTenant->id, 'stall_id' => $otherStall->id, 'start_date' => today(), 'end_date' => today()->addYear(), 'status' => 'active']);

        $this->actingAs($this->manager)->get('/manager/payments')->assertOk()
            ->assertViewHas('tenantStallAssignments', fn ($items) => $items->count() === 1
                && $items->first()['tenant_id'] === $this->tenant->id && $items->first()['stall_id'] === $this->stall->id)
            ->assertViewHas('tenants', fn ($users) => $users->pluck('id')->all() === [$this->tenant->id])
            ->assertSee('Manage GCash QR');
    }

    public function test_mismatched_tenant_and_stall_cannot_be_billed(): void
    {
        $otherTenant = User::factory()->create(['role' => 'tenant']);
        $this->actingAs($this->manager)->post('/manager/payments', [
            'tenant_id' => $otherTenant->id, 'stall_id' => $this->stall->id,
            'amount' => 1000, 'due_date' => today()->toDateString(),
        ])->assertSessionHasErrors('stall_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_manager_can_record_a_cash_payment(): void
    {
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'manager_id' => $this->manager->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'due_date' => '2026-09-01',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)->patch("/billing/payment/{$payment->id}/record-cash");

        $response->assertSessionHas('success');
        $payment->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('cash', $payment->payment_method);
        $this->assertNotNull($payment->paid_date);
        $this->assertMatchesRegularExpression('/^OR-\d{4}-\d{6}$/', $payment->receipt_number);
        $this->assertSame($this->manager->id, $payment->verified_by);
    }

    public function test_manager_can_mark_payment_as_overdue(): void
    {
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'manager_id' => $this->manager->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'due_date' => '2026-08-01',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)->patch("/manager/payments/{$payment->id}/overdue");

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'overdue',
        ]);
    }

    public function test_manager_cannot_mark_another_managers_payment(): void
    {
        $otherManager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'manager_id' => $otherManager->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'due_date' => '2026-09-01',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)->patch("/billing/payment/{$payment->id}/record-cash");

        $response->assertStatus(403);
    }
}
