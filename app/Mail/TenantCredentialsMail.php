<?php

namespace App\Mail;

use App\Models\RentalApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public RentalApplication $application;

    /**
     * Create a new message instance.
     */
    public function __construct(RentalApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Tecnomart Tenant Account — Login Credentials & Receipt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-credentials',
            with: [
                'application' => $this->application,
                'stall' => $this->application->stall,
            ],
        );
    }
}
