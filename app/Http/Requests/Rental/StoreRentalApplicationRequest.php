<?php

namespace App\Http\Requests\Rental;

use App\Rules\GmailAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreRentalApplicationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:20',
            'email' => ['required', 'string', 'lowercase', new GmailAddress, 'max:40'],
            'phone' => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'address' => 'required|string|max:100',
            'password' => ['required', 'confirmed', Password::defaults(), 'max:20'],
            'rental_duration' => 'required|integer|min:1|max:12',
            'terms' => 'accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must agree to the Terms and Conditions before proceeding.',
        ];
    }
}
