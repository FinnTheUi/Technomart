<?php

namespace Tests\Feature\Tenant;

use App\Models\ElectricBill;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TenantListPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;

    private User $manager;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $this->stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Tenant Pagination Stall',
            'price' => 5000,
            'status' => 'occupied',
        ]);
    }

    public function test_available_products_are_paginated_ten_per_page(): void
    {
        foreach (range(1, 11) as $number) {
            Product::create([
                'name' => 'Product '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'price' => 100,
                'stock' => 10,
                'is_active' => true,
            ]);
        }

        $this->assertPaginator('/tenant/products', 'products');
    }

    public function test_tenant_orders_are_paginated_ten_per_page(): void
    {
        $product = Product::create(['name' => 'Order Item', 'price' => 100, 'stock' => 20, 'is_active' => true]);

        foreach (range(1, 11) as $number) {
            Order::create([
                'user_id' => $this->tenant->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'total_price' => 100 + $number,
                'status' => 'pending',
            ]);
        }

        $this->assertPaginator('/tenant/orders', 'orders');
    }

    public function test_tenant_rent_bills_are_paginated_ten_per_page(): void
    {
        foreach (range(1, 11) as $month) {
            Payment::create([
                'tenant_id' => $this->tenant->id,
                'manager_id' => $this->manager->id,
                'stall_id' => $this->stall->id,
                'amount' => 5000,
                'due_date' => today()->addMonths($month),
                'status' => 'pending',
            ]);
        }

        $this->assertPaginator('/tenant/bills', 'bills');
    }

    public function test_tenant_electric_bills_are_paginated_ten_per_page(): void
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

        $this->assertPaginator('/tenant/electric-bills', 'bills');
    }

    private function assertPaginator(string $url, string $viewVariable): void
    {
        $this->actingAs($this->tenant)->get($url)
            ->assertOk()
            ->assertViewHas($viewVariable, fn (LengthAwarePaginator $records) => $records->count() === 10
                && $records->total() === 11
                && $records->currentPage() === 1)
            ->assertSee('Showing 1');

        $this->actingAs($this->tenant)->get($url.'?page=2')
            ->assertOk()
            ->assertViewHas($viewVariable, fn (LengthAwarePaginator $records) => $records->count() === 1
                && $records->total() === 11
                && $records->currentPage() === 2);
    }
}
