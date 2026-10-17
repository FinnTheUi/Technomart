<?php

namespace Tests\Feature\Manager;

use App\Mail\TenantCredentialsMail;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use App\Notifications\ApplicationApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

class ApprovalEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private RentalApplication $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'manager']);
        $stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'Stall 4', 'floor_number' => 1, 'stall_number' => 4, 'price' => 3000, 'status' => 'available']);
        $this->application = RentalApplication::create([
            'stall_id' => $stall->id, 'name' => 'Ana', 'email' => 'ana.applicant@gmail.com', 'password_hash' => bcrypt('secret123'),
            'phone' => '09171234567', 'address' => 'Street', 'rental_duration' => 6,
            'payment_method' => 'gcash', 'payment_status' => 'for_verification', 'gcash_reference' => '1234567890123',
        ]);
    }

    private function approve()
    {
        return $this->actingAs($this->manager)->patch("/manager/rental-applications/{$this->application->id}/approve");
    }

    public function test_applicant_is_emailed_immediately_even_without_a_queue_worker(): void
    {
        Mail::fake();
        Queue::fake(); // nothing processes queued jobs

        $this->approve()->assertSessionHas('success', 'Application approved! An approval email was sent to ana.applicant@gmail.com.');

        Mail::assertSent(TenantCredentialsMail::class, fn ($mail) => $mail->hasTo('ana.applicant@gmail.com'));
        Mail::assertNotQueued(TenantCredentialsMail::class);
    }

    public function test_email_shows_the_correct_first_payment_and_lease(): void
    {
        Mail::fake();

        $this->approve();

        Mail::assertSent(TenantCredentialsMail::class, function (TenantCredentialsMail $mail) {
            $html = $mail->render();

            return str_contains($mail->envelope()->subject, 'Stall 4 was approved')
                && str_contains($html, 'First month paid')
                && str_contains($html, '₱3,000.00')          // one month, not 6 × 3,000
                && ! str_contains($html, '₱18,000.00')
                && str_contains($html, '1234567890123')
                && str_contains($html, 'OR-'.now()->year)    // official receipt number
                && str_contains($html, route('login'));
        });
    }

    public function test_applicant_gets_one_email_and_an_in_app_notification(): void
    {
        Mail::fake();
        Notification::fake();

        $this->approve();

        Mail::assertSentCount(1);
        $tenant = User::where('email', 'ana.applicant@gmail.com')->firstOrFail();
        Notification::assertSentTo($tenant, ApplicationApprovedNotification::class, fn ($n, $channels) => $channels === ['database']);
    }

    public function test_mail_failure_keeps_the_approval_and_warns_the_manager(): void
    {
        Notification::fake();
        Mail::extend('failing', fn () => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new RuntimeException('SMTP authentication failed');
            }

            public function __toString(): string
            {
                return 'failing';
            }
        });
        config(['mail.mailers.failing' => ['transport' => 'failing'], 'mail.default' => 'failing']);

        $this->approve()->assertSessionHas('error', fn ($m) => str_contains($m, 'could not be sent'));

        $this->assertSame('approved', $this->application->fresh()->status->value);
        $this->assertDatabaseHas('users', ['email' => 'ana.applicant@gmail.com', 'role' => 'tenant']);
    }
}
