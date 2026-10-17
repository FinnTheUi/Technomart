<?php

namespace Tests\Feature\Manager;

use App\Models\User;
use App\Models\Stall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerStallTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    }

    public function test_manager_can_adjust_a_stalls_price_and_details(): void
    {
        $stall = Stall::create([
            'manager_id' => $this->manager->id, 'name' => 'Stall 5', 'floor_number' => 1, 'stall_number' => 5,
            'price' => 5000, 'status' => 'available',
        ]);

        $response = $this->actingAs($this->manager)->put("/manager/stalls/{$stall->id}", [
            'price' => 6500,
            'size' => '3x3m',
            'location' => 'Near entrance',
            'name' => 'Ignored', // names follow the numbering
        ]);

        $response->assertRedirect(route('manager.stalls'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('stalls', [
            'id' => $stall->id,
            'name' => 'Stall 5',
            'price' => 6500,
            'size' => '3x3m',
            'location' => 'Near entrance',
        ]);
    }

    public function test_manager_cannot_set_a_negative_price(): void
    {
        $stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'Stall 6', 'floor_number' => 1, 'stall_number' => 6, 'price' => 5000, 'status' => 'available']);

        $this->actingAs($this->manager)->put("/manager/stalls/{$stall->id}", ['price' => -1000])->assertSessionHasErrors('price');
    }

    public function test_manager_cannot_edit_another_managers_stall(): void
    {
        $other = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $stall = Stall::create(['manager_id' => $other->id, 'name' => 'Stall 7', 'floor_number' => 1, 'stall_number' => 7, 'price' => 5000, 'status' => 'available']);

        $this->actingAs($this->manager)->get("/manager/stalls/{$stall->id}/edit")->assertForbidden();
        $this->actingAs($this->manager)->put("/manager/stalls/{$stall->id}", ['price' => 1])->assertForbidden();
    }

    public function test_manager_can_update_stall_status(): void
    {
        $stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Stall A',
            'price' => 5000,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->manager)->patch("/manager/stalls/{$stall->id}/status", [
            'status' => 'maintenance',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('stalls', [
            'id' => $stall->id,
            'status' => 'maintenance',
        ]);
    }

    public function test_manager_cannot_update_another_managers_stall(): void
    {
        $otherManager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $stall = Stall::create([
            'manager_id' => $otherManager->id,
            'name' => 'Other Stall',
            'price' => 3000,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->manager)->patch("/manager/stalls/{$stall->id}/status", [
            'status' => 'maintenance',
        ]);

        $response->assertStatus(403);
    }

    public function test_manager_cannot_set_invalid_stall_status(): void
    {
        $stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Stall B',
            'price' => 5000,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->manager)->patch("/manager/stalls/{$stall->id}/status", [
            'status' => 'demolished',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('stalls', [
            'id' => $stall->id,
            'status' => 'available',
        ]);
    }
}
