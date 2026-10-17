<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Runs a mail-sending action without letting a mail-server failure (wrong SMTP password,
 * server down, ...) break the surrounding request. Failures are logged for the admin.
 */
class SafeMail
{
    public const FAILURE_MESSAGE = 'We couldn\'t send the email right now. Please try again later or contact the administrator.';

    /**
     * @return bool true if the mail was handed to the mail server
     */
    public static function attempt(callable $send, array $context = []): bool
    {
        try {
            $send();

            return true;
        } catch (TransportExceptionInterface $e) {
            Log::error('Email could not be sent: '.$e->getMessage(), $context);

            return false;
        }
    }
}
