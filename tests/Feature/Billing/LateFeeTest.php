<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentStatus;
use App\Models\ElectricBill;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use App\Notifications\OverduePaymentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LateFeeTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $tenant;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['billing.late_fee' => ['percent' => 5, 'fixed' => 50, 'grace_days' => 0]]);

        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->tenant = User::factory()->create(['role' => 'tenant']);
        $this->stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'Stall A', 'price' => 5000, 'status' => 'occupied']);
    }

    private function rent(string $dueDate, string $status = 'pending'): Payment
    {
        return Payment::create([
            'tenant_id' => $this->tenant->id, 'manager_id' => $this->manager->id, 'stall_id' => $this->stall->id,
            'amount' => 5000, 'due_date' => $dueDate, 'status' => $status,
        ]);
    }

    public function test_daily_job_marks_past_due_bills_overdue_with_a_late_fee(): void
    {
        $late = $this->rent(today()->subDay()->toDateString());
        $notYetDue = $this->rent(today()->addDay()->toDateString());

        $this->artisan('app:analyze-payments')->assertSuccessful();

        $late->refresh();
        $this->assertSame(PaymentStatus::Overdue, $late->status);
        $this->assertEquals(300.00, $late->lateFee()); // 5% of 5000 + 50
        $this->assertEquals(5300.00, $late->amountDue());
        $this->assertSame(PaymentStatus::Pending, $notYetDue->fresh()->status);
        Notification::assertSentTo($this->tenant, OverduePaymentNotification::class);
    }

    public function test_late_fee_is_applied_only_once(): void
    {
        $bill = $this->rent(today()->subDays(10)->toDateString());

        $this->artisan('app:analyze-payments');
        $this->actingAs($this->manager)->patch("/manager/payments/{$bill->id}/overdue");
        $this->artisan('app:analyze-payments');

        $this->assertEquals(300.00, $bill->fresh()->lateFee());
    }

    public function test_grace_period_delays_overdue(): void
    {
        config(['billing.late_fee.grace_days' => 3]);
        $bill = $this->rent(today()->subDays(2)->toDateString());

        $this->artisan('app:analyze-payments');

        $this->assertSame(PaymentStatus::Pending, $bill->fresh()->status);
    }

    public function test_electric_bills_also_become_overdue(): void
    {
        $bill = ElectricBill::create([
            'tenant_id' => $this->tenant->id, 'stall_id' => $this->stall->id, 'manager_id' => $this->manager->id,
            'period_from' => today()->subMonth(), 'period_to' => today()->subDays(5), 'billing_date' => today()->subDays(4),
            'reading_present' => 200, 'reading_previous' => 100, 'kwh_used' => 100, 'rate_per_kwh' => 10,
            'total_amount' => 1000, 'due_date' => today()->subDay(), 'status' => 'pending',
        ]);

        $this->artisan('app:analyze-payments');

        $bill->refresh();
        $this->assertSame(PaymentStatus::Overdue, $bill->status);
        $this->assertEquals(100.00, $bill->lateFee()); // 5% of 1000 + 50
    }

    public function test_bills_awaiting_verification_are_not_marked_overdue(): void
    {
        $bill = $this->rent(today()->subDay()->toDateString(), 'for_verification');

        $this->artisan('app:analyze-payments');

        $this->assertSame(PaymentStatus::ForVerification, $bill->fresh()->status);
        $this->assertEquals(0, $bill->fresh()->lateFee());
    }

    public function test_receipt_and_verification_include_the_late_fee(): void
    {
        $bill = $this->rent(today()->subDay()->toDateString());
        $this->artisan('app:analyze-payments');

        $this->actingAs($this->manager)->patch("/billing/payment/{$bill->id}/record-cash");

        $this->actingAs($this->tenant)->get("/receipts/payment/{$bill->id}")
            ->assertOk()
            ->assertSee('Late payment fee')
            ->assertSee('₱5,300.00', false);
    }
}
