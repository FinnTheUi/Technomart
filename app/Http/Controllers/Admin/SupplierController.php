<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupplierRequest;
use App\Models\Supplier;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    use ActivityLogTrait;

    public function index(): View
    {
        return view('admin.suppliers', [
            'suppliers' => Supplier::withCount(['products', 'purchaseOrders'])
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->fragment('supplier-records'),
        ]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());
        $this->logActivity('created_supplier', $request->user()->name.' added supplier "'.$supplier->name.'"', $supplier);

        return back()->with('success', 'Supplier added.');
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());
        $this->logActivity('updated_supplier', $request->user()->name.' updated supplier "'.$supplier->name.'"', $supplier);

        return back()->with('success', 'Supplier updated.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'This supplier has purchase orders and cannot be deleted.');
        }

        $this->logActivity('deleted_supplier', $request->user()->name.' deleted supplier "'.$supplier->name.'"', null, ['name' => $supplier->name]);
        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }
}

