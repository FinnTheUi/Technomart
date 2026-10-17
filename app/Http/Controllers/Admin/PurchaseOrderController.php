<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Restocking: draft → ordered → received (stock added), or cancelled.
 */
class PurchaseOrderController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(): View
    {
        return view('admin.purchase-orders', [
            'purchaseOrders' => PurchaseOrder::with('supplier')->withCount('items')->latest()->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
            'lowStock' => Product::where('is_active', true)->lowStock()->with('supplier')->orderBy('stock')->get(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $po = $this->inventory->createPurchaseOrder($request->integer('supplier_id'), $request->items(), $request->notes, $request->user());

        return redirect()->route('admin.purchase-orders.show', $po)->with('success', 'Purchase order '.$po->po_number.' created.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        return view('admin.purchase-order', [
            'po' => $purchaseOrder->load('supplier', 'items.product', 'creator'),
        ]);
    }

    public function markOrdered(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return $this->attempt(fn () => $this->inventory->markOrdered($purchaseOrder, $request->user()), 'Marked as ordered.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return $this->attempt(fn () => $this->inventory->receive($purchaseOrder, $request->user()), 'Goods received and added to stock.');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return $this->attempt(fn () => $this->inventory->cancelPurchaseOrder($purchaseOrder, $request->user()), 'Purchase order cancelled.');
    }

    private function attempt(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
