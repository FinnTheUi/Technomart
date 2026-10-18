<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Contract;
use App\Models\ElectricBill;
use App\Models\MaintenanceRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: every page renders for its role with data in every status.
 */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $tenant;

    private Contract $contract;

    private MaintenanceRequest $maintenance;

    private RentalApplication $application;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ml.python' => 'python-binary-that-does-not-exist']);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->tenant = User::factory()->create(['role' => 'tenant']);

        $stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'Stall A', 'price' => 3000, 'status' => 'occupied', 'floor_number' => 1, 'stall_number' => 1]);
        Stall::create(['manager_id' => $this->manager->id, 'name' => 'Stall B', 'price' => 3000, 'status' => 'available', 'floor_number' => 2, 'stall_number' => 2]);

        foreach (PaymentStatus::cases() as $i => $status) {
            Payment::create([
                'tenant_id' => $this->tenant->id, 'manager_id' => $this->manager->id, 'stall_id' => $stall->id,
                'amount' => 3000, 'late_fee' => $status === PaymentStatus::Overdue ? 150 : 0,
                'due_date' => today()->subMonths($i), 'status' => $status,
                'paid_date' => $status === PaymentStatus::Paid ? now() : null,
                'receipt_number' => $status === PaymentStatus::Paid ? 'OR-2026-00000'.$i : null,
                'payment_method' => $status === PaymentStatus::Paid ? 'cash' : null,
                'reference_number' => $status === PaymentStatus::ForVerification ? '1234567890123' : null,
            ]);
            ElectricBill::create([
                'tenant_id' => $this->tenant->id, 'stall_id' => $stall->id, 'manager_id' => $this->manager->id,
                'period_from' => today()->subMonth(), 'period_to' => today(), 'billing_date' => today(),
                'reading_present' => 200, 'reading_previous' => 100, 'kwh_used' => 100, 'rate_per_kwh' => 10,
                'total_amount' => 1000, 'due_date' => today(), 'status' => $status,
            ]);
        }

        $product = Product::create(['name' => 'Cola', 'price' => 20, 'stock' => 5, 'reorder_level' => 10, 'is_active' => true]);
        foreach (OrderStatus::cases() as $status) {
            Order::create(['user_id' => $this->tenant->id, 'product_id' => $product->id, 'quantity' => 1, 'total_price' => 20, 'status' => $status]);
        }

        foreach (ContractStatus::cases() as $status) {
            $contract = Contract::create(['tenant_id' => $this->tenant->id, 'stall_id' => $stall->id, 'start_date' => today()->subMonth(), 'end_date' => today()->addMonth(), 'status' => $status]);
        }
        $this->contract = Contract::where('status', ContractStatus::Active)->first();
        $this->contract->update(['renewal_requested_at' => now(), 'renewal_months_requested' => 3]);

        foreach (MaintenanceStatus::cases() as $status) {
            $this->maintenance = MaintenanceRequest::create(['tenant_id' => $this->tenant->id, 'stall_id' => $stall->id, 'subject' => 'Leak', 'description' => 'Leak', 'priority' => 'high', 'status' => $status]);
        }

        $this->application = RentalApplication::create([
            'stall_id' => $stall->id, 'name' => 'Applicant', 'email' => 'applicant@gmail.com', 'password_hash' => bcrypt('x'),
            'phone' => '09171234567', 'address' => 'Street', 'rental_duration' => 1,
            'payment_method' => 'gcash', 'payment_status' => 'for_verification', 'gcash_reference' => '1234567890123',
        ]);
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/stalls', '/login', '/privacy', '/terms'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get($this->application->signedUrl('rental.success'))->assertOk()->assertSee('Payment Submitted!');
    }

    public function test_tenant_pages_render(): void
    {
        foreach (['/tenant/dashboard', '/tenant/products', '/tenant/orders', '/tenant/bills', '/tenant/electric-bills',
            '/tenant/maintenance', "/tenant/maintenance/{$this->maintenance->id}", '/tenant/contracts',
            "/tenant/contracts/{$this->contract->id}", "/tenant/contracts/{$this->contract->id}/pdf",
            '/messages', '/notifications', '/profile'] as $url) {
            $this->actingAs($this->tenant)->get($url)->assertOk();
        }

        $this->actingAs($this->tenant)->get('/tenant/bills')
            ->assertSee('Awaiting verification')
            ->assertSee('Submit for verification')
            ->assertSee('incl. ₱150.00 late fee', false);
    }

    public function test_manager_pages_render(): void
    {
        foreach (['/manager/dashboard', '/manager/stalls', '/manager/floor-plan', '/manager/payments',
            '/manager/electric-bills', '/manager/rental-applications', '/manager/contracts', '/manager/inbox'] as $url) {
            $this->actingAs($this->manager)->get($url)->assertOk();
        }

        $this->actingAs($this->manager)->get('/manager/payments')->assertSee('Verify')->assertSee('Record cash');
        $this->actingAs($this->manager)->get('/manager/rental-applications')->assertSee('1234567890123');
        $this->actingAs($this->manager)->get('/manager/contracts')->assertSee('Renewal requested: 3 mo.');
    }

    public function test_admin_pages_render(): void
    {
        foreach (['/admin/dashboard', '/admin/users', '/admin/products', '/admin/products/create', '/admin/orders',
            '/admin/inventory', '/admin/suppliers', '/admin/purchase-orders', '/admin/audit-log', '/admin/locations'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }
}
