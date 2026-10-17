<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $tenant;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->tenant = User::factory()->create(['role' => 'tenant']);
        $this->stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'Stall A', 'price' => 5000, 'status' => 'occupied']);
    }

    private function bill(array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'manager_id' => $this->manager->id,
            'stall_id' => $this->stall->id,
            'amount' => 5000,
            'due_date' => today()->addWeek(),
            'status' => 'pending',
        ], $overrides));
    }

    private function submit(Payment $bill, array $data = ['reference_number' => '1234567890123'])
    {
        return $this->actingAs($this->tenant)->patch("/billing/payment/{$bill->id}/submit", $data);
    }

    public function test_submitting_a_reference_notifies_the_manager_and_awaits_verification(): void
    {
        $bill = $this->bill();

        $this->submit($bill)->assertSessionHas('success');

        $this->assertSame(PaymentStatus::ForVerification, $bill->fresh()->status);
        Notification::assertSentTo($this->manager, PaymentSubmittedNotification::class);
    }

    public function test_reference_number_must_be_13_digits(): void
    {
        $bill = $this->bill();

        $this->submit($bill, ['reference_number' => 'ABC123'])->assertSessionHasErrors('reference_number');

        $this->assertSame(PaymentStatus::Pending, $bill->fresh()->status);
    }

    public function test_a_bill_cannot_be_submitted_twice(): void
    {
        $bill = $this->bill();

        $this->submit($bill);
        $this->submit($bill, ['reference_number' => '9999999999999'])
            ->assertSessionHas('error', 'A payment for this bill is already awaiting verification.');

        $this->assertSame('1234567890123', $bill->fresh()->reference_number);
    }

    public function test_manager_verification_issues_sequential_receipt_numbers(): void
    {
        $first = $this->bill();
        $second = $this->bill();
        $this->submit($first);
        $this->submit($second, ['reference_number' => '2222222222222']);

        $this->actingAs($this->manager)->patch("/billing/payment/{$first->id}/verify")->assertSessionHas('success');
        $this->actingAs($this->manager)->patch("/billing/payment/{$second->id}/verify");

        $year = now()->year;
        $this->assertSame("OR-{$year}-000001", $first->fresh()->receipt_number);
        $this->assertSame("OR-{$year}-000002", $second->fresh()->receipt_number);
        $this->assertSame(PaymentStatus::Paid, $first->fresh()->status);
        $this->assertSame('gcash', $first->fresh()->payment_method);
        Notification::assertSentTo($this->tenant, PaymentReceivedNotification::class);
    }

    public function test_rejection_returns_the_bill_to_unpaid_with_a_reason(): void
    {
        $bill = $this->bill(['due_date' => today()->subDays(3)]);
        $this->submit($bill);

        $this->actingAs($this->manager)
            ->patch("/billing/payment/{$bill->id}/reject", ['reason' => 'Reference not found in GCash'])
            ->assertSessionHas('success');

        $bill->refresh();
        $this->assertSame(PaymentStatus::Overdue, $bill->status); // past due → back to overdue, not pending
        $this->assertSame('Reference not found in GCash', $bill->rejection_reason);
        $this->assertNull($bill->receipt_number);
        Notification::assertSentTo($this->tenant, PaymentRejectedNotification::class);

        // The tenant can submit again.
        $this->submit($bill, ['reference_number' => '3333333333333'])->assertSessionHas('success');
    }

    public function test_tenant_cannot_verify_their_own_payment(): void
    {
        $bill = $this->bill();
        $this->submit($bill);

        $this->actingAs($this->tenant)->patch("/billing/payment/{$bill->id}/verify")->assertForbidden();

        $this->assertSame(PaymentStatus::ForVerification, $bill->fresh()->status);
    }

    public function test_another_manager_cannot_verify_or_record_cash(): void
    {
        $other = User::factory()->create(['role' => 'manager']);
        $bill = $this->bill();

        $this->actingAs($other)->patch("/billing/payment/{$bill->id}/record-cash")->assertForbidden();
        $this->submit($bill);
        $this->actingAs($other)->patch("/billing/payment/{$bill->id}/verify")->assertForbidden();
    }

    public function test_receipt_is_visible_to_tenant_only_after_payment(): void
    {
        $bill = $this->bill();

        $this->actingAs($this->tenant)->get("/receipts/payment/{$bill->id}")->assertNotFound();

        $this->actingAs($this->manager)->patch("/billing/payment/{$bill->id}/record-cash");

        $this->actingAs($this->tenant)->get("/receipts/payment/{$bill->id}")
            ->assertOk()
            ->assertSee($bill->fresh()->receipt_number)
            ->assertSee('₱5,000.00', false);

        $stranger = User::factory()->create(['role' => 'tenant']);
        $this->actingAs($stranger)->get("/receipts/payment/{$bill->id}")->assertForbidden();
    }

    public function test_proof_screenshot_is_stored_privately_and_viewable_by_the_manager(): void
    {
        Storage::fake('local');
        $bill = $this->bill();

        $this->submit($bill, [
            'reference_number' => '1234567890123',
            'proof' => UploadedFile::fake()->image('gcash.png'),
        ]);

        $path = $bill->fresh()->proof_path;
        Storage::disk('local')->assertExists($path);

        $this->actingAs($this->manager)->get("/billing/payment/{$bill->id}/proof")->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'tenant']))->get("/billing/payment/{$bill->id}/proof")->assertForbidden();
    }

    public function test_unknown_payable_type_is_not_routable(): void
    {
        $this->actingAs($this->tenant)->patch('/billing/user/1/submit', ['reference_number' => '1234567890123'])->assertNotFound();
    }
}
