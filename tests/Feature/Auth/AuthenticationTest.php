<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────
    //  LOGIN
    // ──────────────────────────────────────────

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'role' => 'tenant',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'role' => 'tenant',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@gmail.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // ──────────────────────────────────────────
    //  REGISTRATION
    // ──────────────────────────────────────────

    public function test_direct_registration_redirects_to_stall_selection(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'privacy_consent' => '1',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@gmail.com']);
        $response->assertRedirect(route('stalls.index'));
    }

    public function test_direct_registration_never_changes_an_existing_account(): void
    {
        User::factory()->create(['email' => 'taken@gmail.com']);

        $response = $this->post('/register', [
            'name' => 'Duplicate',
            'email' => 'taken@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('stalls.index'));
        $this->assertSame(1, User::where('email', 'taken@gmail.com')->count());
    }

    public function test_direct_registration_does_not_create_weak_password_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'Weak Pass User',
            'email' => 'weak@gmail.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('stalls.index'));
        $this->assertDatabaseMissing('users', ['email' => 'weak@gmail.com']);
    }

    // ──────────────────────────────────────────
    //  LOGOUT
    // ──────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
