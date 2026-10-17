<?php

namespace Tests\Feature\Auth;

use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_users_are_sent_to_the_verification_notice(): void
    {
        foreach (['tenant' => '/tenant/dashboard', 'manager' => '/manager/dashboard', 'admin' => '/admin/dashboard'] as $role => $url) {
            $user = User::factory()->unverified()->create(['role' => $role]);

            $this->actingAs($user)->get($url)->assertRedirect(route('verification.notice'));
        }
    }

    public function test_unverified_users_can_still_fix_their_profile(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'tenant']);

        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_registration_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'New User',
            'email' => 'new.user@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_consent' => '1',
        ]);

        Notification::assertSentTo(User::where('email', 'new.user@gmail.com')->first(), VerifyEmail::class);
    }

    public function test_tenant_created_on_approval_must_verify_their_email(): void
    {
        Notification::fake();
        Mail::fake();

        $manager = User::factory()->create(['role' => 'manager']);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'A', 'price' => 1000, 'status' => 'available']);
        $application = RentalApplication::create([
            'stall_id' => $stall->id, 'name' => 'Applicant', 'email' => 'applicant@gmail.com', 'password_hash' => bcrypt('x'),
            'phone' => '09171234567', 'address' => 'Street', 'rental_duration' => 1,
            'payment_method' => 'in_person', 'payment_status' => 'pending',
        ]);

        $this->actingAs($manager)->patch("/manager/rental-applications/{$application->id}/approve")->assertSessionHas('success');

        $tenant = User::where('email', 'applicant@gmail.com')->firstOrFail();
        $this->assertFalse($tenant->hasVerifiedEmail());
        Notification::assertSentTo($tenant, VerifyEmail::class);
    }

    public function test_manager_created_by_admin_receives_a_verification_email(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/users/manager', [
            'name' => 'Manny', 'email' => 'manny@gmail.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        Notification::assertSentTo(User::where('email', 'manny@gmail.com')->first(), VerifyEmail::class);
    }
}
