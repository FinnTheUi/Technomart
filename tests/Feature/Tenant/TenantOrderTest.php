<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
    }

    public function test_tenant_can_place_an_order(): void
    {
        $product = Product::create([
            'name' => 'Water Bottle',
            'price' => 25.00,
            'stock' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenant)->post('/tenant/orders', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->tenant->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'total_price' => 75.00,
            'status' => 'pending', // awaiting payment; stock is reserved
        ]);

        // Stock should be decremented
        $product->refresh();
        $this->assertEquals(97, $product->stock);
    }

    public function test_tenant_cannot_order_more_than_available_stock(): void
    {
        $product = Product::create([
            'name' => 'Limited Item',
            'price' => 50.00,
            'stock' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenant)->post('/tenant/orders', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response->assertSessionHas('error', 'Not enough stock available.');

        // Stock should remain unchanged
        $product->refresh();
        $this->assertEquals(2, $product->stock);

        // No order should have been created
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_tenant_cannot_order_with_missing_product_id(): void
    {
        $response = $this->actingAs($this->tenant)->post('/tenant/orders', [
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('product_id');
    }
}
