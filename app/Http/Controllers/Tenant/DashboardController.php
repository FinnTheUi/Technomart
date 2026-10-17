<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        return view('tenant.dashboard', [
            'pendingBills' => Payment::where('tenant_id', $userId)->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Overdue])->count(),
            'totalPaid' => Payment::where('tenant_id', $userId)->where('status', PaymentStatus::Paid)->sum('amount'),
            'totalOrders' => Order::where('user_id', $userId)->count(),
            'maintenanceCount' => MaintenanceRequest::where('tenant_id', $userId)->count(),

            'recentBills' => Payment::with('stall')->where('tenant_id', $userId)->orderByDesc('due_date')->limit(5)->get(),
            'recentOrders' => Order::with('product')->where('user_id', $userId)->latest()->limit(5)->get(),
            'recentMaintenance' => MaintenanceRequest::with('stall')->where('tenant_id', $userId)->latest()->limit(5)->get(),

            ...$this->monthlySpending($userId),

            'activeContracts' => Contract::with('stall')->where('tenant_id', $userId)->where('status', ContractStatus::Active)->get(),
        ]);
    }

    /**
     * @return array{monthlySpending: array<int, float>, monthlyLabels: array<int, string>}
     */
    private function monthlySpending(int $userId): array
    {
        $paid = Payment::where('tenant_id', $userId)
            ->where('status', PaymentStatus::Paid)
            ->where('paid_date', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->get(['amount', 'late_fee', 'paid_date'])
            ->groupBy(fn (Payment $p) => $p->paid_date->format('Y-m'));

        $labels = [];
        $spending = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M');
            $spending[] = (float) ($paid->get($month->format('Y-m'))?->sum(fn (Payment $p) => $p->amountDue()) ?? 0);
        }

        return ['monthlySpending' => $spending, 'monthlyLabels' => $labels];
    }
}
