<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_system_health_and_reports(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get('/manager/system-health')->assertOk()->assertSee('System Health');
        $this->actingAs($manager)->get('/manager/reports')->assertOk()->assertSee('Operational Reports');
    }

    public function test_manager_can_download_operational_csv_reports(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        foreach (['rent', 'orders', 'inventory'] as $report) {
            $this->actingAs($manager)->get("/manager/reports/{$report}.csv")
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_non_manager_cannot_access_system_readiness_pages(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);

        $this->actingAs($tenant)->get('/manager/system-health')->assertForbidden();
        $this->actingAs($tenant)->get('/manager/reports')->assertForbidden();
    }
}
