<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TenantController extends Controller
{
    use ActivityLogTrait;

    public function index()
    {
        $userId = auth()->id();

        // Stats
        $pendingBills = Payment::where('tenant_id', $userId)->where('status', 'pending')->count();
        $totalPaid = Payment::where('tenant_id', $userId)->where('status', 'paid')->sum('amount');
        $totalOrders = Order::where('user_id', $userId)->count();
        $maintenanceCount = MaintenanceRequest::where('tenant_id', $userId)->count();

        // Recent bills
        $recentBills = Payment::with('stall')
            ->where('tenant_id', $userId)
            ->orderByDesc('due_date')
            ->limit(5)
            ->get();

        // Recent orders
        $recentOrders = Order::with('product')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Maintenance requests
        $recentMaintenance = MaintenanceRequest::with('stall')
            ->where('tenant_id', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ── NEW: Monthly Spending Chart (last 6 months) ──
        $monthlySpending = [];
        $monthlyLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyLabels[] = $month->format('M');
            $monthlySpending[] = (float) Payment::where('tenant_id', $userId)
                ->where('status', 'paid')
                ->whereYear('paid_date', $month->year)
                ->whereMonth('paid_date', $month->month)
                ->sum('amount');
        }

        // ── NEW: Active contracts ──
        $activeContracts = Contract::with('stall')
            ->where('tenant_id', $userId)
            ->where('status', 'active')
            ->get();

        return view('tenant.dashboard', compact(
            'pendingBills', 'totalPaid', 'totalOrders', 'maintenanceCount',
            'recentBills', 'recentOrders', 'recentMaintenance',
            'monthlySpending', 'monthlyLabels', 'activeContracts'
        ));
    }

    // ---- Product Catalog & Ordering ----
    public function products()
    {
        $products = Product::where('is_active', true)->where('stock', '>', 0)->orderBy('name')->get();
        return view('tenant.products', compact('products'));
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Not enough stock available.');
        }

        $order = Order::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'total_price' => $product->price * $request->quantity,
            'status' => 'completed',
        ]);

        $product->decrement('stock', $request->quantity);
        $this->logActivity('placed_order', auth()->user()->name . ' ordered ' . $request->quantity . 'x "' . $product->name . '"', $order);

        return back()->with('success', 'Order placed successfully!');
    }

    // ---- Bills & Payments ----
    public function bills()
    {
        $bills = Payment::with(['stall', 'manager'])
            ->where('tenant_id', auth()->id())
            ->orderByDesc('due_date')
            ->get();
        return view('tenant.bills', compact('bills'));
    }

    public function payBill(Payment $payment)
    {
        if ($payment->tenant_id !== auth()->id()) abort(403);
        if ($payment->status === 'paid') {
            return back()->with('error', 'This bill is already paid.');
        }

        $payment->update([
            'status' => 'paid',
            'paid_date' => now(),
        ]);

        $this->logActivity('paid_bill', auth()->user()->name . ' paid bill #' . $payment->id . ' (₱' . number_format($payment->amount, 2) . ')', $payment);

        return back()->with('success', 'Payment successful! Receipt generated.');
    }

    // ---- Maintenance Requests ----
    public function maintenance()
    {
        $requests = MaintenanceRequest::with('stall')
            ->where('tenant_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        // Get stalls the tenant is associated with (via contracts or payments)
        $stallIds = Payment::where('tenant_id', auth()->id())->pluck('stall_id')->unique();
        $stalls = Stall::whereIn('id', $stallIds)->get();

        // If no stalls from payments, get all stalls
        if ($stalls->isEmpty()) {
            $stalls = Stall::all();
        }

        return view('tenant.maintenance', compact('requests', 'stalls'));
    }

    public function submitMaintenance(Request $request)
    {
        $request->validate([
            'stall_id' => 'required|exists:stalls,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'image' => 'nullable|image|max:5120', // max 5MB
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('maintenance', 'public');
        }

        $maintenanceRequest = MaintenanceRequest::create([
            'tenant_id' => auth()->id(),
            'stall_id' => $request->stall_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'image_path' => $imagePath,
        ]);

        $this->logActivity('submitted_maintenance', auth()->user()->name . ' submitted maintenance request: "' . $request->subject . '"', $maintenanceRequest);

        return back()->with('success', 'Maintenance request submitted! The manager will be notified.');
    }

    // ---- Maintenance Detail (Timeline View) ----
    public function maintenanceDetail(MaintenanceRequest $maintenanceRequest)
    {
        if ($maintenanceRequest->tenant_id !== auth()->id()) abort(403);
        $maintenanceRequest->load('stall');

        // Get activity log entries related to this maintenance request for timeline
        $timeline = \App\Models\ActivityLog::where('subject_type', MaintenanceRequest::class)
            ->where('subject_id', $maintenanceRequest->id)
            ->orderBy('created_at')
            ->get();

        return view('tenant.maintenance-detail', compact('maintenanceRequest', 'timeline'));
    }

    // ---- Contracts ----
    public function contracts()
    {
        $contracts = Contract::with('stall')
            ->where('tenant_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('tenant.contracts', compact('contracts'));
    }
}
