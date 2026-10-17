<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PlaceOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\Billing\OnlinePaymentService;
use App\Services\Inventory\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function catalog(): View
    {
        return view('tenant.products', [
            'products' => Product::where('is_active', true)
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->fragment('product-list'),
        ]);
    }

    public function index(Request $request, OnlinePaymentService $online): View
    {
        return view('tenant.orders', [
            'orders' => Order::with(['product', 'latestActivity'])
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->fragment('order-records'),
            'onlinePayments' => $online->isEnabled(),
        ]);
    }

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        try {
            $this->orders->place($request->user(), Product::findOrFail($request->product_id), $request->integer('quantity'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tenant.orders')->with('success', 'Order placed! Please pay within '.config('billing.order_auto_cancel_days').' days to confirm it.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('cancel', $order);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $this->orders->cancel($order, $request->user(), 'Cancelled by tenant: '.$validated['reason']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order cancelled.');
    }
}
