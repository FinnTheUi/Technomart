<?php

namespace App\Services\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Billing\DocumentNumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The only way stock changes. Every change is written to the stock ledger (stock_movements)
 * inside a row lock, so concurrent orders can never oversell.
 */
class InventoryService
{
    public function __construct(private readonly DocumentNumberGenerator $numbers) {}

    /**
     * Apply a signed quantity change to a product and record it. Must be called inside a transaction
     * when combined with other writes; it opens its own otherwise.
     *
     * @throws RuntimeException when the change would make stock negative
     */
    public function move(
        Product $product,
        int $quantity,
        StockMovementType $type,
        ?Model $reference = null,
        ?User $user = null,
        ?string $note = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $type, $reference, $user, $note) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);
            $balance = $locked->stock + $quantity;

            if ($balance < 0) {
                throw new RuntimeException('Not enough stock available.');
            }

            $locked->forceFill(['stock' => $balance])->save();
            $product->setAttribute('stock', $balance);

            return StockMovement::create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $balance,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
                'user_id' => $user?->id ?? auth()->id(),
                'note' => $note,
            ]);
        });
    }

    /**
     * Set a product's stock to a counted value (manual correction).
     */
    public function adjustTo(Product $product, int $newStock, User $user, ?string $note = null): ?StockMovement
    {
        $delta = $newStock - $product->fresh()->stock;

        if ($delta === 0) {
            return null;
        }

        $movement = $this->move($product, $delta, StockMovementType::Adjustment, null, $user, $note ?? 'Manual stock adjustment');

        ActivityLogger::log('adjusted_stock', $user->name.' adjusted stock of "'.$product->name.'" by '.sprintf('%+d', $delta).' to '.$newStock, $product, ['delta' => $delta], $user);

        return $movement;
    }

    // ── Purchase orders ──

    /**
     * @param  array<int, array{product_id:int, quantity:int, unit_cost:float}>  $items
     */
    public function createPurchaseOrder(int $supplierId, array $items, ?string $notes, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($supplierId, $items, $notes, $user) {
            $po = PurchaseOrder::create([
                'po_number' => $this->numbers->next('PO', 4),
                'supplier_id' => $supplierId,
                'status' => PurchaseOrderStatus::Draft,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            foreach ($items as $item) {
                $po->items()->create($item);
            }

            $po->update(['total_cost' => $po->items->sum(fn ($i) => $i->lineTotal())]);

            ActivityLogger::log('created_purchase_order', $user->name.' created purchase order '.$po->po_number, $po, null, $user);

            return $po;
        });
    }

    public function markOrdered(PurchaseOrder $po, User $user): void
    {
        if ($po->status !== PurchaseOrderStatus::Draft) {
            throw new RuntimeException('Only draft purchase orders can be sent to the supplier.');
        }

        $po->update(['status' => PurchaseOrderStatus::Ordered, 'ordered_at' => now()]);
        ActivityLogger::log('ordered_purchase_order', $user->name.' sent purchase order '.$po->po_number.' to the supplier', $po, null, $user);
    }

    /**
     * Receive the goods: every line is added to stock.
     */
    public function receive(PurchaseOrder $po, User $user): void
    {
        DB::transaction(function () use ($po, $user) {
            $po = PurchaseOrder::with('items.product')->lockForUpdate()->findOrFail($po->id);

            if (! $po->isOpen()) {
                throw new RuntimeException('This purchase order has already been '.$po->status->value.'.');
            }

            foreach ($po->items as $item) {
                $this->move($item->product, $item->quantity, StockMovementType::Purchase, $po, $user, 'Received '.$po->po_number);
            }

            $po->update(['status' => PurchaseOrderStatus::Received, 'received_at' => now()]);
        });

        ActivityLogger::log('received_purchase_order', $user->name.' received purchase order '.$po->po_number, $po, null, $user);
    }

    public function cancelPurchaseOrder(PurchaseOrder $po, User $user): void
    {
        if (! $po->isOpen()) {
            throw new RuntimeException('This purchase order can no longer be cancelled.');
        }

        $po->update(['status' => PurchaseOrderStatus::Cancelled, 'cancelled_at' => now()]);
        ActivityLogger::log('cancelled_purchase_order', $user->name.' cancelled purchase order '.$po->po_number, $po, null, $user);
    }
}
