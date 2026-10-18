<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\SafeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

/**
 * A mail-server failure (e.g. Gmail rejecting the SMTP password) must never show an error page.
 */
class MailFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::extend('rejecting', fn () => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('535-5.7.8 Username and Password not accepted.');
            }

            public function __toString(): string
            {
                return 'rejecting';
            }
        });
        config(['mail.mailers.rejecting' => ['transport' => 'rejecting'], 'mail.default' => 'rejecting']);
    }

    public function test_resend_verification_shows_a_friendly_error(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->from('/verify-email')->post('/email/verification-notification')
            ->assertRedirect('/verify-email')
            ->assertSessionHasErrors(['mail' => SafeMail::FAILURE_MESSAGE]);

        $this->actingAs($user)->get('/verify-email')
            ->assertOk()->assertSee('Resend verification email');
    }

    public function test_email_correction_survives_a_mail_failure(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'wrong@gmail.com']);

        $this->actingAs($user)->from('/verify-email/account')->patch('/verify-email/account', [
            'name' => $user->name,
            'email' => 'new.person@gmail.com',
            'phone_number' => $user->phone_number,
            'address' => $user->address,
        ])->assertRedirect('/verify-email')->assertSessionHasErrors('mail');

        $this->assertAuthenticated();
        $this->assertSame('new.person@gmail.com', $user->fresh()->email);
    }

    public function test_forgot_password_shows_a_friendly_error(): void
    {
        User::factory()->create(['email' => 'someone@gmail.com']);

        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'someone@gmail.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors(['email' => SafeMail::FAILURE_MESSAGE]);
    }

    public function test_admin_can_still_create_a_manager(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/users/manager', [
            'name' => 'Manny', 'email' => 'manny@gmail.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHas('success', fn ($m) => str_contains($m, 'could not be sent'));

        $this->assertDatabaseHas('users', ['email' => 'manny@gmail.com', 'role' => 'manager']);
    }
}
