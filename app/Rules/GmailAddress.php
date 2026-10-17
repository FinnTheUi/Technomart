<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts only @gmail.com addresses (the system's accepted account provider).
 */
class GmailAddress implements ValidationRule
{
    private const PATTERN = '/^[a-z0-9](?:[a-z0-9.]*[a-z0-9])?@gmail\.com$/i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value) || str_contains($value, '..')) {
            $fail('The :attribute must be a valid @gmail.com address.');
        }
    }
}
