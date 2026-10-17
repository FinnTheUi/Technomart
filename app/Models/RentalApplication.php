<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class RentalApplication extends Model
{
    /** How long the applicant's checkout/receipt links remain valid. */
    public const LINK_TTL_HOURS = 72;

    protected $fillable = [
        'stall_id', 'name', 'email', 'password_hash', 'phone', 'address',
        'rental_duration', 'payment_method', 'payment_status',
        'status', 'gcash_reference', 'gateway_checkout_id', 'generated_username', 'generated_password',
    ];

    protected $hidden = ['password_hash', 'generated_password'];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class);
    }

    public function isPending(): bool
    {
        return $this->status === ApplicationStatus::PendingApproval;
    }

    /** The applicant hasn't chosen or completed a payment option yet. */
    public function awaitingPayment(): bool
    {
        return $this->isPending()
            && $this->payment_method === null
            && $this->payment_status === PaymentStatus::Pending;
    }

    /**
     * Applications are submitted by guests, so their follow-up pages are protected
     * with expiring signed URLs instead of sequential, guessable IDs.
     */
    public function signedUrl(string $routeName): string
    {
        return URL::temporarySignedRoute($routeName, now()->addHours(self::LINK_TTL_HOURS), $this);
    }
}
