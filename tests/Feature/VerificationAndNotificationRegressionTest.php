<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Models\Message;
use App\Models\Product;
use App\Models\Stall;
use App\Models\User;
use App\Notifications\SystemEventNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerificationAndNotificationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_unverified_login_sends_email_without_exposing_signup_editor(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['role' => 'tenant']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('verification.notice'))->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/verify-email')->assertOk()->assertDontSee('edit your signup information');
        $this->get('/verify-email/account')->assertForbidden();
        $this->patch('/verify-email/account', ['name' => 'Changed', 'email' => 'other@gmail.com'])->assertForbidden();
        $this->assertSame($user->email, $user->fresh()->email);
        $this->post('/email/verification-notification')->assertRedirect()->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    }

    public function test_signup_marker_cannot_edit_another_account_or_a_verified_account(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'tenant']);
        $this->actingAs($user)->withSession(['pending_signup_email' => 'different@gmail.com'])
            ->get('/verify-email/account')->assertForbidden();
        $user->markEmailAsVerified();
        $this->withSession(['pending_signup_email' => $user->email])->get('/verify-email/account')->assertRedirect(route('dashboard'));
    }

    public function test_existing_account_cannot_gain_signup_edit_access_by_submitting_a_rental_application(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $existing = User::factory()->unverified()->create(['role' => 'tenant']);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'New', 'price' => 1000, 'status' => 'available']);
        $this->post('/stalls/'.$stall->id.'/apply', [
            'name' => 'Existing Tenant', 'email' => $existing->email, 'phone' => '09171234567', 'address' => 'Main Street',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'rental_duration' => 1, 'terms' => '1',
        ])->assertRedirect()->assertSessionMissing('pending_signup_email');
        $this->assertSame('application_submitted', $manager->unreadNotifications()->first()->data['type']);
        $this->actingAs($existing)->get('/verify-email/account')->assertForbidden();
    }

    public function test_messages_are_paginated_and_new_messages_create_readable_notifications(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        foreach (range(1, 55) as $number) {
            Message::create(['sender_id' => $manager->id, 'receiver_id' => $tenant->id, 'message' => 'Message '.$number]);
        }
        $this->actingAs($tenant)->get('/messages/'.$manager->id)->assertOk()
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 55 && $messages->count() === 50 && $messages->first()->message === 'Message 6');
        $this->get('/messages/'.$manager->id.'?messages_page=2')->assertOk()
            ->assertViewHas('messages', fn ($messages) => $messages->count() === 5 && $messages->first()->message === 'Message 1');
        $this->getJson('/api/messages/'.$manager->id.'?after_id=53')->assertOk()->assertJsonCount(2);
        $this->post('/messages', ['receiver_id' => $manager->id, 'message' => 'Please check.'])->assertRedirect();
        $this->assertSame(1, $manager->unreadNotifications()->count());
        $this->actingAs($manager)->getJson('/api/notifications')->assertOk()->assertJsonPath('count', 1)
            ->assertJsonPath('notifications.0.type', 'new_message')->assertJsonPath('notifications.0.url', route('messages.conversation', $tenant));
        $this->get('/messages/'.$tenant->id)->assertOk();
        $this->assertSame(0, $manager->unreadNotifications()->count());
    }

    public function test_notifications_are_paginated_and_read_actions_are_scoped_to_the_owner(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $other = User::factory()->create(['role' => 'tenant']);
        foreach (range(1, 23) as $number) {
            $tenant->notify(new SystemEventNotification('info', 'Alert '.$number, route('tenant.bills')));
        }
        $foreign = $other->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => SystemEventNotification::class, 'data' => ['message' => 'Private']]);
        $this->actingAs($tenant)->get('/notifications')->assertOk()
            ->assertViewHas('notifications', fn ($items) => $items->total() === 23 && $items->count() === 20);
        $this->get('/notifications?page=2')->assertOk()->assertViewHas('notifications', fn ($items) => $items->count() === 3);
        $this->patchJson('/notifications/'.$foreign->id.'/read')->assertNotFound();
        $this->postJson('/notifications/mark-all-read')->assertOk();
        $this->assertSame(0, $tenant->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_maintenance_alerts_reach_manager_and_tenant(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'A', 'price' => 1000, 'status' => 'occupied']);
        Contract::create(['tenant_id' => $tenant->id, 'stall_id' => $stall->id, 'start_date' => today(), 'end_date' => today()->addYear(), 'status' => 'active']);
        $this->actingAs($tenant)->post('/tenant/maintenance', ['stall_id' => $stall->id, 'subject' => 'Leak', 'description' => 'Please repair.', 'priority' => 'high'])->assertSessionHas('success');
        $this->assertSame('maintenance_submitted', $manager->unreadNotifications()->first()->data['type']);
        $request = MaintenanceRequest::firstOrFail();
        $this->actingAs($manager)->patch('/manager/maintenance/'.$request->id.'/status', ['status' => 'resolved'])->assertSessionHas('success');
        $this->assertSame('maintenance_updated', $tenant->unreadNotifications()->first()->data['type']);
    }

    public function test_contacts_and_low_stock_lists_have_independent_pagination(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        User::factory()->count(18)->create(['role' => 'tenant']);
        foreach (range(1, 12) as $number) {
            Product::create(['name' => 'Low '.$number, 'price' => 10, 'stock' => 0, 'reorder_level' => 5, 'is_active' => true]);
        }
        $this->actingAs($manager)->get('/messages')->assertOk()
            ->assertViewHas('contactableUsers', fn ($users) => $users->total() === 18 && $users->count() === 15);
        $this->get('/messages?contacts_page=2')->assertOk()->assertViewHas('contactableUsers', fn ($users) => $users->count() === 3);
        $this->get('/manager/inventory?low_stock_page=2')->assertOk()->assertViewHas('lowStock', fn ($items) => $items->total() === 12 && $items->count() === 2);
        $this->get('/manager/purchase-orders?restock_page=2')->assertOk()->assertViewHas('lowStock', fn ($items) => $items->count() === 2);
    }

    public function test_risk_pagination_does_not_drop_tenants_after_the_first_hundred(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $tenants = User::factory()->count(101)->create(['role' => 'tenant']);
        foreach ($tenants as $tenant) {
            \App\Models\TenantRiskScore::create([
                'manager_id' => $manager->id, 'tenant_id' => $tenant->id, 'score' => 50,
                'level' => 'High', 'source' => 'history', 'factors' => [], 'computed_at' => now(),
            ]);
        }
        $this->actingAs($manager)->get('/manager/dashboard?risk_page=21')->assertOk()
            ->assertViewHas('riskTenants', fn ($items) => $items->total() === 101 && $items->count() === 1);
    }
}
