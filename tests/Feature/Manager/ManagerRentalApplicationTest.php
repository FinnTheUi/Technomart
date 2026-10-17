<?php

namespace Tests\Feature\Manager;

use App\Models\Payment;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManagerRentalApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Stall 1',
            'price' => 5000,
            'status' => 'available',
        ]);
    }

    public function test_manager_can_approve_a_rental_application(): void
    {
        $application = RentalApplication::create([
            'stall_id' => $this->stall->id,
            'name' => 'John Doe',
            'email' => 'john@gmail.com',
            'password_hash' => Hash::make('password123'),
            'phone' => '09171234567',
            'address' => '123 Street',
            'rental_duration' => 3,
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
            'status' => 'pending_approval',
            'gcash_reference' => 'GCASH-TEST1234',
        ]);

        $response = $this->actingAs($this->manager)
            ->patch("/manager/rental-applications/{$application->id}/approve");

        $response->assertSessionHas('success');

        // Application is approved
        $this->assertDatabaseHas('rental_applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);

        // Tenant user account was created
        $this->assertDatabaseHas('users', [
            'email' => 'john@gmail.com',
            'role' => 'tenant',
        ]);

        // Contract was created
        $this->assertDatabaseHas('contracts', [
            'stall_id' => $this->stall->id,
            'status' => 'active',
        ]);

        // Stall is now occupied
        $this->assertDatabaseHas('stalls', [
            'id' => $this->stall->id,
            'status' => 'occupied',
        ]);

        // Monthly payments were generated (3 months)
        $tenantUser = User::where('email', 'john@gmail.com')->first();
        $this->assertEquals(3, Payment::where('tenant_id', $tenantUser->id)->count());
    }

    public function test_manager_can_reject_a_rental_application(): void
    {
        $application = RentalApplication::create([
            'stall_id' => $this->stall->id,
            'name' => 'Jane Doe',
            'email' => 'jane@gmail.com',
            'password_hash' => Hash::make('password123'),
            'phone' => '09181234567',
            'address' => '456 Avenue',
            'rental_duration' => 1,
            'payment_method' => 'in_person',
            'payment_status' => 'pending',
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($this->manager)
            ->patch("/manager/rental-applications/{$application->id}/reject");

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('rental_applications', [
            'id' => $application->id,
            'status' => 'rejected',
        ]);
    }

    public function test_manager_cannot_approve_application_for_another_managers_stall(): void
    {
        $otherManager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $otherStall = Stall::create([
            'manager_id' => $otherManager->id,
            'name' => 'Other Stall',
            'price' => 3000,
            'status' => 'available',
        ]);

        $application = RentalApplication::create([
            'stall_id' => $otherStall->id,
            'name' => 'Bob',
            'email' => 'bob@gmail.com',
            'password_hash' => Hash::make('password123'),
            'phone' => '09191234567',
            'address' => '789 Road',
            'rental_duration' => 2,
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($this->manager)
            ->patch("/manager/rental-applications/{$application->id}/approve");

        $response->assertStatus(404);
    }
}
