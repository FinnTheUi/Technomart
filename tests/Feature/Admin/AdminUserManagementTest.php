<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    }

    public function test_manager_can_view_users_list(): void
    {
        User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        User::factory()->create(['role' => 'manager', 'is_active' => true]);

        $response = $this->actingAs($this->manager)->get('/manager/users');

        $response->assertStatus(200);
    }

    public function test_users_list_is_paginated(): void
    {
        foreach (range(1, 12) as $number) {
            User::factory()->create([
                'name' => sprintf('Tenant %02d', $number),
                'role' => User::ROLE_TENANT,
            ]);
        }

        $this->actingAs($this->manager)->get('/manager/users')
            ->assertOk()
            ->assertSee('Showing 1')
            ->assertDontSee('Tenant 10');

        $this->actingAs($this->manager)->get('/manager/users?page=2')
            ->assertOk()
            ->assertSee('Tenant 10')
            ->assertDontSee('Tenant 01');
    }

    public function test_manager_can_update_user_role(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($this->manager)->patch("/manager/users/{$tenant->id}/role", [
            'role' => 'manager',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $tenant->id,
            'role' => 'manager',
        ]);
    }

    public function test_manager_can_create_a_manager(): void
    {
        $response = $this->actingAs($this->manager)->post('/manager/users/manager', [
            'name' => 'New Manager',
            'email' => 'manager@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('manager.users'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'manager@gmail.com',
            'role' => 'manager',
        ]);
    }

    public function test_manager_cannot_create_manager_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@gmail.com']);

        $response = $this->actingAs($this->manager)->post('/manager/users/manager', [
            'name' => 'Duplicate',
            'email' => 'existing@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_manager_can_delete_a_user(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($this->manager)->delete("/manager/users/{$tenant->id}");

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $tenant->id]);
    }

    public function test_manager_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->manager)->delete("/manager/users/{$this->manager->id}");

        $response->assertSessionHas('error', 'You cannot delete yourself.');
        $this->assertDatabaseHas('users', ['id' => $this->manager->id]);
    }

    public function test_manager_cannot_assign_invalid_role(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($this->manager)->patch("/manager/users/{$tenant->id}/role", [
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('users', [
            'id' => $tenant->id,
            'role' => 'tenant',
        ]);
    }

    public function test_manager_can_resend_verification_to_an_active_unverified_user(): void
    {
        Notification::fake();
        $tenant = User::factory()->unverified()->create(['role' => 'tenant', 'is_active' => true]);

        $this->actingAs($this->manager)
            ->post("/manager/users/{$tenant->id}/resend-verification")
            ->assertSessionHas('success');

        Notification::assertSentTo($tenant, VerifyEmail::class);
    }

    public function test_manager_can_invalidate_another_users_sessions(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        DB::table('sessions')->insert([
            'id' => 'tenant-session', 'user_id' => $tenant->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'Test', 'payload' => 'data', 'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->manager)
            ->delete("/manager/users/{$tenant->id}/sessions")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'tenant-session']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'invalidated_sessions', 'subject_id' => $tenant->id]);
    }

    public function test_manager_cannot_invalidate_their_own_current_session(): void
    {
        $this->actingAs($this->manager)
            ->delete("/manager/users/{$this->manager->id}/sessions")
            ->assertSessionHas('error');
    }
}
