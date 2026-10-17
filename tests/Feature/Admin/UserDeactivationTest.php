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

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_user_with_billing_history_is_deactivated_not_deleted(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'A', 'price' => 1000, 'status' => 'occupied']);
        $contract = Contract::create(['tenant_id' => $tenant->id, 'stall_id' => $stall->id, 'start_date' => today(), 'end_date' => today()->addYear(), 'status' => 'active']);
        $payment = Payment::create(['tenant_id' => $tenant->id, 'manager_id' => $manager->id, 'stall_id' => $stall->id, 'amount' => 1000, 'due_date' => today()->subMonth(), 'status' => 'paid']);

        $this->actingAs($this->admin)->delete("/admin/users/{$tenant->id}")
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

    public function test_admin_can_reactivate_a_user(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => false]);

        $this->actingAs($this->admin)->patch("/admin/users/{$tenant->id}/active")->assertSessionHas('success');

        $this->assertTrue($tenant->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $this->actingAs($this->admin)->patch("/admin/users/{$this->admin->id}/active")->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->is_active);
    }
}
