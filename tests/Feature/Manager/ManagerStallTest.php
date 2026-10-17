<?php

namespace Tests\Feature\Manager;

use App\Models\Stall;
use App\Models\User;
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

    public function test_manager_stalls_are_paginated(): void
    {
        $firstPage = $this->actingAs($this->manager)->get('/manager/stalls');
        $firstPage->assertOk()
            ->assertSee('Showing 1')
            ->assertSee('>Stall 10<', false)
            ->assertDontSee('>Stall 11<', false);

        $this->actingAs($this->manager)->get('/manager/stalls?page=2')
            ->assertOk()
            ->assertSee('>Stall 11<', false)
            ->assertDontSee('>Stall 10<', false);
    }

    public function test_manager_can_search_stalls_and_clear_the_search(): void
    {
        $this->actingAs($this->manager)->get('/manager/stalls?q=Stall+23')
            ->assertOk()
            ->assertSee('value="Stall 23"', false)
            ->assertSee('>Stall 23<', false)
            ->assertDontSee('>Stall 22<', false)
            ->assertSee('Clear');

        $this->actingAs($this->manager)->get('/manager/stalls?q=missing')
            ->assertOk()
            ->assertSee('No stalls match your search.');
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
