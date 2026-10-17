<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\GoogleOAuthCredentials;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret_encrypted' => Crypt::encryptString('test-client-secret'),
            'services.google.allowed_domains' => ['gmail.com'],
        ]);
    }

    private function fakeGoogleUser(array $overrides = []): void
    {
        $data = array_merge([
            'id' => 'google-123',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.delacruz@gmail.com',
            'email_verified' => true,
            'picture' => 'https://example.com/avatar.png',
        ], $overrides);

        $user = (new SocialiteUser)->setRaw($data)->map([
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => $data['picture'],
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('buildProvider')
            ->withArgs(fn ($class, $config) => $class === GoogleProvider::class
                && $config['client_secret'] === 'test-client-secret')
            ->andReturn($provider);
    }

    public function test_client_secret_is_decrypted_only_from_the_encrypted_value(): void
    {
        $credentials = app(GoogleOAuthCredentials::class);

        $this->assertTrue($credentials->isConfigured());
        $this->assertSame('test-client-secret', $credentials->clientSecret());
        $this->assertNotSame('test-client-secret', config('services.google.client_secret_encrypted'));
    }

    public function test_google_button_is_hidden_when_not_configured(): void
    {
        config(['services.google.client_secret_encrypted' => null]);

        $this->get('/register')->assertOk()->assertDontSee('Sign up with Google');
        $this->get('/auth/google/redirect')->assertRedirect(route('login'));
    }

    public function test_google_button_is_shown_when_configured(): void
    {
        $this->get('/register')->assertOk()->assertSee('Sign up with Google');
        $this->get('/login')->assertOk()->assertSee('Sign in with Google');
    }

    public function test_redirect_sends_user_to_google(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $response->headers->get('Location'));
        $this->assertStringContainsString('client_id=test-client-id', $response->headers->get('Location'));
        $this->assertStringNotContainsString('test-client-secret', $response->headers->get('Location'));
    }

    public function test_new_google_user_is_registered_as_verified_tenant(): void
    {
        $this->fakeGoogleUser();

        $this->get('/auth/google/callback?code=abc&state=xyz')
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'juan.delacruz@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame(User::ROLE_TENANT, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->privacy_consented_at);
        $this->assertTrue($user->is_active);
    }

    public function test_existing_email_account_is_linked_instead_of_duplicated(): void
    {
        $existing = User::factory()->create(['email' => 'juan.delacruz@gmail.com', 'role' => 'manager', 'is_active' => true]);
        $this->fakeGoogleUser();

        $this->get('/auth/google/callback?code=abc&state=xyz');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::where('email', 'juan.delacruz@gmail.com')->count());
        $this->assertSame('google-123', $existing->fresh()->google_id);
        $this->assertSame('manager', $existing->fresh()->role);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogleUser(['email_verified' => false]);

        $this->get('/auth/google/callback?code=abc&state=xyz')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_non_gmail_domain_is_rejected(): void
    {
        $this->fakeGoogleUser(['email' => 'someone@company.com']);

        $this->get('/auth/google/callback?code=abc&state=xyz')->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_sign_in_with_google(): void
    {
        User::factory()->create(['email' => 'juan.delacruz@gmail.com', 'google_id' => null, 'is_active' => false]);
        $this->fakeGoogleUser();

        $this->get('/auth/google/callback?code=abc&state=xyz')->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_cancelled_consent_returns_to_login(): void
    {
        $this->get('/auth/google/callback?error=access_denied')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_encrypt_secret_command_prints_decryptable_value(): void
    {
        $this->artisan('google:encrypt-secret', ['--show' => true])
            ->expectsQuestion('Google OAuth client secret (input hidden)', 'my-real-secret')
            ->expectsOutputToContain('GOOGLE_CLIENT_SECRET_ENCRYPTED=')
            ->assertSuccessful();
    }

    public function test_decryption_failure_does_not_leak_secret(): void
    {
        config(['services.google.client_secret_encrypted' => 'not-a-valid-payload']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to decrypt');

        app(GoogleOAuthCredentials::class)->clientSecret();
    }
}
