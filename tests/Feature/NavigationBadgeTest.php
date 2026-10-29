<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Models\Contract;
use App\Models\ElectricBill;
use App\Models\Message;
use App\Models\Stall;
use App\Models\User;
use App\Notifications\BillIssuedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NavigationBadgeTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $tenant;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_active' => true]);
        $this->tenant = User::factory()->create(['role' => User::ROLE_TENANT, 'is_active' => true]);
        $this->stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Stall A',
            'price' => 5000,
            'status' => 'occupied',
        ]);

        Contract::create([
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'start_date' => today()->startOfMonth(),
            'end_date' => today()->addYear(),
            'terms' => 'Test lease',
            'status' => ContractStatus::Active,
        ]);
    }

    public function test_issuing_an_electric_bill_notifies_the_tenant(): void
    {
        Notification::fake();

        $this->actingAs($this->manager)->post(route('manager.electric-bills.store'), [
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'meter_number' => 'M-100',
            'period_from' => today()->startOfMonth()->toDateString(),
            'period_to' => today()->endOfMonth()->toDateString(),
            'billing_date' => today()->toDateString(),
            'reading_previous' => 100,
            'reading_present' => 125,
            'rate_per_kwh' => 12,
            'due_date' => today()->addDays(10)->toDateString(),
        ])->assertSessionHas('success');

        Notification::assertSentTo(
            $this->tenant,
            BillIssuedNotification::class,
            fn (BillIssuedNotification $notification) => $notification->bill::payableType() === 'electric_bill'
        );
    }

    public function test_electric_bill_and_message_badges_show_and_clear_when_read(): void
    {
        $bill = ElectricBill::create([
            'tenant_id' => $this->tenant->id,
            'manager_id' => $this->manager->id,
            'stall_id' => $this->stall->id,
            'period_from' => today()->startOfMonth(),
            'period_to' => today()->endOfMonth(),
            'billing_date' => today(),
            'reading_previous' => 100,
            'reading_present' => 125,
            'kwh_used' => 25,
            'rate_per_kwh' => 12,
            'total_amount' => 300,
            'due_date' => today()->addDays(10),
            'status' => PaymentStatus::Pending,
        ]);
        $this->tenant->notifyNow(new BillIssuedNotification($bill));

        Message::create([
            'sender_id' => $this->manager->id,
            'receiver_id' => $this->tenant->id,
            'message' => 'Your electric bill is ready.',
        ]);

        $this->actingAs($this->tenant)
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('data-nav-badge-key="electric-bills"', false)
            ->assertSee('data-nav-badge-key="messages"', false);

        $this->get(route('api.navigation-badges'))
            ->assertOk()
            ->assertJsonPath('electric-bills', 1)
            ->assertJsonPath('messages', 1);

        $this->get(route('tenant.electric-bills'))->assertOk();

        $this->get(route('api.navigation-badges'))
            ->assertJsonPath('electric-bills', 0)
            ->assertJsonPath('messages', 1);
    }
}
