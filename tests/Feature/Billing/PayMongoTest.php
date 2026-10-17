<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PayMongoTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsk_test_secret';

    private User $manager;

    private User $tenant;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'services.paymongo.secret_key_encrypted' => Crypt::encryptString('sk_test_123'),
            'services.paymongo.webhook_secret_encrypted' => Crypt::encryptString(self::WEBHOOK_SECRET),
        ]);

        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->tenant = User::factory()->create(['role' => 'tenant']);
        $this->stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'Stall A', 'price' => 5000, 'status' => 'available']);
    }

    private function bill(): Payment
    {
        return Payment::create([
            'tenant_id' => $this->tenant->id, 'manager_id' => $this->manager->id, 'stall_id' => $this->stall->id,
            'amount' => 5000, 'due_date' => today()->addWeek(), 'status' => 'pending',
        ]);
    }

    private function fakeCheckout(string $id = 'cs_test_1'): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response([
            'data' => ['id' => $id, 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/'.$id]],
        ])]);
    }

    private function paidEvent(string $type, int $id, string $sessionId, int $centavos): array
    {
        return ['data' => ['id' => 'evt_1', 'type' => 'event', 'attributes' => [
            'type' => 'checkout_session.payment.paid',
            'livemode' => false,
            'data' => ['id' => $sessionId, 'type' => 'checkout_session', 'attributes' => [
                'metadata' => ['payable_type' => $type, 'payable_id' => (string) $id],
                'payments' => [['id' => 'pay_abc', 'attributes' => ['amount' => $centavos, 'status' => 'paid']]],
            ]],
        ]]];
    }

    private function postWebhook(array $event, ?string $secret = self::WEBHOOK_SECRET)
    {
        $body = json_encode($event);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, (string) $secret);

        return $this->call('POST', '/webhooks/paymongo', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li=",
        ], $body);
    }

    public function test_tenant_is_redirected_to_paymongo_checkout(): void
    {
        $this->fakeCheckout();
        $bill = $this->bill();

        $this->actingAs($this->tenant)->post("/billing/payment/{$bill->id}/checkout")
            ->assertRedirect('https://checkout.paymongo.com/cs_test_1');

        $this->assertSame('cs_test_1', $bill->fresh()->gateway_checkout_id);
        Http::assertSent(fn ($request) => $request['data']['attributes']['line_items'][0]['amount'] === 500000
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('sk_test_123:')));
    }

    public function test_signed_webhook_settles_the_bill_and_issues_a_receipt(): void
    {
        $this->fakeCheckout();
        $bill = $this->bill();
        $this->actingAs($this->tenant)->post("/billing/payment/{$bill->id}/checkout");

        $this->postWebhook($this->paidEvent('payment', $bill->id, 'cs_test_1', 500000))
            ->assertOk()->assertJson(['handled' => true]);

        $bill->refresh();
        $this->assertSame(PaymentStatus::Paid, $bill->status);
        $this->assertSame('online', $bill->payment_method);
        $this->assertSame('pay_abc', $bill->reference_number);
        $this->assertNotNull($bill->receipt_number);

        // Redelivery of the same event is idempotent.
        $this->postWebhook($this->paidEvent('payment', $bill->id, 'cs_test_1', 500000))->assertJson(['handled' => false]);
        $this->assertSame($bill->receipt_number, $bill->fresh()->receipt_number);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $bill = $this->bill();
        $bill->forceFill(['gateway_checkout_id' => 'cs_test_1'])->save();

        $this->postWebhook($this->paidEvent('payment', $bill->id, 'cs_test_1', 500000), 'wrong-secret')->assertStatus(401);

        $this->assertSame(PaymentStatus::Pending, $bill->fresh()->status);
    }

    public function test_webhook_for_a_session_we_did_not_create_is_ignored(): void
    {
        $bill = $this->bill();
        $bill->forceFill(['gateway_checkout_id' => 'cs_real'])->save();

        $this->postWebhook($this->paidEvent('payment', $bill->id, 'cs_forged', 500000))->assertJson(['handled' => false]);

        $this->assertSame(PaymentStatus::Pending, $bill->fresh()->status);
    }

    public function test_underpayment_is_not_settled(): void
    {
        $bill = $this->bill();
        $bill->forceFill(['gateway_checkout_id' => 'cs_test_1'])->save();

        $this->postWebhook($this->paidEvent('payment', $bill->id, 'cs_test_1', 100))->assertJson(['handled' => false]);

        $this->assertSame(PaymentStatus::Pending, $bill->fresh()->status);
    }

    public function test_guest_rental_application_can_be_paid_online(): void
    {
        $this->fakeCheckout('cs_app');
        $application = RentalApplication::create([
            'stall_id' => $this->stall->id, 'name' => 'Guest', 'email' => 'guest@gmail.com', 'password_hash' => bcrypt('x'),
            'phone' => '09171234567', 'address' => 'Street', 'rental_duration' => 2, 'payment_status' => 'pending',
        ]);

        $this->post($application->signedUrl('rental.payGateway'))->assertRedirect('https://checkout.paymongo.com/cs_app');

        $this->postWebhook($this->paidEvent('rental_application', $application->id, 'cs_app', 500000))->assertJson(['handled' => true]);

        $application->refresh();
        $this->assertSame(PaymentStatus::Paid, $application->payment_status);
        $this->assertSame('online', $application->payment_method);
    }

    public function test_online_payment_is_hidden_when_not_configured(): void
    {
        config(['services.paymongo.secret_key_encrypted' => null]);
        $bill = $this->bill();

        $this->actingAs($this->tenant)->get('/tenant/bills')->assertOk()->assertDontSee('Pay online');
        $this->actingAs($this->tenant)->post("/billing/payment/{$bill->id}/checkout")->assertSessionHas('error');
    }
}
