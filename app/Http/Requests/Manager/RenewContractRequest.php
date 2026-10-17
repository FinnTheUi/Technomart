<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used by managers to renew, and by tenants to request a renewal.
 */
class RenewContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'months' => 'required|integer|min:1|max:12',
        ];
    }
}
