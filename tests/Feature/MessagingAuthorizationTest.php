<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_contacts_exclude_dashboard_only_admins_and_inactive_users(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $admin = User::factory()->create(['role' => 'admin']);
        $inactive = User::factory()->create(['role' => 'tenant', 'is_active' => false]);
        $tenant = User::factory()->create(['role' => 'tenant']);

        $this->actingAs($manager)->get('/messages')->assertOk()
            ->assertViewHas('contactableUsers', fn ($users) => $users->modelKeys() === [$tenant->id]);
        $this->post('/messages', ['receiver_id' => $admin->id, 'message' => 'Blocked'])->assertForbidden();
        $this->post('/messages', ['receiver_id' => $inactive->id, 'message' => 'Blocked'])->assertForbidden();
    }

    public function test_tenant_cannot_message_or_open_another_tenant_conversation(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $otherTenant = User::factory()->create(['role' => 'tenant']);

        $this->actingAs($tenant)->post('/messages', [
            'receiver_id' => $otherTenant->id,
            'message' => 'This should be blocked.',
        ])->assertForbidden();

        $this->actingAs($tenant)
            ->get("/messages/{$otherTenant->id}")
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get("/api/messages/{$otherTenant->id}")
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_tenant_can_message_a_manager(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($tenant)->post('/messages', [
            'receiver_id' => $manager->id,
            'message' => 'Please help.',
        ])->assertRedirect(route('messages.conversation', $manager));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $tenant->id,
            'receiver_id' => $manager->id,
            'message' => 'Please help.',
        ]);
    }
}
