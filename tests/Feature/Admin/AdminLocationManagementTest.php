<?php

namespace Tests\Feature\Admin;

use App\Models\Location;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_create_a_location(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/locations', [
            'name' => 'Market Branch A',
            'address' => '123 Main Street',
            'description' => 'Main market branch',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('locations', ['name' => 'Market Branch A']);
    }

    public function test_admin_can_delete_a_location(): void
    {
        $location = Location::create([
            'name' => 'To Delete',
            'address' => '456 Street',
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/locations/{$location->id}");

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    public function test_admin_cannot_delete_location_with_assigned_stalls(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $location = Location::create([
            'name' => 'Busy Location',
            'address' => '789 Ave',
        ]);
        Stall::create([
            'manager_id' => $manager->id,
            'name' => 'Stall A',
            'price' => 5000,
            'status' => 'available',
            'location_id' => $location->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/locations/{$location->id}");

        $response->assertSessionHas('error', 'Cannot delete a location that has stalls assigned to it.');
        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_admin_cannot_create_location_with_missing_name(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/locations', [
            'address' => 'Some Address',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
