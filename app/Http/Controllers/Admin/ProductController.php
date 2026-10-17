<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Inventory\InventoryReport;
use App\Services\Inventory\InventoryService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    use ActivityLogTrait;

    public function __construct(private readonly InventoryService $inventory) {}

    public function index(InventoryReport $report): View
    {
        return view('admin.products', [
            'products' => Product::with('supplier')->orderBy('name')->get(),
            'inventoryReport' => $report->daily(3),
        ]);
    }

    public function create(): View
    {
        return view('admin.product-form', ['product' => null, 'suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create([...$request->productAttributes(), 'stock' => 0]);

            if ($request->integer('stock') > 0) {
                $this->inventory->move($product, $request->integer('stock'), StockMovementType::Initial, null, $request->user(), 'Opening stock');
            }

            return $product;
        });

        $this->logActivity('created_product', $request->user()->name.' added product "'.$product->name.'"', $product);

        return redirect()->route('admin.products')->with('success', 'Product added successfully.');
    }

    public function edit(Product $product): View
    {
        return view('admin.product-form', ['product' => $product, 'suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($request->productAttributes());

            // A changed stock value is recorded as an adjustment in the stock ledger.
            $this->inventory->adjustTo($product, $request->integer('stock'), $request->user(), 'Edited on product form');
        });

        $this->logActivity('updated_product', $request->user()->name.' updated product "'.$product->name.'"', $product);

        return redirect()->route('admin.products')->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        if ($product->orders()->exists()) {
            $product->update(['is_active' => false]);
            $this->logActivity('deactivated_product', $request->user()->name.' hid product "'.$product->name.'" (it has order history)', $product);

            return redirect()->route('admin.products')->with('success', 'Product has order history, so it was hidden from the catalog instead of deleted.');
        }

        $this->logActivity('deleted_product', $request->user()->name.' deleted product "'.$product->name.'"', null, ['deleted_product_name' => $product->name]);
        $product->delete();

        return redirect()->route('admin.products')->with('success', 'Product deleted successfully.');
    }
}
