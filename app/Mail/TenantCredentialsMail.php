<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\RentalApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the applicant's email when a manager approves their rental application.
 * The approver sends it immediately (sendNow) so it arrives even without a queue worker.
 */
class TenantCredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public RentalApplication $application,
        public ?Contract $contract = null,
        public ?Payment $firstPayment = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your rental application for '.$this->application->stall->name.' was approved — Technomart',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-credentials',
            with: [
                'application' => $this->application,
                'stall' => $this->application->stall,
                'contract' => $this->contract,
                'firstPayment' => $this->firstPayment,
                'paymentMethod' => match ($this->application->payment_method) {
                    'gcash' => 'GCash',
                    'online' => 'Online (PayMongo)',
                    default => 'Cash (in person)',
                },
            ],
        );
    }
}
