<?php

namespace Tests\Feature\Manager;

use App\Models\Contract;
use App\Models\ElectricBill;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ManagerRecordPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $tenant;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $this->stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Pagination Stall',
            'price' => 5000,
            'status' => 'occupied',
        ]);
    }

    public function test_manager_leases_are_paginated_ten_per_page(): void
    {
        foreach (range(1, 11) as $month) {
            Contract::create([
                'tenant_id' => $this->tenant->id,
                'stall_id' => $this->stall->id,
                'start_date' => today(),
                'end_date' => today()->addMonths($month),
                'status' => 'active',
            ]);
        }

        $this->assertPaginator('/manager/contracts', 'contracts');
    }

    public function test_manager_payments_are_paginated_ten_per_page(): void
    {
        foreach (range(1, 11) as $day) {
            Payment::create([
                'tenant_id' => $this->tenant->id,
                'manager_id' => $this->manager->id,
                'stall_id' => $this->stall->id,
                'amount' => 1000 + $day,
                'due_date' => today()->addDays($day),
                'status' => 'pending',
            ]);
        }

        $this->assertPaginator('/manager/payments', 'payments');
    }

    public function test_manager_electric_bills_are_paginated_ten_per_page(): void
    {
        foreach (range(1, 11) as $month) {
            ElectricBill::create([
                'tenant_id' => $this->tenant->id,
                'manager_id' => $this->manager->id,
                'stall_id' => $this->stall->id,
                'period_from' => today()->subMonths($month),
                'period_to' => today()->subMonths($month)->endOfMonth(),
                'billing_date' => today()->subMonths($month),
                'reading_previous' => 100,
                'reading_present' => 110,
                'kwh_used' => 10,
                'rate_per_kwh' => 11.36,
                'total_amount' => 113.60,
                'due_date' => today()->addDays($month),
                'status' => 'pending',
            ]);
        }

        $this->assertPaginator('/manager/electric-bills', 'bills');
    }

    private function assertPaginator(string $url, string $viewVariable): void
    {
        $this->actingAs($this->manager)->get($url)
            ->assertOk()
            ->assertViewHas($viewVariable, fn (LengthAwarePaginator $records) => $records->count() === 10
                && $records->total() === 11
                && $records->currentPage() === 1)
            ->assertSee('Showing 1');

        $this->actingAs($this->manager)->get($url.'?page=2')
            ->assertOk()
            ->assertViewHas($viewVariable, fn (LengthAwarePaginator $records) => $records->count() === 1
                && $records->total() === 11
                && $records->currentPage() === 2);
    }
}
