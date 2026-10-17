<?php

namespace App\Http\Requests\Rental;

use App\Rules\GmailAddress;
use Illuminate\Foundation\Http\FormRequest;

class JoinWaitlistRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:20',
            'email' => ['required', 'string', 'lowercase', new GmailAddress, 'max:40'],
            'phone' => ['nullable', 'string', 'regex:/^(09|\+639)\d{9}$/'],
        ];
    }
}
