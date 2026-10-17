<?php

namespace App\Services\Auth;

use RuntimeException;

/**
 * Raised when a Google identity cannot be used to sign in or register.
 * The message is safe to show to the end user.
 */
class GoogleSignInException extends RuntimeException {}
