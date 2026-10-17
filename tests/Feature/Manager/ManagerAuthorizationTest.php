<?php

namespace Tests\Feature\Manager;

use App\Enums\ApplicationStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\PaymentStatus;
use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManagerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $otherManager;

    private User $tenant;

    private Stall $stall;

    private Stall $otherStall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->otherManager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $this->stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'Mine', 'price' => 5000, 'status' => 'available']);
        $this->otherStall = Stall::create(['manager_id' => $this->otherManager->id, 'name' => 'Theirs', 'price' => 5000, 'status' => 'occupied']);
    }

    private function application(array $overrides = []): RentalApplication
    {
        return RentalApplication::create(array_merge([
            'stall_id' => $this->stall->id,
            'name' => 'Applicant',
            'email' => 'applicant@gmail.com',
            'password_hash' => bcrypt('password123'),
            'phone' => '09171234567',
            'address' => '1 Street',
            'rental_duration' => 2,
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
            'status' => 'pending_approval',
        ], $overrides));
    }

    public function test_application_cannot_be_approved_twice(): void
    {
        Mail::fake();
        $application = $this->application();

        $this->actingAs($this->manager)->patch("/manager/rental-applications/{$application->id}/approve")->assertSessionHas('success');
        $this->actingAs($this->manager)->patch("/manager/rental-applications/{$application->id}/approve")->assertSessionHas('error');

        $this->assertSame(1, Contract::count());
        $this->assertSame(2, Payment::count());
    }

    public function test_application_for_occupied_stall_cannot_be_approved(): void
    {
        Mail::fake();
        $this->stall->update(['status' => 'occupied']);
        $application = $this->application();

        $this->actingAs($this->manager)
            ->patch("/manager/rental-applications/{$application->id}/approve")
            ->assertSessionHas('error', 'This stall is no longer available.');

        $this->assertSame(ApplicationStatus::PendingApproval, $application->fresh()->status);
        $this->assertSame(0, Contract::count());
    }

    public function test_approval_does_not_convert_a_staff_account_into_a_tenant(): void
    {
        Mail::fake();
        $application = $this->application(['email' => $this->otherManager->email]);

        $this->actingAs($this->manager)
            ->patch("/manager/rental-applications/{$application->id}/approve")
            ->assertSessionHas('error');

        $this->assertSame('manager', $this->otherManager->fresh()->role);
        $this->assertSame(0, Contract::count());
    }

    public function test_approval_sets_first_gcash_month_as_paid(): void
    {
        Mail::fake();
        $application = $this->application();

        $this->actingAs($this->manager)->patch("/manager/rental-applications/{$application->id}/approve");

        $this->assertSame([PaymentStatus::Paid, PaymentStatus::Pending], Payment::orderBy('due_date')->get()->pluck('status')->all());
        $this->assertNotNull(Payment::orderBy('due_date')->first()->receipt_number);
    }

    public function test_manager_cannot_update_maintenance_on_another_managers_stall(): void
    {
        $request = MaintenanceRequest::create([
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->otherStall->id,
            'subject' => 'Leak',
            'description' => 'Leaking',
            'priority' => 'high',
            'status' => 'pending',
        ]);

        $this->actingAs($this->manager)
            ->patch("/manager/maintenance/{$request->id}/status", ['status' => 'resolved'])
            ->assertForbidden();

        $this->assertSame(MaintenanceStatus::Pending, $request->fresh()->status);
    }

    public function test_manager_cannot_bill_for_another_managers_stall(): void
    {
        $this->actingAs($this->manager)->post('/manager/payments', [
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->otherStall->id,
            'amount' => 1000,
            'due_date' => now()->addWeek()->toDateString(),
        ])->assertSessionHasErrors('stall_id');

        $this->assertSame(0, Payment::count());
    }

    public function test_manager_cannot_bill_a_non_tenant(): void
    {
        $this->actingAs($this->manager)->post('/manager/payments', [
            'tenant_id' => $this->otherManager->id,
            'stall_id' => $this->stall->id,
            'amount' => 1000,
            'due_date' => now()->addWeek()->toDateString(),
        ])->assertSessionHasErrors('tenant_id');
    }

    public function test_manager_cannot_view_contract_pdf_for_another_managers_stall(): void
    {
        $contract = Contract::create([
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->otherStall->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'terms' => 'Terms',
            'status' => 'active',
        ]);

        // The PDF route lives in the tenant group, so managers are already blocked by role middleware;
        // the controller check is defence in depth.
        $this->actingAs($this->manager)->get("/tenant/contracts/{$contract->id}/pdf")->assertForbidden();
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch("/admin/users/{$admin->id}/role", ['role' => 'tenant'])
            ->assertSessionHas('error');

        $this->assertSame('admin', $admin->fresh()->role);
    }
}
