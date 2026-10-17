<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use App\Models\Waitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StallRentalFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Stall $availableStall;

    private Stall $occupiedStall;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->availableStall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Available Stall',
            'price' => 5000,
            'status' => 'available',
            'floor_number' => 1,
            'stall_number' => 1,
        ]);
        $this->occupiedStall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Occupied Stall',
            'price' => 7000,
            'status' => 'occupied',
            'floor_number' => 1,
            'stall_number' => 2,
        ]);
    }

    public function test_visitor_can_view_stall_listing(): void
    {
        $response = $this->get('/stalls');

        $response->assertStatus(200);
    }

    public function test_visitor_can_apply_for_an_available_stall(): void
    {
        $response = $this->post("/stalls/{$this->availableStall->id}/apply", [
            'name' => 'Alice Smith',
            'email' => 'alice@gmail.com',
            'phone' => '09171234567',
            'address' => '123 Main Street',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'rental_duration' => 6,
            'terms' => true,
        ]);

        $this->assertDatabaseHas('rental_applications', [
            'stall_id' => $this->availableStall->id,
            'name' => 'Alice Smith',
            'email' => 'alice@gmail.com',
            'status' => 'pending_approval',
        ]);

        $application = RentalApplication::where('email', 'alice@gmail.com')->first();
        $response->assertRedirectContains("/rental/{$application->id}/checkout?expires=");
        $this->get($response->headers->get('Location'))->assertOk();
    }

    public function test_visitor_cannot_apply_for_an_occupied_stall(): void
    {
        $response = $this->get("/stalls/{$this->occupiedStall->id}/apply");

        $response->assertRedirect(route('stalls.index'));
        $response->assertSessionHas('error', 'This stall is no longer available.');
    }

    public function test_application_with_missing_fields_fails_validation(): void
    {
        $response = $this->post("/stalls/{$this->availableStall->id}/apply", [
            'name' => 'Incomplete',
        ]);

        $response->assertSessionHasErrors(['email', 'phone', 'address', 'password', 'rental_duration', 'terms']);
    }

    public function test_visitor_can_pay_online_gcash(): void
    {
        $application = RentalApplication::create([
            'stall_id' => $this->availableStall->id,
            'name' => 'Alice',
            'email' => 'alice@gmail.com',
            'password_hash' => bcrypt('password123'),
            'phone' => '09171234567',
            'address' => '123 Street',
            'rental_duration' => 3,
            'payment_status' => 'pending',
            'status' => 'pending_approval',
        ]);

        $response = $this->post($application->signedUrl('rental.payOnline'), ['reference_number' => '9876543210123']);

        $response->assertRedirectContains("/rental/{$application->id}/success?expires=");
        $application->refresh();
        $this->assertEquals('gcash', $application->payment_method);
        // The reference is checked by the manager on approval; it is not trusted as paid.
        $this->assertSame(PaymentStatus::ForVerification, $application->payment_status);
        $this->assertSame('9876543210123', $application->gcash_reference);
    }

    public function test_visitor_can_pay_in_person(): void
    {
        $application = RentalApplication::create([
            'stall_id' => $this->availableStall->id,
            'name' => 'Bob',
            'email' => 'bob@gmail.com',
            'password_hash' => bcrypt('password123'),
            'phone' => '09181234567',
            'address' => '456 Avenue',
            'rental_duration' => 1,
            'payment_status' => 'pending',
            'status' => 'pending_approval',
        ]);

        $response = $this->post($application->signedUrl('rental.payInPerson'));

        $response->assertRedirectContains("/rental/{$application->id}/success?expires=");
        $application->refresh();
        $this->assertEquals('in_person', $application->payment_method);
        $this->assertSame(PaymentStatus::Pending, $application->payment_status);
    }

    public function test_visitor_can_join_waitlist(): void
    {
        $response = $this->post("/stalls/{$this->occupiedStall->id}/waitlist", [
            'name' => 'Charlie',
            'email' => 'charlie@gmail.com',
            'phone' => '09191234567',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('waitlists', [
            'stall_id' => $this->occupiedStall->id,
            'email' => 'charlie@gmail.com',
            'status' => 'waiting',
        ]);
    }

    public function test_duplicate_waitlist_entry_is_rejected(): void
    {
        Waitlist::create([
            'stall_id' => $this->occupiedStall->id,
            'name' => 'Charlie',
            'email' => 'charlie@gmail.com',
            'phone' => '09191234567',
            'status' => 'waiting',
        ]);

        $response = $this->post("/stalls/{$this->occupiedStall->id}/waitlist", [
            'name' => 'Charlie',
            'email' => 'charlie@gmail.com',
            'phone' => '09191234567',
        ]);

        $response->assertSessionHas('error', 'You are already on the waitlist for this stall.');
        $this->assertEquals(1, Waitlist::where('email', 'charlie@gmail.com')->count());
    }

    private function pendingApplication(array $overrides = []): RentalApplication
    {
        return RentalApplication::create(array_merge([
            'stall_id' => $this->availableStall->id,
            'name' => 'Dana',
            'email' => 'dana@gmail.com',
            'password_hash' => bcrypt('password123'),
            'phone' => '09171234567',
            'address' => '789 Road',
            'rental_duration' => 2,
            'payment_status' => 'pending',
            'status' => 'pending_approval',
        ], $overrides));
    }

    public function test_rental_pages_reject_unsigned_urls(): void
    {
        $application = $this->pendingApplication();

        $this->get("/rental/{$application->id}/checkout")->assertForbidden();
        $this->get("/rental/{$application->id}/success")->assertForbidden();
        $this->post("/rental/{$application->id}/pay-online")->assertForbidden();
        $this->post("/rental/{$application->id}/pay-in-person")->assertForbidden();

        $this->assertNull($application->fresh()->payment_method);
    }

    public function test_signed_url_for_one_application_cannot_be_reused_for_another(): void
    {
        $mine = $this->pendingApplication();
        $theirs = $this->pendingApplication(['email' => 'other@gmail.com']);

        $tampered = str_replace("/rental/{$mine->id}/", "/rental/{$theirs->id}/", $mine->signedUrl('rental.checkout'));

        $this->get($tampered)->assertForbidden();
    }

    public function test_payment_cannot_be_changed_after_it_was_chosen(): void
    {
        $application = $this->pendingApplication();

        $this->post($application->signedUrl('rental.payOnline'), ['reference_number' => '1111222233334']);
        $reference = $application->fresh()->gcash_reference;

        $this->post($application->signedUrl('rental.payInPerson'));
        $this->post($application->signedUrl('rental.payOnline'), ['reference_number' => '5555666677778']);

        $application->refresh();
        $this->assertSame('gcash', $application->payment_method);
        $this->assertSame($reference, $application->gcash_reference);
    }

    public function test_cannot_submit_application_for_occupied_stall(): void
    {
        $this->post("/stalls/{$this->occupiedStall->id}/apply", [
            'name' => 'Eve',
            'email' => 'eve@gmail.com',
            'phone' => '09171234567',
            'address' => '1 Street',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'rental_duration' => 1,
            'terms' => true,
        ])->assertRedirect(route('stalls.index'));

        $this->assertDatabaseMissing('rental_applications', ['email' => 'eve@gmail.com']);
    }
}
