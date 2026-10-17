<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\GmailAddress;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'string',
                'lowercase',
                new GmailAddress,
                'max:40',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone_number' => ['nullable', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'address' => ['nullable', 'string', 'max:100'],
            'profile_photo' => $this->user()->isAdmin()
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000']
                : ['prohibited'],
        ];
    }
}
