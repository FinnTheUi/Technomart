<?php

namespace App\Http\Controllers\Manager;

use App\Enums\MaintenanceStatus;
use App\Enums\PaymentStatus;
use App\Enums\StallStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Stall;
use App\Services\Analytics\TenantRiskAnalyzer;
use App\Services\Rental\StallLayoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, TenantRiskAnalyzer $riskAnalyzer, StallLayoutService $layout): View
    {
        $layout->sync($request->user()); // first visit generates the floor layout
        $managerId = $request->user()->id;

        $stalls = Stall::where('manager_id', $managerId)->get();
        $stallCounts = $stalls->countBy(fn (Stall $s) => $s->status->value);

        $payments = Payment::where('manager_id', $managerId);
        $statusCounts = (clone $payments)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $stallIds = $stalls->pluck('id');

        $stallFilters = $request->validate([
            'stall_q' => ['nullable', 'string', 'max:50'],
            'stall_status' => ['nullable', Rule::enum(StallStatus::class)],
        ]);

        return view('manager.dashboard', [
            'stalls' => $stalls,

            // Stall Availability panel: searchable, 8 per page.
            'stallFilters' => ['q' => trim($stallFilters['stall_q'] ?? ''), 'status' => $stallFilters['stall_status'] ?? null],
            'stallPage' => Stall::where('manager_id', $managerId)
                ->searchName($stallFilters['stall_q'] ?? null)
                ->when($stallFilters['stall_status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->orderBy('floor_number')->orderBy('stall_number')
                ->paginate(8, ['*'], 'stalls_page')
                ->withQueryString()->fragment('stall-availability'),
            'totalStalls' => $stalls->count(),
            'availableStalls' => $stallCounts[StallStatus::Available->value] ?? 0,
            'occupiedStalls' => $stallCounts[StallStatus::Occupied->value] ?? 0,
            'maintenanceStalls' => $stallCounts[StallStatus::Maintenance->value] ?? 0,

            'totalCollected' => (clone $payments)->where('status', PaymentStatus::Paid)->sum('amount'),
            'totalPending' => (clone $payments)->whereIn('status', [PaymentStatus::Pending, PaymentStatus::ForVerification])->sum('amount'),
            'overdueCount' => $statusCounts[PaymentStatus::Overdue->value] ?? 0,
            'awaitingVerification' => $statusCounts[PaymentStatus::ForVerification->value] ?? 0,
            'renewalRequests' => Contract::whereIn('stall_id', $stallIds)->whereNotNull('renewal_requested_at')->count(),

            'recentPayments' => Payment::with(['tenant', 'stall'])->where('manager_id', $managerId)->latest()
                ->paginate(5, ['*'], 'payments_page')->withQueryString()->fragment('recent-payments'),

            // Predictive analytics: cached late-payment risk (refreshed daily by `ml:score`).
            'riskTenants' => $riskAnalyzer->paginateForManager($managerId),
            'modelMetrics' => File::exists(storage_path('app/ml/model_metrics.json'))
                ? json_decode(File::get(storage_path('app/ml/model_metrics.json')), true)
                : null,

            'maintenanceRequests' => MaintenanceRequest::with(['tenant', 'stall'])->whereIn('stall_id', $stallIds)->latest()->limit(20)->get(),
            'unreadCount' => MaintenanceRequest::whereIn('stall_id', $stallIds)->where('status', MaintenanceStatus::Pending)->count(),

            ...$this->monthlyRevenue($managerId),

            'paymentStatusData' => [
                'paid' => $statusCounts[PaymentStatus::Paid->value] ?? 0,
                'pending' => ($statusCounts[PaymentStatus::Pending->value] ?? 0) + ($statusCounts[PaymentStatus::ForVerification->value] ?? 0),
                'overdue' => $statusCounts[PaymentStatus::Overdue->value] ?? 0,
            ],

            'activityFeed' => ActivityLog::with('user')->where('user_id', $managerId)->latest()
                ->paginate(5, ['*'], 'activity_page')->withQueryString()->fragment('activity-feed'),
        ]);
    }

    /**
     * Paid rent per month for the last six months.
     *
     * @return array{monthlyRevenue: array<int, float>, monthlyLabels: array<int, string>}
     */
    private function monthlyRevenue(int $managerId): array
    {
        $start = Carbon::now()->subMonths(5)->startOfMonth();

        $paid = Payment::where('manager_id', $managerId)
            ->where('status', PaymentStatus::Paid)
            ->where('paid_date', '>=', $start)
            ->get(['amount', 'late_fee', 'paid_date'])
            ->groupBy(fn (Payment $p) => $p->paid_date->format('Y-m'));

        $labels = [];
        $revenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M');
            $revenue[] = (float) ($paid->get($month->format('Y-m'))?->sum(fn (Payment $p) => $p->amountDue()) ?? 0);
        }

        return ['monthlyRevenue' => $revenue, 'monthlyLabels' => $labels];
    }
}
