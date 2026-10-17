<?php

namespace App\Http\Requests\Admin;

use App\Rules\GmailAddress;
use Illuminate\Foundation\Http\FormRequest;

class StoreManagerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:20',
            'email' => ['required', 'string', 'lowercase', new GmailAddress, 'max:40', 'unique:users,email'],
            'password' => 'required|string|min:8|max:20|confirmed',
        ];
    }
}
