<?php

namespace Tests\Feature\Inventory;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Inventory\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->tenant = User::factory()->create(['role' => 'tenant']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->product = Product::create(['name' => 'Cola', 'price' => 20, 'stock' => 10, 'is_active' => true]);
    }

    private function order(int $qty = 2): Order
    {
        $this->actingAs($this->tenant)->post('/tenant/orders', ['product_id' => $this->product->id, 'quantity' => $qty]);

        return Order::latest('id')->firstOrFail();
    }

    public function test_placing_an_order_reserves_stock_and_records_a_ledger_entry(): void
    {
        $order = $this->order(3);

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(7, $this->product->fresh()->stock);

        $movement = StockMovement::where('reference_type', Order::class)->where('reference_id', $order->id)->firstOrFail();
        $this->assertSame(StockMovementType::Sale, $movement->type);
        $this->assertSame(-3, $movement->quantity);
        $this->assertSame(7, $movement->balance_after);
    }

    public function test_stock_can_never_go_negative(): void
    {
        $service = app(OrderService::class);
        $service->place($this->tenant, $this->product, 10);

        $this->expectException(RuntimeException::class);
        $service->place($this->tenant, $this->product->fresh(), 1);
    }

    public function test_failed_order_leaves_no_trace(): void
    {
        $this->actingAs($this->tenant)->post('/tenant/orders', ['product_id' => $this->product->id, 'quantity' => 11])
            ->assertSessionHas('error', 'Not enough stock available.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_tenant_can_cancel_an_unpaid_order_and_stock_returns(): void
    {
        $order = $this->order(4);

        $this->actingAs($this->tenant)->patch("/tenant/orders/{$order->id}/cancel", ['reason' => 'Ordered by mistake'])->assertSessionHas('success');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_tenant_cannot_cancel_someone_elses_order(): void
    {
        $order = $this->order();

        $this->actingAs(User::factory()->create(['role' => 'tenant']))->patch("/tenant/orders/{$order->id}/cancel")->assertForbidden();
    }

    public function test_admin_verifies_payment_then_delivers(): void
    {
        $order = $this->order();
        $this->actingAs($this->tenant)->patch("/billing/order/{$order->id}/submit", ['reference_number' => '1234567890123']);

        $this->actingAs($this->admin)->patch("/billing/order/{$order->id}/verify")->assertSessionHas('success');
        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->receipt_number);
        $this->assertFalse($order->status->isCancellable());

        $this->actingAs($this->admin)->patch("/admin/orders/{$order->id}/deliver")->assertSessionHas('success');
        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_managers_cannot_verify_supply_orders(): void
    {
        $order = $this->order();
        $this->actingAs($this->tenant)->patch("/billing/order/{$order->id}/submit", ['reference_number' => '1234567890123']);

        $this->actingAs(User::factory()->create(['role' => 'manager']))->patch("/billing/order/{$order->id}/verify")->assertForbidden();
    }

    public function test_stale_unpaid_orders_are_cancelled_and_release_stock(): void
    {
        config(['billing.order_auto_cancel_days' => 3]);
        $stale = $this->order(5);
        $stale->forceFill(['created_at' => now()->subDays(4)])->save();
        $fresh = $this->order(1);

        $this->artisan('leases:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $stale->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $fresh->fresh()->status);
        $this->assertSame(9, $this->product->fresh()->stock);
    }

    public function test_only_paid_and_delivered_orders_count_as_revenue(): void
    {
        $this->order(1); // pending
        $paid = $this->order(2);
        $this->actingAs($this->admin)->patch("/billing/order/{$paid->id}/record-cash");

        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()->assertViewHas('totalRevenue', 40);
    }
}
