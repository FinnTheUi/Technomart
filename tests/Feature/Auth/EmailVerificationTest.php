<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_link_from_gmail_works_in_a_browser_with_no_login_session(): void
    {
        Event::fake();
        $user = User::factory()->unverified()->create(['role' => 'tenant']);
        $notification = new \Illuminate\Auth\Notifications\VerifyEmail;
        $link = $notification->toMail($user)->actionUrl;

        $this->get($link)->assertOk()->assertSee('Email verified')->assertSee('Sign in');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();
        Event::assertDispatchedTimes(Verified::class, 1);
    }

    public function test_link_verifies_its_recipient_without_switching_the_signed_in_account(): void
    {
        $recipient = User::factory()->unverified()->create(['role' => 'tenant']);
        $other = User::factory()->create(['role' => 'manager']);
        $link = (new \Illuminate\Auth\Notifications\VerifyEmail)->toMail($recipient)->actionUrl;

        $this->actingAs($other)->get($link)->assertOk()->assertSee('Email verified')->assertSee('different account');
        $this->assertTrue($recipient->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($other);
    }

    public function test_expired_link_shows_recovery_without_verifying_the_email(): void
    {
        $user = User::factory()->unverified()->create();
        $link = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->get($link)->assertOk()->assertSee('Verification link unavailable')->assertSee('request a new link');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function test_tampered_link_cannot_verify_any_account(): void
    {
        $user = User::factory()->unverified()->create();
        $link = (new \Illuminate\Auth\Notifications\VerifyEmail)->toMail($user)->actionUrl;
        $link = preg_replace('/signature=[a-f0-9]+/', 'signature=invalid', $link);

        $this->get($link)->assertOk()->assertSee('Verification link unavailable');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_link_for_previous_email_cannot_verify_a_changed_address(): void
    {
        $user = User::factory()->unverified()->create();
        $link = (new \Illuminate\Auth\Notifications\VerifyEmail)->toMail($user)->actionUrl;
        $user->update(['email' => 'changed@gmail.com']);

        $this->get($link)->assertOk()->assertSee('Verification link unavailable');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_reopening_a_valid_link_is_safe_and_does_not_duplicate_the_event(): void
    {
        Event::fake();
        $user = User::factory()->unverified()->create();
        $link = (new \Illuminate\Auth\Notifications\VerifyEmail)->toMail($user)->actionUrl;

        $this->get($link)->assertOk()->assertSee('Email verified');
        $this->get($link)->assertOk()->assertSee('Email verified');
        Event::assertDispatchedTimes(Verified::class, 1);
        $this->assertGuest();
    }

    public function test_valid_link_does_not_reactivate_a_disabled_account(): void
    {
        $user = User::factory()->unverified()->create(['is_active' => false]);
        $link = (new \Illuminate\Auth\Notifications\VerifyEmail)->toMail($user)->actionUrl;

        $this->get($link)->assertOk()->assertSee('Verification link unavailable');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_resending_from_an_expired_link_returns_to_the_verification_screen(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = User::factory()->unverified()->create(['role' => 'tenant']);
        $link = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($link)->assertOk()->assertSee('Send a fresh verification email');
        $this->from($link)->post('/email/verification-notification')
            ->assertRedirect(route('verification.notice'))->assertSessionHas('status', 'verification-link-sent');
        \Illuminate\Support\Facades\Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }
}
