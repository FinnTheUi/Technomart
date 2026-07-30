<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stall;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\RentalApplication;
use App\Models\MaintenanceRequest;
use App\Models\ActivityLog;
use App\Mail\TenantCredentialsMail;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ManagerController extends Controller
{
    use ActivityLogTrait;

    public function index()
    {
        $managerId = auth()->id();

        // Stall stats
        $stalls = Stall::where('manager_id', $managerId)->get();
        $totalStalls = $stalls->count();
        $availableStalls = $stalls->where('status', 'available')->count();
        $occupiedStalls = $stalls->where('status', 'occupied')->count();
        $maintenanceStalls = $stalls->where('status', 'maintenance')->count();

        // Payment stats
        $totalCollected = Payment::where('manager_id', $managerId)->where('status', 'paid')->sum('amount');
        $totalPending = Payment::where('manager_id', $managerId)->where('status', 'pending')->sum('amount');
        $overdueCount = Payment::where('manager_id', $managerId)->where('status', 'overdue')->count();

        // Recent payments
        $recentPayments = Payment::with(['tenant', 'stall'])
            ->where('manager_id', $managerId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Predictive Analytics: ML Logistic Regression Integration
        $rawTenants = DB::table('payments')
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('COUNT(CASE WHEN payments.status = "overdue" THEN 1 END) as overdue_count')
            ->selectRaw('COUNT(CASE WHEN payments.status = "paid" THEN 1 END) as paid_count')
            ->selectRaw('AVG(payments.amount) as avg_amount')
            ->selectRaw('COUNT(*) as total_payments')
            ->join('users', 'payments.tenant_id', '=', 'users.id')
            ->where('payments.manager_id', $managerId)
            ->groupBy('users.id', 'users.name', 'users.email')
            ->havingRaw('total_payments > 0')
            ->get();

        $mlData = $rawTenants->map(function ($t) {
            $total = $t->paid_count + $t->overdue_count;
            return [
                'id' => $t->id,
                'past_paid' => (int) $t->paid_count,
                'past_overdue' => (int) $t->overdue_count,
                'overdue_ratio' => $total > 0 ? ($t->overdue_count / $total) : 0,
                'amount' => (float) $t->avg_amount
            ];
        })->toJson();

        $process = new \Symfony\Component\Process\Process(['python', base_path('ml/predict_risk.py'), $mlData]);
        $process->run();

        if ($process->isSuccessful()) {
            $predictions = json_decode($process->getOutput(), true);
            $predMap = collect($predictions)->keyBy('id');
            
            $riskTenants = $rawTenants->map(function ($t) use ($predMap) {
                if (isset($predMap[$t->id])) {
                    $prob = $predMap[$t->id]['risk_probability'] * 100;
                    $t->risk_score = round($prob);
                } else {
                    $t->risk_score = 0;
                }
                
                $t->risk_level = $t->risk_score >= 50 ? 'High' : ($t->risk_score >= 25 ? 'Medium' : 'Low');
                return $t;
            })->sortByDesc('risk_score')->take(10);
        } else {
            // Fallback to simple ratio if ML fails
            $riskTenants = $rawTenants->map(function ($t) {
                $t->risk_score = $t->total_payments > 0
                    ? round(($t->overdue_count / $t->total_payments) * 100)
                    : 0;
                $t->risk_level = $t->risk_score >= 50 ? 'High' : ($t->risk_score >= 25 ? 'Medium' : 'Low');
                return $t;
            })->sortByDesc('risk_score')->take(10);
        }

        // Maintenance inbox
        $stallIds = $stalls->pluck('id');
        $maintenanceRequests = MaintenanceRequest::with(['tenant', 'stall'])
            ->whereIn('stall_id', $stallIds)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
        $unreadCount = MaintenanceRequest::whereIn('stall_id', $stallIds)
            ->where('status', 'pending')
            ->count();

        // ── NEW: Monthly Revenue Chart (last 6 months) ──
        $monthlyRevenue = [];
        $monthlyLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyLabels[] = $month->format('M');
            $monthlyRevenue[] = (float) Payment::where('manager_id', $managerId)
                ->where('status', 'paid')
                ->whereYear('paid_date', $month->year)
                ->whereMonth('paid_date', $month->month)
                ->sum('amount');
        }

        // ── NEW: Payment Status Breakdown ──
        $paymentStatusData = [
            'paid' => Payment::where('manager_id', $managerId)->where('status', 'paid')->count(),
            'pending' => Payment::where('manager_id', $managerId)->where('status', 'pending')->count(),
            'overdue' => Payment::where('manager_id', $managerId)->where('status', 'overdue')->count(),
        ];

        // ── NEW: Activity Feed (scoped to manager's stalls) ──
        $activityFeed = ActivityLog::with('user')
            ->where('user_id', $managerId)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('manager.dashboard', compact(
            'stalls', 'totalStalls', 'availableStalls', 'occupiedStalls', 'maintenanceStalls',
            'totalCollected', 'totalPending', 'overdueCount',
            'recentPayments', 'riskTenants',
            'maintenanceRequests', 'unreadCount',
            'monthlyRevenue', 'monthlyLabels', 'paymentStatusData', 'activityFeed'
        ));
    }

    // Stall Management
    public function stalls()
    {
        $stalls = Stall::where('manager_id', auth()->id())->get();
        return view('manager.stalls', compact('stalls'));
    }

    public function createStall()
    {
        return view('manager.stall-form', ['stall' => null]);
    }

    public function storeStall(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'floor_x' => 'nullable|integer|min:0',
            'floor_y' => 'nullable|integer|min:0',
            'floor_label' => 'nullable|string|max:10',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $stall = Stall::create([
            'manager_id' => auth()->id(),
            'name' => $request->name,
            'location' => $request->location,
            'size' => $request->size,
            'price' => $request->price,
            'status' => 'available',
            'floor_x' => $request->floor_x,
            'floor_y' => $request->floor_y,
            'floor_label' => $request->floor_label,
            'location_id' => $request->location_id,
        ]);

        $this->logActivity('created_stall', auth()->user()->name . ' created stall "' . $stall->name . '"', $stall);

        return redirect()->route('manager.stalls')->with('success', 'Stall created successfully.');
    }

    public function updateStallStatus(Request $request, Stall $stall)
    {
        if ($stall->manager_id !== auth()->id()) abort(403);
        $request->validate(['status' => 'required|in:available,occupied,maintenance']);
        $oldStatus = $stall->status;
        $stall->update(['status' => $request->status]);

        $this->logActivity('updated_stall_status', auth()->user()->name . ' changed stall "' . $stall->name . '" from ' . $oldStatus . ' to ' . $request->status, $stall);

        // If stall became available, notify waitlisted users
        if ($request->status === 'available' && $oldStatus !== 'available') {
            $waitlisted = \App\Models\Waitlist::where('stall_id', $stall->id)
                ->where('status', 'waiting')
                ->get();

            foreach ($waitlisted as $entry) {
                // Find or create a temp user for notification
                $user = User::where('email', $entry->email)->first();
                if ($user) {
                    $user->notify(new \App\Notifications\StallAvailableNotification($stall, $entry));
                }
                $entry->update(['status' => 'notified', 'notified_at' => now()]);
            }
        }

        return back()->with('success', 'Stall status updated.');
    }

    // ── Floor Plan ──
    public function floorPlan()
    {
        $stalls = Stall::where('manager_id', auth()->id())->get();
        return view('manager.floor-plan', compact('stalls'));
    }

    public function updateStallPosition(Request $request, Stall $stall)
    {
        if ($stall->manager_id !== auth()->id()) abort(403);
        $request->validate([
            'floor_x' => 'required|integer|min:0',
            'floor_y' => 'required|integer|min:0',
        ]);
        $stall->update([
            'floor_x' => $request->floor_x,
            'floor_y' => $request->floor_y,
        ]);
        return response()->json(['success' => true]);
    }

    // Payments
    public function payments()
    {
        $payments = Payment::with(['tenant', 'stall'])
            ->where('manager_id', auth()->id())
            ->orderByDesc('due_date')
            ->get();
        $tenants = User::where('role', 'tenant')->get();
        $stalls = Stall::where('manager_id', auth()->id())->get();
        return view('manager.payments', compact('payments', 'tenants', 'stalls'));
    }

    public function createPayment(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:users,id',
            'stall_id' => 'required|exists:stalls,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
        ]);

        $payment = Payment::create([
            'tenant_id' => $request->tenant_id,
            'manager_id' => auth()->id(),
            'stall_id' => $request->stall_id,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => 'pending',
        ]);

        $this->logActivity('created_payment', auth()->user()->name . ' created a ₱' . number_format($request->amount, 2) . ' bill for tenant', $payment);

        return back()->with('success', 'Payment bill created successfully.');
    }

    public function markPaid(Payment $payment)
    {
        if ($payment->manager_id !== auth()->id()) abort(403);
        $payment->update(['status' => 'paid', 'paid_date' => now()]);
        $this->logActivity('marked_paid', auth()->user()->name . ' marked payment #' . $payment->id . ' as paid', $payment);
        return back()->with('success', 'Payment marked as paid.');
    }

    public function markOverdue(Payment $payment)
    {
        if ($payment->manager_id !== auth()->id()) abort(403);
        $payment->update(['status' => 'overdue']);
        $this->logActivity('marked_overdue', auth()->user()->name . ' marked payment #' . $payment->id . ' as overdue', $payment);

        // Send overdue notification to tenant
        $tenant = $payment->tenant;
        if ($tenant) {
            $tenant->notify(new \App\Notifications\OverduePaymentNotification($payment));
        }

        return back()->with('success', 'Payment marked as overdue.');
    }

    // Maintenance Inbox
    public function inbox()
    {
        $stallIds = Stall::where('manager_id', auth()->id())->pluck('id');
        $requests = MaintenanceRequest::with(['tenant', 'stall'])
            ->whereIn('stall_id', $stallIds)
            ->orderByDesc('created_at')
            ->get();
        return view('manager.inbox', compact('requests'));
    }

    public function updateMaintenanceStatus(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $request->validate(['status' => 'required|in:pending,in_progress,resolved,closed']);
        $oldStatus = $maintenanceRequest->status;
        $maintenanceRequest->update(['status' => $request->status]);
        $this->logActivity('updated_maintenance', auth()->user()->name . ' changed maintenance request "' . $maintenanceRequest->subject . '" from ' . $oldStatus . ' to ' . $request->status, $maintenanceRequest);
        return back()->with('success', 'Request status updated.');
    }

    // ── Rental Applications ──

    /**
     * List all rental applications for stalls owned by this manager.
     */
    public function rentalApplications()
    {
        $stallIds = Stall::where('manager_id', auth()->id())->pluck('id');
        $applications = RentalApplication::with('stall')
            ->whereIn('stall_id', $stallIds)
            ->orderByDesc('created_at')
            ->get();

        return view('manager.rental-applications', compact('applications'));
    }

    /**
     * Approve a rental application — create tenant user, contract, mark stall occupied.
     */
    public function approveApplication(RentalApplication $application)
    {
        // Ensure the stall belongs to this manager
        $stall = Stall::where('id', $application->stall_id)
            ->where('manager_id', auth()->id())
            ->firstOrFail();

        // Generate credentials
        $plainPassword = Str::random(10);
        
        $user = User::where('email', $application->email)->first();
        $isNewUser = false;

        if (!$user) {
            // Create tenant user account
            $user = User::create([
                'name' => $application->name,
                'email' => $application->email,
                'password' => Hash::make($plainPassword),
                'role' => 'tenant',
            ]);
            $isNewUser = true;
        }

        // Create contract
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addMonths($application->rental_duration);

        $contract = Contract::create([
            'tenant_id' => $user->id,
            'stall_id' => $application->stall_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'terms' => 'Rental duration: ' . $application->rental_duration . ' month(s). Payment in person.',
            'status' => 'active',
        ]);
        
        // Generate monthly payments
        for ($i = 0; $i < $application->rental_duration; $i++) {
            $dueDate = $startDate->copy()->addMonths($i);
            \App\Models\Payment::create([
                'tenant_id' => $user->id,
                'manager_id' => $stall->manager_id,
                'stall_id' => $application->stall_id,
                'amount' => $stall->price,
                'due_date' => $dueDate,
                // In-person payments are pending until the manager manually marks them as paid
                'status' => 'pending',
            ]);
        }

        // Mark stall as occupied
        $stall->update(['status' => 'occupied']);

        // Update application
        $application->update([
            'status' => 'approved',
            'payment_status' => 'paid',
            'generated_username' => $application->email,
            'generated_password' => $isNewUser ? $plainPassword : null,
        ]);

        // Send credentials email
        Mail::to($application->email)->send(new TenantCredentialsMail($application));

        // Send approval notification
        $user->notify(new \App\Notifications\ApplicationApprovedNotification($application, $stall));

        $this->logActivity('approved_application', auth()->user()->name . ' approved rental application from ' . $application->name . ' for stall "' . $stall->name . '"', $application);

        return back()->with('success', 'Application approved! Tenant account created and credentials sent to ' . $application->email);
    }

    /**
     * Reject a rental application.
     */
    public function rejectApplication(RentalApplication $application)
    {
        // Ensure the stall belongs to this manager
        Stall::where('id', $application->stall_id)
            ->where('manager_id', auth()->id())
            ->firstOrFail();

        $application->update(['status' => 'rejected']);
        $this->logActivity('rejected_application', auth()->user()->name . ' rejected rental application from ' . $application->name, $application);

        return back()->with('success', 'Application rejected.');
    }
}
