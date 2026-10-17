<?php

namespace Tests\Feature\Manager;

use App\Models\MaintenanceRequest;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Stall A',
            'price' => 5000,
            'status' => 'occupied',
        ]);
    }

    public function test_manager_can_view_maintenance_inbox(): void
    {
        $response = $this->actingAs($this->manager)->get('/manager/inbox');

        $response->assertStatus(200);
    }

    public function test_manager_can_update_maintenance_status(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $request = MaintenanceRequest::create([
            'tenant_id' => $tenant->id,
            'stall_id' => $this->stall->id,
            'subject' => 'Leaky faucet',
            'description' => 'Faucet is leaking badly',
            'priority' => 'high',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->patch("/manager/maintenance/{$request->id}/status", [
                'status' => 'in_progress',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('maintenance_requests', [
            'id' => $request->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_manager_cannot_set_invalid_maintenance_status(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $request = MaintenanceRequest::create([
            'tenant_id' => $tenant->id,
            'stall_id' => $this->stall->id,
            'subject' => 'Broken light',
            'description' => 'Light is broken',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->patch("/manager/maintenance/{$request->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertSessionHasErrors('status');
    }
}
