<?php

namespace Tests\Feature\Admin;

use App\Enums\ContractStatus;
use App\Enums\StallStatus;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserDeactivationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->manager = User::factory()->create(['role' => 'manager']);
    }

    public function test_user_with_billing_history_is_deactivated_not_deleted(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'A', 'price' => 1000, 'status' => 'occupied']);
        $contract = Contract::create(['tenant_id' => $tenant->id, 'stall_id' => $stall->id, 'start_date' => today(), 'end_date' => today()->addYear(), 'status' => 'active']);
        $payment = Payment::create(['tenant_id' => $tenant->id, 'manager_id' => $manager->id, 'stall_id' => $stall->id, 'amount' => 1000, 'due_date' => today()->subMonth(), 'status' => 'paid']);

        $this->actingAs($this->manager)->delete("/manager/users/{$tenant->id}")
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'deactivated instead of deleted'));

        $this->assertModelExists($tenant);
        $this->assertModelExists($payment);
        $this->assertFalse($tenant->fresh()->is_active);
        $this->assertSame(ContractStatus::Terminated, $contract->fresh()->status);
        $this->assertSame(StallStatus::Available, $stall->fresh()->status);
    }

    public function test_deactivated_user_is_signed_out_on_next_request(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);

        $this->actingAs($tenant)->get('/tenant/dashboard')->assertOk();

        $tenant->update(['is_active' => false]);

        $this->get('/tenant/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_manager_can_reactivate_a_user(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => false]);

        $this->actingAs($this->manager)->patch("/manager/users/{$tenant->id}/active")->assertSessionHas('success');

        $this->assertTrue($tenant->fresh()->is_active);
    }

    public function test_manager_cannot_deactivate_themselves(): void
    {
        $this->actingAs($this->manager)->patch("/manager/users/{$this->manager->id}/active")->assertSessionHas('error');

        $this->assertTrue($this->manager->fresh()->is_active);
    }
}
