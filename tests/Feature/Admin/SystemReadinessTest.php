<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_system_health_and_reports(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/system-health')->assertOk()->assertSee('System Health');
        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Operational Reports');
    }

    public function test_admin_can_download_operational_csv_reports(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['rent', 'orders', 'inventory'] as $report) {
            $this->actingAs($admin)->get("/admin/reports/{$report}.csv")
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_non_admin_cannot_access_system_readiness_pages(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);

        $this->actingAs($tenant)->get('/admin/system-health')->assertForbidden();
        $this->actingAs($tenant)->get('/admin/reports')->assertForbidden();
    }
}
