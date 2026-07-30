<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Stall;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\RentalApplication;
use App\Models\ActivityLog;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    use ActivityLogTrait;

    public function index()
    {
        // Summary stats
        $totalTenants = User::where('role', 'tenant')->count();
        $totalManagers = User::where('role', 'manager')->count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', 'completed')->sum('total_price');
        $totalStalls = Stall::count();

        // Top products by order quantity
        $topProducts = DB::table('products')
            ->select('products.id', 'products.name', 'products.price', 'products.stock')
            ->selectRaw('COALESCE(SUM(orders.quantity), 0) as total_sold')
            ->leftJoin('orders', function ($join) {
                $join->on('products.id', '=', 'orders.product_id')
                     ->where(function ($q) {
                         $q->whereNull('orders.status')->orWhere('orders.status', 'completed');
                     });
            })
            ->groupBy('products.id', 'products.name', 'products.price', 'products.stock')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Top buying tenants
        $topTenants = DB::table('users')
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('COALESCE(SUM(orders.quantity), 0) as total_bought')
            ->selectRaw('COALESCE(SUM(orders.total_price), 0) as total_spent')
            ->leftJoin('orders', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                     ->where('orders.status', 'completed');
            })
            ->where('users.role', 'tenant')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_bought')
            ->limit(10)
            ->get();

        // Chart data: product sales for bar chart
        $chartLabels = $topProducts->pluck('name')->toArray();
        $chartData = $topProducts->pluck('total_sold')->toArray();

        // Recent orders
        $recentOrders = Order::with(['user', 'product'])->latest()->limit(5)->get();

        // ── NEW: Monthly Revenue Chart (last 12 months) ──
        $monthlyRevenue = [];
        $monthlyLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyLabels[] = $month->format('M Y');
            $monthlyRevenue[] = (float) Payment::where('status', 'paid')
                ->whereYear('paid_date', $month->year)
                ->whereMonth('paid_date', $month->month)
                ->sum('amount');
        }

        // ── NEW: Stall Occupancy Chart ──
        $stallOccupancy = [
            'available' => Stall::where('status', 'available')->count(),
            'occupied' => Stall::where('status', 'occupied')->count(),
            'maintenance' => Stall::where('status', 'maintenance')->count(),
        ];

        // ── NEW: Activity Feed ──
        $activityFeed = ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('admin.dashboard', compact(
            'totalTenants', 'totalManagers', 'totalProducts', 'totalOrders',
            'totalRevenue', 'totalStalls', 'topProducts', 'topTenants',
            'chartLabels', 'chartData', 'recentOrders',
            'monthlyRevenue', 'monthlyLabels', 'stallOccupancy', 'activityFeed'
        ));
    }

    // ---- User Management ----
    public function users()
    {
        $users = User::orderBy('role')->orderBy('name')->get();
        return view('admin.users', compact('users'));
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:admin,manager,tenant']);
        $oldRole = $user->role;
        $user->update(['role' => $request->role]);
        $this->logActivity('updated_role', auth()->user()->name . ' changed ' . $user->name . '\'s role from ' . $oldRole . ' to ' . $request->role, $user, ['old_role' => $oldRole, 'new_role' => $request->role]);
        return back()->with('success', $user->name . '\'s role updated to ' . $request->role);
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        // Cascade-delete all related records
        // 1. Delete rental applications matching user's email
        RentalApplication::where('email', $user->email)->delete();

        // 2. Delete contracts where user is the tenant
        Contract::where('tenant_id', $user->id)->delete();

        // 3. Delete payments where user is the tenant
        Payment::where('tenant_id', $user->id)->delete();

        // 4. Delete maintenance requests where user is the tenant
        \App\Models\MaintenanceRequest::where('tenant_id', $user->id)->delete();

        // 5. Free up any stalls occupied by this user's contracts
        $occupiedStallIds = Stall::whereIn('id', function ($query) use ($user) {
            $query->select('stall_id')->from('contracts')->where('tenant_id', $user->id);
        })->pluck('id');
        Stall::whereIn('id', $occupiedStallIds)->update(['status' => 'available']);

        $this->logActivity('deleted_user', auth()->user()->name . ' deleted user ' . $user->name . ' (' . $user->email . ')', null, ['deleted_user_name' => $user->name, 'deleted_user_email' => $user->email]);
        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    public function storeManager(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $manager = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'manager',
        ]);

        $this->logActivity('created_manager', auth()->user()->name . ' created manager account for ' . $manager->name, $manager);

        return redirect()->route('admin.users')->with('success', 'Manager account created successfully.');
    }

    // ---- Product Management ----
    public function products()
    {
        $products = Product::orderBy('created_at', 'desc')->get();
        return view('admin.products', compact('products'));
    }

    public function createProduct()
    {
        return view('admin.product-form', ['product' => null]);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        $product = Product::create($request->only('name', 'description', 'price', 'stock', 'category'));
        $this->logActivity('created_product', auth()->user()->name . ' added product "' . $product->name . '"', $product);
        return redirect()->route('admin.products')->with('success', 'Product added successfully.');
    }

    public function editProduct(Product $product)
    {
        return view('admin.product-form', compact('product'));
    }

    public function updateProduct(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        $product->update($request->only('name', 'description', 'price', 'stock', 'category'));
        $this->logActivity('updated_product', auth()->user()->name . ' updated product "' . $product->name . '"', $product);
        return redirect()->route('admin.products')->with('success', 'Product updated successfully.');
    }

    public function deleteProduct(Product $product)
    {
        $this->logActivity('deleted_product', auth()->user()->name . ' deleted product "' . $product->name . '"', null, ['deleted_product_name' => $product->name]);
        $product->delete();
        return redirect()->route('admin.products')->with('success', 'Product deleted successfully.');
    }

    // ---- Audit Log ----
    public function auditLog(Request $request)
    {
        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(30);
        $users = User::orderBy('name')->get();
        $actions = ActivityLog::select('action')->distinct()->pluck('action');

        return view('admin.audit-log', compact('logs', 'users', 'actions'));
    }

    // ---- Location Management ----
    public function locations()
    {
        $locations = \App\Models\Location::withCount('stalls')->get();
        return view('admin.locations', compact('locations'));
    }

    public function storeLocation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
        ]);

        $location = \App\Models\Location::create($request->only('name', 'address', 'description'));
        $this->logActivity('created_location', auth()->user()->name . ' created location "' . $location->name . '"', $location);

        return back()->with('success', 'Location created successfully.');
    }

    public function deleteLocation(\App\Models\Location $location)
    {
        if ($location->stalls()->count() > 0) {
            return back()->with('error', 'Cannot delete a location that has stalls assigned to it.');
        }
        $this->logActivity('deleted_location', auth()->user()->name . ' deleted location "' . $location->name . '"', null, ['deleted_location_name' => $location->name]);
        $location->delete();
        return back()->with('success', 'Location deleted successfully.');
    }
}
