<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Inventory\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Tenant supply orders. Payment verification uses Billing\PaymentFlowController.
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $request->validate(['status' => ['nullable', Rule::enum(OrderStatus::class)]]);

        return view('admin.orders', [
            'orders' => Order::with(['user', 'product'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [OrderStatus::ForVerification->value, OrderStatus::Paid->value])
                ->latest()
                ->paginate(30)
                ->withQueryString(),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        return $this->attempt(fn () => $this->orders->markDelivered($order, $request->user()), 'Order marked as delivered.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        return $this->attempt(fn () => $this->orders->cancel($order, $request->user(), 'Cancelled by administrator'), 'Order cancelled and stock returned.');
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
