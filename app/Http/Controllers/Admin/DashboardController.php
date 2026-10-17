<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StallStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stall;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $sold = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::sold());

        // Top products by quantity sold
        $topProducts = DB::table('products')
            ->select('products.id', 'products.name', 'products.price', 'products.stock')
            ->selectRaw('COALESCE(SUM(orders.quantity), 0) as total_sold')
            ->leftJoin('orders', fn ($join) => $join->on('products.id', '=', 'orders.product_id')->whereIn('orders.status', $sold))
            ->groupBy('products.id', 'products.name', 'products.price', 'products.stock')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Top buying tenants
        $topTenants = DB::table('users')
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('COALESCE(SUM(orders.quantity), 0) as total_bought')
            ->selectRaw('COALESCE(SUM(orders.total_price), 0) as total_spent')
            ->leftJoin('orders', fn ($join) => $join->on('users.id', '=', 'orders.user_id')->whereIn('orders.status', $sold))
            ->where('users.role', User::ROLE_TENANT)
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_bought')
            ->limit(10)
            ->get();

        $stallCounts = Stall::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.dashboard', [
            'totalTenants' => User::where('role', User::ROLE_TENANT)->count(),
            'totalManagers' => User::where('role', User::ROLE_MANAGER)->count(),
            'totalProducts' => Product::count(),
            'totalOrders' => Order::count(),
            'totalRevenue' => Order::whereIn('status', $sold)->sum('total_price'),
            'totalStalls' => Stall::count(),
            'lowStockCount' => Product::where('is_active', true)->lowStock()->count(),
            'ordersAwaitingAction' => Order::whereIn('status', [OrderStatus::ForVerification, OrderStatus::Paid])->count(),
            'topProducts' => $topProducts,
            'topTenants' => $topTenants,
            'chartLabels' => $topProducts->pluck('name')->toArray(),
            'chartData' => $topProducts->pluck('total_sold')->toArray(),
            'recentOrders' => Order::with(['user', 'product'])->latest()->limit(5)->get(),
            ...$this->monthlyRevenue(),
            'stallOccupancy' => [
                'available' => $stallCounts[StallStatus::Available->value] ?? 0,
                'occupied' => $stallCounts[StallStatus::Occupied->value] ?? 0,
                'maintenance' => $stallCounts[StallStatus::Maintenance->value] ?? 0,
            ],
            'activityFeed' => ActivityLog::with('user')->latest()->limit(15)->get(),
        ]);
    }

    /**
     * Rent collected per month for the last 12 months.
     *
     * @return array{monthlyRevenue: array<int, float>, monthlyLabels: array<int, string>}
     */
    private function monthlyRevenue(): array
    {
        $paid = Payment::where('status', PaymentStatus::Paid)
            ->where('paid_date', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->get(['amount', 'late_fee', 'paid_date'])
            ->groupBy(fn (Payment $p) => $p->paid_date->format('Y-m'));

        $labels = [];
        $revenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $revenue[] = (float) ($paid->get($month->format('Y-m'))?->sum(fn (Payment $p) => $p->amountDue()) ?? 0);
        }

        return ['monthlyRevenue' => $revenue, 'monthlyLabels' => $labels];
    }
}
