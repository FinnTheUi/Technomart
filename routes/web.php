<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Billing\PaymentFlowController;
use App\Http\Controllers\Billing\PayMongoWebhookController;
use App\Http\Controllers\Billing\ReceiptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Manager;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StallAvailabilityController;
use App\Http\Controllers\StallRentalController;
use App\Http\Controllers\Tenant;
use App\Services\Billing\PayableResolver;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/terms', 'terms')->name('terms');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// ── Public stall rental flow (guests) ──
Route::get('/stalls', [StallRentalController::class, 'index'])->name('stalls.index');
Route::get('/stalls/{stall}/apply', [StallRentalController::class, 'apply'])->name('stalls.apply');
Route::get('/stalls/availability', StallAvailabilityController::class)->middleware('throttle:120,1')->name('stalls.availability');
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/stalls/{stall}/apply', [StallRentalController::class, 'submitApplication'])->name('stalls.submitApplication');
    Route::post('/stalls/{stall}/waitlist', [StallRentalController::class, 'joinWaitlist'])->name('stalls.joinWaitlist');
});

// Applicant follow-up pages are only reachable via expiring signed URLs.
Route::middleware('signed')->prefix('rental/{application}')->name('rental.')->group(function () {
    Route::get('/checkout', [StallRentalController::class, 'checkout'])->name('checkout');
    Route::post('/pay-online', [StallRentalController::class, 'payOnline'])->name('payOnline');
    Route::post('/pay-gateway', [StallRentalController::class, 'payGateway'])->name('payGateway');
    Route::post('/pay-in-person', [StallRentalController::class, 'payInPerson'])->name('payInPerson');
    Route::get('/success', [StallRentalController::class, 'success'])->name('success');
});

// ── Payment gateway webhook (authenticated by signature; CSRF-exempt in bootstrap/app.php) ──
Route::post('/webhooks/paymongo', PayMongoWebhookController::class)->middleware('throttle:webhooks')->name('webhooks.paymongo');

// ── Admin ──
Route::middleware(['auth', 'verified', 'role:admin', 'throttle:writes'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/system-health', Admin\SystemHealthController::class)->name('system-health');
    Route::get('/reports/{report}.csv', [Admin\ReportController::class, 'download'])
        ->whereIn('report', ['rent', 'orders', 'inventory'])->name('reports.download');

    // Users
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users');
    Route::post('/users/manager', [Admin\UserController::class, 'storeManager'])->name('users.storeManager');
    Route::patch('/users/{user}/role', [Admin\UserController::class, 'updateRole'])->name('users.updateRole');
    Route::patch('/users/{user}/active', [Admin\UserController::class, 'toggleActive'])->name('users.toggleActive');
    Route::delete('/users/{user}/sessions', [Admin\UserController::class, 'invalidateSessions'])->name('users.invalidateSessions');
    Route::post('/users/{user}/resend-verification', [Admin\UserController::class, 'resendVerification'])->middleware('throttle:6,1')->name('users.resendVerification');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.delete');

    // Products & inventory
    Route::get('/products', [Admin\ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [Admin\ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [Admin\ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [Admin\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [Admin\ProductController::class, 'destroy'])->name('products.delete');

    Route::get('/inventory', [Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/adjust', [Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Suppliers & purchase orders
    Route::get('/suppliers', [Admin\SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [Admin\SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [Admin\SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [Admin\SupplierController::class, 'destroy'])->name('suppliers.destroy');

    Route::get('/purchase-orders', [Admin\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::post('/purchase-orders', [Admin\PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/purchase-orders/{purchaseOrder}', [Admin\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::patch('/purchase-orders/{purchaseOrder}/ordered', [Admin\PurchaseOrderController::class, 'markOrdered'])->name('purchase-orders.ordered');
    Route::patch('/purchase-orders/{purchaseOrder}/receive', [Admin\PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::patch('/purchase-orders/{purchaseOrder}/cancel', [Admin\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    // Tenant supply orders
    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}/deliver', [Admin\OrderController::class, 'deliver'])->name('orders.deliver');
    Route::patch('/orders/{order}/cancel', [Admin\OrderController::class, 'cancel'])->name('orders.cancel');

    // Audit log & locations
    Route::get('/audit-log', [Admin\AuditLogController::class, 'index'])->name('audit-log');
    Route::get('/audit-log/export', [Admin\AuditLogController::class, 'export'])->name('audit-log.export');
    Route::get('/locations', [Admin\LocationController::class, 'index'])->name('locations');
    Route::post('/locations', [Admin\LocationController::class, 'store'])->name('locations.store');
    Route::delete('/locations/{location}', [Admin\LocationController::class, 'destroy'])->name('locations.delete');
});

// ── Manager ──
Route::middleware(['auth', 'verified', 'role:manager', 'throttle:writes'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', [Manager\DashboardController::class, 'index'])->name('dashboard');

    // Stalls & floor plan
    Route::get('/stalls', [Manager\StallController::class, 'index'])->name('stalls');
    Route::get('/stalls/{stall}/edit', [Manager\StallController::class, 'edit'])->name('stalls.edit');
    Route::put('/stalls/{stall}', [Manager\StallController::class, 'update'])->name('stalls.update');
    Route::post('/stalls/{stall}/tenant', [Manager\StallController::class, 'assignTenant'])->name('stalls.assignTenant');
    Route::patch('/stalls/{stall}/status', [Manager\StallController::class, 'updateStatus'])->name('stalls.updateStatus');
    Route::get('/floor-plan', [Manager\StallController::class, 'floorPlan'])->name('floor-plan');
    Route::patch('/stalls/{stall}/position', [Manager\StallController::class, 'updatePosition'])->name('stalls.updatePosition');

    // Rent & electric bills (verify / reject / record cash: see billing.* routes)
    Route::get('/payments', [Manager\PaymentController::class, 'index'])->name('payments');
    Route::post('/payments', [Manager\PaymentController::class, 'store'])->name('payments.store');
    Route::patch('/payments/{payment}/overdue', [Manager\PaymentController::class, 'markOverdue'])->name('payments.markOverdue');

    Route::get('/electric-bills', [Manager\ElectricBillController::class, 'index'])->name('electric-bills');
    Route::post('/electric-bills', [Manager\ElectricBillController::class, 'store'])->name('electric-bills.store');
    Route::patch('/electric-bills/{electricBill}/overdue', [Manager\ElectricBillController::class, 'markOverdue'])->name('electric-bills.markOverdue');

    // Rental applications
    Route::get('/rental-applications', [Manager\RentalApplicationController::class, 'index'])->name('rental-applications');
    Route::patch('/rental-applications/{application}/approve', [Manager\RentalApplicationController::class, 'approve'])->name('rental-applications.approve');
    Route::patch('/rental-applications/{application}/reject', [Manager\RentalApplicationController::class, 'reject'])->name('rental-applications.reject');

    // Leases
    Route::get('/contracts', [Manager\ContractController::class, 'index'])->name('contracts.index');
    Route::post('/contracts/{contract}/renew', [Manager\ContractController::class, 'renew'])->name('contracts.renew');
    Route::post('/contracts/{contract}/terminate', [Manager\ContractController::class, 'terminate'])->name('contracts.terminate');
    Route::get('/contracts/{contract}/pdf', [Manager\ContractController::class, 'pdf'])->name('contracts.pdf');

    // Maintenance inbox
    Route::get('/inbox', [Manager\MaintenanceController::class, 'index'])->name('inbox');
    Route::patch('/maintenance/{maintenanceRequest}/status', [Manager\MaintenanceController::class, 'updateStatus'])->name('maintenance.updateStatus');
});

// ── Tenant ──
Route::middleware(['auth', 'verified', 'role:tenant', 'throttle:writes'])->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/dashboard', [Tenant\DashboardController::class, 'index'])->name('dashboard');

    // Supply catalog & orders
    Route::get('/products', [Tenant\OrderController::class, 'catalog'])->name('products');
    Route::get('/orders', [Tenant\OrderController::class, 'index'])->name('orders');
    Route::post('/orders', [Tenant\OrderController::class, 'store'])->name('orders.store');
    Route::patch('/orders/{order}/cancel', [Tenant\OrderController::class, 'cancel'])->name('orders.cancel');

    // Bills (paying: see billing.* routes)
    Route::get('/bills', [Tenant\BillController::class, 'index'])->name('bills');
    Route::get('/electric-bills', [Tenant\BillController::class, 'electricBills'])->name('electric-bills');

    // Maintenance
    Route::get('/maintenance', [Tenant\MaintenanceController::class, 'index'])->name('maintenance');
    Route::post('/maintenance', [Tenant\MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::get('/maintenance/{maintenanceRequest}', [Tenant\MaintenanceController::class, 'show'])->name('maintenance.detail');

    // Contracts
    Route::get('/contracts', [Tenant\ContractController::class, 'index'])->name('contracts');
    Route::get('/contracts/{contract}', [Tenant\ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/sign', [Tenant\ContractController::class, 'sign'])->name('contracts.sign');
    Route::get('/contracts/{contract}/pdf', [Tenant\ContractController::class, 'pdf'])->name('contracts.pdf');
    Route::post('/contracts/{contract}/renewal', [Tenant\ContractController::class, 'requestRenewal'])->name('contracts.requestRenewal');
});

// ── Billing actions shared by rent bills, electric bills and supply orders (authorized by policies) ──
Route::middleware(['auth', 'verified'])->prefix('billing/{type}/{id}')->name('billing.')
    ->whereIn('type', array_keys(PayableResolver::TYPES))->whereNumber('id')
    ->group(function () {
        Route::patch('/submit', [PaymentFlowController::class, 'submit'])->middleware('throttle:payments')->name('submit');
        Route::post('/checkout', [PaymentFlowController::class, 'checkout'])->name('checkout');
        Route::get('/checkout/return', [PaymentFlowController::class, 'checkoutReturn'])->name('checkout.return');
        Route::patch('/verify', [PaymentFlowController::class, 'verify'])->name('verify');
        Route::patch('/reject', [PaymentFlowController::class, 'reject'])->name('reject');
        Route::patch('/record-cash', [PaymentFlowController::class, 'recordCash'])->name('recordCash');
        Route::get('/proof', [PaymentFlowController::class, 'proof'])->name('proof');
    });

Route::middleware(['auth', 'verified'])->get('/receipts/{type}/{id}', [ReceiptController::class, 'show'])
    ->whereIn('type', array_keys(PayableResolver::TYPES))->whereNumber('id')
    ->name('receipts.show');

// ── Messaging & notifications (all signed-in users) ──
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/{user}', [MessageController::class, 'conversation'])->name('conversation');
        Route::post('/', [MessageController::class, 'send'])->middleware('throttle:messages')->name('send');
    });
    Route::get('/api/messages/{user}', [MessageController::class, 'apiMessages'])->name('api.messages');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::get('/api/notifications', [NotificationController::class, 'apiUnread'])->name('api.notifications');
});

// ── Profile (verification not required, so users can fix their email) ──
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'disable'])->name('profile.disable');
});

require __DIR__.'/auth.php';
