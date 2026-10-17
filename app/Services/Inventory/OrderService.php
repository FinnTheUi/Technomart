<?php

namespace App\Services\Inventory;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tenant supply orders: placed (stock reserved) → paid → delivered, or cancelled (stock returned).
 */
class OrderService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @throws RuntimeException when the product is unavailable or out of stock
     */
    public function place(User $tenant, Product $product, int $quantity): Order
    {
        $order = DB::transaction(function () use ($tenant, $product, $quantity) {
            if (! $product->is_active) {
                throw new RuntimeException('This product is no longer available.');
            }

            $order = Order::create([
                'user_id' => $tenant->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'total_price' => round((float) $product->price * $quantity, 2),
                'status' => OrderStatus::Pending,
            ]);

            // Locks the product row; throws (rolling back the order) if stock is insufficient.
            $this->inventory->move($product, -$quantity, StockMovementType::Sale, $order, $tenant, 'Order #'.$order->id);

            return $order;
        });

        ActivityLogger::log('placed_order', $tenant->name.' ordered '.$quantity.'x "'.$product->name.'"', $order, null, $tenant);

        return $order;
    }

    public function cancel(Order $order, ?User $actor = null, string $reason = 'Cancelled'): void
    {
        DB::transaction(function () use ($order, $actor, $reason) {
            $locked = Order::with('product')->lockForUpdate()->findOrFail($order->id);

            if (! $locked->status->isCancellable()) {
                throw new RuntimeException('This order can no longer be cancelled.');
            }

            $locked->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
            $this->inventory->move($locked->product, $locked->quantity, StockMovementType::Cancellation, $locked, $actor, $reason);

            $order->setRawAttributes($locked->getAttributes(), true);
        });

        ActivityLogger::log('cancelled_order', ($actor?->name ?? 'System').' cancelled order #'.$order->id.': '.$reason, $order, null, $actor);
    }

    public function markDelivered(Order $order, User $admin): void
    {
        DB::transaction(function () use ($order) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== OrderStatus::Paid) {
                throw new RuntimeException('Only paid orders can be marked as delivered.');
            }

            $locked->update(['status' => OrderStatus::Delivered, 'delivered_at' => now()]);
            $order->setRawAttributes($locked->getAttributes(), true);
        });
        ActivityLogger::log('delivered_order', $admin->name.' delivered order #'.$order->id, $order, null, $admin);
    }

    /**
     * Cancel unpaid orders older than the configured window so reserved stock is released.
     */
    public function cancelStaleUnpaidOrders(): int
    {
        $cutoff = now()->subDays(config('billing.order_auto_cancel_days', 3));

        $stale = Order::where('status', OrderStatus::Pending)->where('created_at', '<', $cutoff)->get();

        foreach ($stale as $order) {
            $this->cancel($order, null, 'Automatically cancelled: unpaid for '.config('billing.order_auto_cancel_days').' days');
        }

        return $stale->count();
    }
}
