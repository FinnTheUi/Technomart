<?php

namespace App\Http\Requests\Manager;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_TENANT)->where('is_active', true)],
            'months' => 'required|integer|min:1|max:12',
        ];
    }

    public function messages(): array
    {
        return ['tenant_id.exists' => 'Choose an active tenant account.'];
    }
}
