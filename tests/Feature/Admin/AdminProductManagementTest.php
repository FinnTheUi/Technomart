<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_view_products_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/products');

        $response->assertStatus(200);
    }

    public function test_stock_inventory_is_paginated_and_uses_the_generic_title(): void
    {
        foreach (range(1, 12) as $number) {
            Product::create([
                'name' => sprintf('Inventory Item %02d', $number),
                'price' => 10,
                'stock' => 5,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/admin/products');

        $response->assertOk()
            ->assertSee('STOCK INVENTORY')
            ->assertDontSee('PEPSI-COLA PRODUCTS')
            ->assertSee('Inventory Item 10')
            ->assertDontSee('Inventory Item 11')
            ->assertSee('Showing 1');
    }

    public function test_admin_can_create_a_product(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => 150.00,
            'stock' => 50,
            'category' => 'Supplies',
        ]);

        $response->assertRedirect(route('admin.products'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'price' => 150.00]);
    }

    public function test_admin_cannot_create_product_with_missing_name(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'price' => 100.00,
            'stock' => 10,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_cannot_create_product_with_negative_price(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Bad Product',
            'price' => -50.00,
            'stock' => 10,
        ]);

        $response->assertSessionHasErrors('price');
    }

    public function test_admin_can_update_a_product(): void
    {
        $product = Product::create([
            'name' => 'Old Name',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
            'name' => 'Updated Name',
            'price' => 200,
            'stock' => 25,
        ]);

        $response->assertRedirect(route('admin.products'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'price' => 200,
        ]);
    }

    public function test_admin_can_delete_a_product(): void
    {
        $product = Product::create([
            'name' => 'To Delete',
            'price' => 100,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");

        $response->assertRedirect(route('admin.products'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
