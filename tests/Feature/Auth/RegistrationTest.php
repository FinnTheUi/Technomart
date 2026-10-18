<?php

namespace Tests\Feature\Auth;

use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_starts_with_stall_selection(): void
    {
        $this->get('/register')
            ->assertRedirect(route('stalls.index'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Choose an available stall'));
    }

    public function test_direct_registration_post_cannot_create_a_stall_less_account(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_consent' => '1',
        ])->assertRedirect(route('stalls.index'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@gmail.com']);
    }

    public function test_stall_application_page_contains_account_fields(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'Stall 1', 'price' => 1000, 'status' => 'available']);

        $this->get(route('stalls.apply', $stall))
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }
}
