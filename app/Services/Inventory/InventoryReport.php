<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Daily stock & sales report built from the stock ledger (stock_movements).
 */
class InventoryReport
{
    /**
     * Report rows keyed by date (m/d/Y), oldest first.
     *
     * @return array<string, array<string, mixed>>
     */
    public function daily(int $days = 3): array
    {
        $products = Product::orderBy('name')->get();
        $since = Carbon::today()->subDays($days - 1);

        // Every movement from the first reported day onwards, grouped per product.
        $movements = StockMovement::where('created_at', '>=', $since)->get()->groupBy('product_id');

        $report = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = Carbon::today()->subDays($offset);
            $endOfDay = $day->copy()->endOfDay();
            $records = [];
            $totalInventory = 0.0;
            $totalSales = 0.0;

            foreach ($products as $product) {
                /** @var Collection<int, StockMovement> $productMovements */
                $productMovements = $movements->get($product->id, collect());

                // Stock at the end of the day = current stock minus everything that happened afterwards.
                $available = $product->stock - (int) $productMovements->filter(fn ($m) => $m->created_at->gt($endOfDay))->sum('quantity');

                $sameDay = $productMovements->filter(fn ($m) => $m->created_at->isSameDay($day));
                $qtySold = max(0, -1 * (int) $sameDay
                    ->filter(fn ($m) => in_array($m->type, [StockMovementType::Sale, StockMovementType::Cancellation], true))
                    ->sum('quantity'));

                $inHand = $available + $qtySold;
                $inventoryValue = $inHand * (float) $product->price;
                $salesValue = $qtySold * (float) $product->price;

                $totalInventory += $inventoryValue;
                $totalSales += $salesValue;

                $records[] = [
                    'product' => $product,
                    'qty_in_hand' => $inHand,
                    'qty_sold' => $qtySold,
                    'inventory_value' => $inventoryValue,
                    'sales_value' => $salesValue,
                    'available_stock' => $available,
                    'status' => $available <= $product->reorder_level ? 'Reorder' : 'Available',
                ];
            }

            $report[$day->format('m/d/Y')] = [
                'records' => $records,
                'total_inventory_value' => $totalInventory,
                'total_sales_value' => $totalSales,
            ];
        }

        return $report;
    }
}
