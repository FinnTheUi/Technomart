<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_admin_profile_has_social_style_photo_controls(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/profile')
            ->assertOk()
            ->assertSee('social-profile-card', false)
            ->assertSee('Change profile photo')
            ->assertSee('profile_photo');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@gmail.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@gmail.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_admin_can_upload_a_sanitized_profile_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->patch('/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'profile_photo' => UploadedFile::fake()->image('avatar.png', 300, 300),
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $path = $admin->refresh()->avatar_url;
        $this->assertNotNull($path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_non_admin_cannot_upload_a_profile_photo(): void
    {
        Storage::fake('public');
        $tenant = User::factory()->create(['role' => User::ROLE_TENANT]);

        $this->actingAs($tenant)->patch('/profile', [
            'name' => $tenant->name,
            'email' => $tenant->email,
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertSessionHasErrors('profile_photo');

        $this->assertNull($tenant->refresh()->avatar_url);
    }

    public function test_admin_profile_photo_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->patch('/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'profile_photo' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('profile_photo');

        $this->assertNull($admin->refresh()->avatar_url);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        // ProfileController::disable() sets is_active = false rather than deleting
        $this->assertNotNull($user->fresh());
        $this->assertFalse((bool) $user->fresh()->is_active);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
