<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_web_response_has_a_correlation_id(): void
    {
        $this->get('/')->assertOk()->assertHeader('X-Request-ID');
        $this->withHeader('X-Request-ID', 'known-request-id')->get('/')
            ->assertHeader('X-Request-ID', 'known-request-id');
    }

    public function test_audit_entries_capture_request_context_without_secrets(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)->withHeaders([
            'X-Request-ID' => '7906b270-e6b2-4bea-8492-0dba953196dd',
            'User-Agent' => 'BackendHardeningTest/1.0',
        ])->post('/manager/locations', ['name' => 'Audited Location'])->assertRedirect();

        $log = ActivityLog::latest()->firstOrFail();
        $this->assertSame('7906b270-e6b2-4bea-8492-0dba953196dd', $log->request_id);
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertSame('BackendHardeningTest/1.0', $log->user_agent);
    }

    public function test_retention_is_preview_only_unless_execute_is_requested(): void
    {
        $users = User::factory()->count(2)->create();
        $message = Message::create([
            'sender_id' => $users[0]->id,
            'receiver_id' => $users[1]->id,
            'message' => 'old',
        ]);
        $message->forceFill(['created_at' => now()->subDays(400)])->save();
        config(['retention.message_days' => 365]);

        $this->artisan('data:prune')->assertSuccessful();
        $this->assertDatabaseHas('messages', ['id' => $message->id]);

        $this->artisan('data:prune', ['--execute' => true])->assertSuccessful();
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }
}
