<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_tenant_cannot_access_admin_routes(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($tenant)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_manager_can_access_manager_dashboard(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);

        $response = $this->actingAs($manager)->get('/manager/dashboard');

        $response->assertStatus(200);
    }

    public function test_tenant_cannot_access_manager_routes(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($tenant)->get('/manager/dashboard');

        $response->assertStatus(403);
    }

    public function test_manager_cannot_access_admin_routes(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);

        $response = $this->actingAs($manager)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }
}
