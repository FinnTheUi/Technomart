<?php

namespace Tests\Feature\Manager;

use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Enums\StallStatus;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use App\Models\Waitlist;
use App\Notifications\LeaseStatusNotification;
use App\Notifications\StallAvailableNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeaseLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $tenant;

    private Stall $stall;

    private Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->tenant = User::factory()->create(['role' => 'tenant']);
        $this->stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'Stall A', 'price' => 4000, 'status' => 'occupied']);
        $this->contract = Contract::create([
            'tenant_id' => $this->tenant->id, 'stall_id' => $this->stall->id,
            'start_date' => today()->subMonths(2), 'end_date' => today()->addMonth(), 'status' => 'active',
        ]);
    }

    public function test_tenant_can_request_a_renewal_and_the_manager_is_notified(): void
    {
        $this->actingAs($this->tenant)
            ->post("/tenant/contracts/{$this->contract->id}/renewal", ['months' => 6])
            ->assertSessionHas('success');

        $this->assertSame(6, $this->contract->fresh()->renewal_months_requested);
        Notification::assertSentTo($this->manager, LeaseStatusNotification::class, fn ($n) => $n->event === LeaseStatusNotification::RENEWAL_REQUESTED);
    }

    public function test_manager_renewal_extends_the_lease_and_bills_the_new_months(): void
    {
        $oldEnd = $this->contract->end_date->copy();
        $this->contract->update(['renewal_requested_at' => now(), 'renewal_months_requested' => 3]);

        $this->actingAs($this->manager)
            ->post("/manager/contracts/{$this->contract->id}/renew", ['months' => 3])
            ->assertSessionHas('success');

        $contract = $this->contract->fresh();
        $this->assertTrue($contract->end_date->equalTo($oldEnd->copy()->addMonths(3)));
        $this->assertNull($contract->renewal_requested_at);

        $bills = Payment::where('tenant_id', $this->tenant->id)->orderBy('due_date')->get();
        $this->assertCount(3, $bills);
        $this->assertTrue($bills->first()->due_date->equalTo($oldEnd));
        $this->assertEquals(4000, $bills->first()->amount);
        Notification::assertSentTo($this->tenant, LeaseStatusNotification::class, fn ($n) => $n->event === LeaseStatusNotification::RENEWED);
    }

    public function test_termination_frees_the_stall_cancels_future_rent_and_emails_guest_waitlisters(): void
    {
        $past = Payment::create(['tenant_id' => $this->tenant->id, 'manager_id' => $this->manager->id, 'stall_id' => $this->stall->id, 'amount' => 4000, 'due_date' => today()->subMonth(), 'status' => 'overdue']);
        $future = Payment::create(['tenant_id' => $this->tenant->id, 'manager_id' => $this->manager->id, 'stall_id' => $this->stall->id, 'amount' => 4000, 'due_date' => today()->addWeeks(2), 'status' => 'pending']);
        Waitlist::create(['stall_id' => $this->stall->id, 'name' => 'Guest', 'email' => 'guest.waiter@gmail.com', 'status' => 'waiting']);

        $this->actingAs($this->manager)
            ->post("/manager/contracts/{$this->contract->id}/terminate", ['reason' => 'Tenant moved out'])
            ->assertSessionHas('success');

        $this->assertSame(ContractStatus::Terminated, $this->contract->fresh()->status);
        $this->assertSame(StallStatus::Available, $this->stall->fresh()->status);
        $this->assertModelMissing($future);
        $this->assertModelExists($past); // money already owed is kept

        Notification::assertSentOnDemand(StallAvailableNotification::class, function ($notification, $channels, AnonymousNotifiable $notifiable) {
            return array_key_exists('guest.waiter@gmail.com', (array) $notifiable->routes['mail']) && $channels === ['mail'];
        });
        $this->assertSame('notified', Waitlist::first()->status);
    }

    public function test_ended_leases_expire_nightly(): void
    {
        $this->contract->update(['end_date' => today()->subDay()]);

        $this->artisan('leases:expire')->assertSuccessful();

        $this->assertSame(ContractStatus::Expired, $this->contract->fresh()->status);
        $this->assertSame(StallStatus::Available, $this->stall->fresh()->status);
        Notification::assertSentTo($this->tenant, LeaseStatusNotification::class, fn ($n) => $n->event === LeaseStatusNotification::EXPIRED);
    }

    public function test_other_managers_cannot_renew_or_terminate(): void
    {
        $other = User::factory()->create(['role' => 'manager']);

        $this->actingAs($other)->post("/manager/contracts/{$this->contract->id}/renew", ['months' => 1])->assertForbidden();
        $this->actingAs($other)->post("/manager/contracts/{$this->contract->id}/terminate", ['reason' => 'x'])->assertForbidden();
        $this->actingAs($other)->get("/manager/contracts/{$this->contract->id}/pdf")->assertForbidden();
    }

    public function test_manager_can_print_leases_for_their_stalls(): void
    {
        $this->actingAs($this->manager)->get('/manager/contracts')->assertOk()->assertSee($this->tenant->name);
        $this->actingAs($this->manager)->get("/manager/contracts/{$this->contract->id}/pdf")->assertOk();
    }

    public function test_stall_with_active_lease_cannot_be_marked_available_by_hand(): void
    {
        $this->actingAs($this->manager)
            ->patch("/manager/stalls/{$this->stall->id}/status", ['status' => 'available'])
            ->assertSessionHas('error');

        $this->assertSame(StallStatus::Occupied, $this->stall->fresh()->status);
    }

    public function test_marking_a_stall_available_notifies_guest_waitlisters(): void
    {
        $this->contract->update(['status' => 'expired']);
        Waitlist::create(['stall_id' => $this->stall->id, 'name' => 'Guest', 'email' => 'guest@gmail.com', 'status' => 'waiting']);

        $this->actingAs($this->manager)->patch("/manager/stalls/{$this->stall->id}/status", ['status' => 'available']);

        Notification::assertSentOnDemand(StallAvailableNotification::class);
    }

    public function test_renewal_is_rejected_for_inactive_leases(): void
    {
        $this->contract->update(['status' => ContractStatus::Expired]);

        $this->actingAs($this->manager)->post("/manager/contracts/{$this->contract->id}/renew", ['months' => 1])
            ->assertSessionHas('error', 'Only active leases can be renewed.');

        $this->assertSame(0, Payment::where('status', PaymentStatus::Pending)->count());
    }
}
