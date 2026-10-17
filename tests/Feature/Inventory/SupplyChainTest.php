<?php

namespace Tests\Feature\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Services\Inventory\InventoryReport;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SupplyChainTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supplier = Supplier::create(['name' => 'Pepsi Distributor']);
        $this->product = Product::create(['name' => 'Cola', 'price' => 20, 'stock' => 4, 'reorder_level' => 10, 'is_active' => true, 'supplier_id' => $this->supplier->id]);
    }

    private function createPurchaseOrder(): PurchaseOrder
    {
        $this->actingAs($this->admin)->post('/admin/purchase-orders', [
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 24, 'unit_cost' => 12.5],
                ['product_id' => '', 'quantity' => '', 'unit_cost' => ''], // blank row from the form is ignored
            ],
        ])->assertRedirect();

        return PurchaseOrder::firstOrFail();
    }

    public function test_purchase_order_is_numbered_and_totalled(): void
    {
        $po = $this->createPurchaseOrder();

        $this->assertMatchesRegularExpression('/^PO-\d{4}-0001$/', $po->po_number);
        $this->assertSame(PurchaseOrderStatus::Draft, $po->status);
        $this->assertEquals(300.00, $po->total_cost);
        $this->assertCount(1, $po->items);
    }

    public function test_receiving_goods_adds_stock_once(): void
    {
        $po = $this->createPurchaseOrder();

        $this->actingAs($this->admin)->patch("/admin/purchase-orders/{$po->id}/ordered")->assertSessionHas('success');
        $this->actingAs($this->admin)->patch("/admin/purchase-orders/{$po->id}/receive")->assertSessionHas('success');

        $this->assertSame(28, $this->product->fresh()->stock);
        $this->assertSame(PurchaseOrderStatus::Received, $po->fresh()->status);
        $this->assertTrue(StockMovement::where('type', StockMovementType::Purchase)->where('quantity', 24)->exists());

        $this->actingAs($this->admin)->patch("/admin/purchase-orders/{$po->id}/receive")->assertSessionHas('error');
        $this->assertSame(28, $this->product->fresh()->stock);
    }

    public function test_cancelled_purchase_order_cannot_be_received(): void
    {
        $po = $this->createPurchaseOrder();

        $this->actingAs($this->admin)->patch("/admin/purchase-orders/{$po->id}/cancel");
        $this->actingAs($this->admin)->patch("/admin/purchase-orders/{$po->id}/receive")->assertSessionHas('error');

        $this->assertSame(4, $this->product->fresh()->stock);
    }

    public function test_physical_count_records_an_adjustment(): void
    {
        $this->actingAs($this->admin)->post('/admin/inventory/adjust', [
            'product_id' => $this->product->id, 'counted_stock' => 2, 'note' => '2 damaged',
        ])->assertSessionHas('success');

        $this->assertSame(2, $this->product->fresh()->stock);
        $movement = StockMovement::where('type', StockMovementType::Adjustment)->firstOrFail();
        $this->assertSame(-2, $movement->quantity);
        $this->assertSame('2 damaged', $movement->note);
    }

    public function test_editing_stock_on_the_product_form_is_recorded_in_the_ledger(): void
    {
        $this->actingAs($this->admin)->put("/admin/products/{$this->product->id}", [
            'name' => 'Cola', 'price' => 20, 'stock' => 15, 'reorder_level' => 10,
        ])->assertRedirect(route('admin.products'));

        $this->assertSame(15, $this->product->fresh()->stock);
        $this->assertTrue(StockMovement::where('type', StockMovementType::Adjustment)->where('quantity', 11)->exists());
    }

    public function test_low_stock_digest_is_sent_to_admins(): void
    {
        Product::create(['name' => 'Plenty', 'price' => 5, 'stock' => 100, 'reorder_level' => 10, 'is_active' => true]);

        $this->artisan('inventory:check-low-stock')->assertSuccessful();

        Notification::assertSentTo($this->admin, LowStockNotification::class,
            fn ($n) => $n->products->pluck('name')->all() === ['Cola']);
    }

    public function test_supplier_with_purchase_orders_cannot_be_deleted(): void
    {
        $this->createPurchaseOrder();

        $this->actingAs($this->admin)->delete("/admin/suppliers/{$this->supplier->id}")->assertSessionHas('error');
        $this->assertModelExists($this->supplier);
    }

    public function test_inventory_report_is_built_from_the_ledger(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $this->product->update(['stock' => 0]);
        app(InventoryService::class)->move($this->product, 20, StockMovementType::Purchase);
        $this->actingAs($tenant)->post('/tenant/orders', ['product_id' => $this->product->id, 'quantity' => 3]);

        $today = collect(app(InventoryReport::class)->daily(3))->last()['records'][0];

        $this->assertSame(3, $today['qty_sold']);
        $this->assertSame(17, $today['available_stock']);
        $this->assertEquals(60.0, $today['sales_value']);
    }

    public function test_admin_pages_render(): void
    {
        $po = $this->createPurchaseOrder();

        foreach (['/admin/products', '/admin/inventory', '/admin/suppliers', '/admin/purchase-orders', "/admin/purchase-orders/{$po->id}", '/admin/orders', '/admin/dashboard'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }
}
