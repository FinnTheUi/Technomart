<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stock ledger (every movement) and manual count adjustments.
 */
class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $movements = StockMovement::with(['product', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.inventory', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->get(),
            'lowStock' => Product::where('is_active', true)->lowStock()->orderBy('stock')->orderBy('id')
                ->paginate(10, ['*'], 'low_stock_page')->withQueryString(),
        ]);
    }

    public function adjust(AdjustStockRequest $request, InventoryService $inventory): RedirectResponse
    {
        $product = Product::findOrFail($request->product_id);
        $movement = $inventory->adjustTo($product, $request->integer('counted_stock'), $request->user(), $request->note);

        return back()->with('success', $movement
            ? 'Stock for "'.$product->name.'" adjusted by '.sprintf('%+d', $movement->quantity).'.'
            : 'Stock already matches the counted quantity.');
    }
}

