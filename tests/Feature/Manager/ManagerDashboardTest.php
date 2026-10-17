<?php

namespace Tests\Feature\Manager;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ml.python' => 'python-binary-that-does-not-exist']);
        $this->manager = User::factory()->create(['role' => 'manager']);
    }

    private function dashboard(string $query = '')
    {
        return $this->actingAs($this->manager)->get('/manager/dashboard'.$query)->assertOk();
    }

    public function test_stall_availability_is_paginated(): void
    {
        $response = $this->dashboard(); // first visit generates 34 stalls

        $page = $response->viewData('stallPage');
        $this->assertSame(8, $page->count());
        $this->assertSame(34, $page->total());
        $this->assertSame(34, $response->viewData('totalStalls')); // stats still cover every stall
        $response->assertSee('Showing 1–8 of 34');
        $this->assertStringContainsString('stalls_page=2', $page->nextPageUrl());
        $this->assertStringContainsString('#stall-availability', $page->nextPageUrl());
    }

    public function test_stall_availability_can_be_searched_by_name(): void
    {
        $this->dashboard();

        $this->assertSame(['Stall 12'], $this->dashboard('?stall_q=stall+12')->viewData('stallPage')->pluck('name')->all());
        $this->assertSame(['Stall 7', 'Cart 7'], $this->dashboard('?stall_q=7')->viewData('stallPage')->pluck('name')->all());
        $this->dashboard('?stall_q=zzz')->assertSee('No stalls match your search.');
    }

    public function test_stall_availability_can_be_filtered_by_status(): void
    {
        $this->dashboard();
        Stall::whereIn('name', ['Stall 1', 'Cart 2'])->update(['status' => 'maintenance']);

        $this->assertSame(['Stall 1', 'Cart 2'], $this->dashboard('?stall_status=maintenance')->viewData('stallPage')->pluck('name')->all());
    }

    public function test_other_sections_page_independently(): void
    {
        $this->dashboard();
        $tenant = User::factory()->create(['role' => 'tenant']);
        $stall = Stall::first();
        foreach (range(1, 7) as $i) {
            Payment::create(['tenant_id' => $tenant->id, 'manager_id' => $this->manager->id, 'stall_id' => $stall->id, 'amount' => 100 * $i, 'due_date' => today()->subMonths($i), 'status' => 'paid', 'paid_date' => now()]);
            ActivityLog::create(['user_id' => $this->manager->id, 'action' => 'test', 'description' => "Event {$i}"]);
        }

        $response = $this->dashboard('?payments_page=2&activity_page=2');

        $this->assertSame(2, $response->viewData('recentPayments')->count());   // 7 payments, 5 per page
        $this->assertSame(1, $response->viewData('stallPage')->currentPage());   // stall panel untouched
        $this->assertSame(5, $response->viewData('riskTenants')->perPage());
        $this->assertSame(2, $response->viewData('activityFeed')->currentPage());
    }

    public function test_invalid_status_filter_is_rejected(): void
    {
        $this->actingAs($this->manager)->get('/manager/dashboard?stall_status=demolished')->assertSessionHasErrors('stall_status');
    }
}
