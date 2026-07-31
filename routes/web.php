<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\StallRentalController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// ── Public Stall Rental Flow (no auth required) ──
Route::get('/stalls', [StallRentalController::class, 'index'])->name('stalls.index');
Route::get('/stalls/{stall}/apply', [StallRentalController::class, 'apply'])->name('stalls.apply');
Route::post('/stalls/{stall}/apply', [StallRentalController::class, 'submitApplication'])->name('stalls.submitApplication');
Route::post('/stalls/{stall}/waitlist', [StallRentalController::class, 'joinWaitlist'])->name('stalls.joinWaitlist');
Route::get('/rental/{application}/checkout', [StallRentalController::class, 'checkout'])->name('rental.checkout');
Route::post('/rental/{application}/pay-online', [StallRentalController::class, 'payOnline'])->name('rental.payOnline');
Route::post('/rental/{application}/pay-in-person', [StallRentalController::class, 'payInPerson'])->name('rental.payInPerson');
Route::get('/rental/{application}/success', [StallRentalController::class, 'success'])->name('rental.success');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // User Management
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.updateRole');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::post('/users/manager', [AdminController::class, 'storeManager'])->name('users.storeManager');

    // Product Management
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::get('/products/create', [AdminController::class, 'createProduct'])->name('products.create');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->name('products.edit');
    Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{product}', [AdminController::class, 'deleteProduct'])->name('products.delete');

    // Audit Log
    Route::get('/audit-log', [AdminController::class, 'auditLog'])->name('audit-log');

    // Location Management
    Route::get('/locations', [AdminController::class, 'locations'])->name('locations');
    Route::post('/locations', [AdminController::class, 'storeLocation'])->name('locations.store');
    Route::delete('/locations/{location}', [AdminController::class, 'deleteLocation'])->name('locations.delete');
});

Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', [ManagerController::class, 'index'])->name('dashboard');

    // Stall Management
    Route::get('/stalls', [ManagerController::class, 'stalls'])->name('stalls');
    Route::get('/stalls/create', [ManagerController::class, 'createStall'])->name('stalls.create');
    Route::post('/stalls', [ManagerController::class, 'storeStall'])->name('stalls.store');
    Route::patch('/stalls/{stall}/status', [ManagerController::class, 'updateStallStatus'])->name('stalls.updateStatus');

    // Floor Plan
    Route::get('/floor-plan', [ManagerController::class, 'floorPlan'])->name('floor-plan');
    Route::patch('/stalls/{stall}/position', [ManagerController::class, 'updateStallPosition'])->name('stalls.updatePosition');

    // Payment Management
    Route::get('/payments', [ManagerController::class, 'payments'])->name('payments');
    Route::post('/payments', [ManagerController::class, 'createPayment'])->name('payments.store');
    Route::patch('/payments/{payment}/paid', [ManagerController::class, 'markPaid'])->name('payments.markPaid');
    Route::patch('/payments/{payment}/overdue', [ManagerController::class, 'markOverdue'])->name('payments.markOverdue');

    // Rental Applications
    Route::get('/rental-applications', [ManagerController::class, 'rentalApplications'])->name('rental-applications');
    Route::patch('/rental-applications/{application}/approve', [ManagerController::class, 'approveApplication'])->name('rental-applications.approve');
    Route::patch('/rental-applications/{application}/reject', [ManagerController::class, 'rejectApplication'])->name('rental-applications.reject');

    // Maintenance Inbox
    Route::get('/inbox', [ManagerController::class, 'inbox'])->name('inbox');
    Route::patch('/maintenance/{maintenanceRequest}/status', [ManagerController::class, 'updateMaintenanceStatus'])->name('maintenance.updateStatus');
});

Route::middleware(['auth', 'role:tenant'])->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/dashboard', [TenantController::class, 'index'])->name('dashboard');

    // Product Catalog & Ordering
    Route::get('/products', [TenantController::class, 'products'])->name('products');
    Route::post('/orders', [TenantController::class, 'placeOrder'])->name('orders.store');

    // Bills & Payments
    Route::get('/bills', [TenantController::class, 'bills'])->name('bills');
    Route::patch('/bills/{payment}/pay', [TenantController::class, 'payBill'])->name('bills.pay');

    // Maintenance Requests
    Route::get('/maintenance', [TenantController::class, 'maintenance'])->name('maintenance');
    Route::post('/maintenance', [TenantController::class, 'submitMaintenance'])->name('maintenance.store');
    Route::get('/maintenance/{maintenanceRequest}', [TenantController::class, 'maintenanceDetail'])->name('maintenance.detail');

    // Contracts
    Route::get('/contracts', [TenantController::class, 'contracts'])->name('contracts');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
    Route::get('/contracts/{contract}/pdf', [ContractController::class, 'downloadPdf'])->name('contracts.pdf');
});

// ── Messaging (all authenticated users) ──
Route::middleware('auth')->prefix('messages')->name('messages.')->group(function () {
    Route::get('/', [MessageController::class, 'index'])->name('index');
    Route::get('/{user}', [MessageController::class, 'conversation'])->name('conversation');
    Route::post('/', [MessageController::class, 'send'])->name('send');
});

// ── Message API (for Alpine.js polling) ──
Route::middleware('auth')->get('/api/messages/{user}', [MessageController::class, 'apiMessages'])->name('api.messages');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
});

// ── Notifications ──
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
});
Route::middleware('auth')->get('/api/notifications', [NotificationController::class, 'apiUnread'])->name('api.notifications');

require __DIR__.'/auth.php';
