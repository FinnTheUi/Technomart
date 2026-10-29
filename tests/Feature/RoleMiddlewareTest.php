<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_reads_do_not_consume_the_write_rate_limit(): void
    {
        $limiter = \Illuminate\Support\Facades\RateLimiter::limiter('writes');
        $read = $limiter(\Illuminate\Http\Request::create('/manager/products', 'GET'));
        $write = $limiter(\Illuminate\Http\Request::create('/manager/products', 'POST'));
        $this->assertSame(300, $read->maxAttempts);
        $this->assertSame(60, $write->maxAttempts);
        $this->assertNotSame($read->key, $write->key);
    }

    public function test_dashboard_only_admin_cannot_access_manager_operations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['products', 'users', 'inventory', 'orders', 'reports', 'suppliers', 'purchase-orders', 'locations'] as $page) {
            $this->actingAs($admin)->get('/manager/'.$page)->assertForbidden();
        }
    }

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
