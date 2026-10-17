<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A tenant submitting a GCash reference number (and optional screenshot) for verification.
 */
class SubmitPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'regex:/^\d{13}$/'],
            'proof' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_number.regex' => 'Enter the 13-digit GCash reference number from your receipt.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Accept "1234 567 890123" as typed from the GCash app.
        $this->merge(['reference_number' => preg_replace('/\s+/', '', (string) $this->reference_number)]);
    }
}
