<?php

namespace App\Http\Requests\Manager;

use App\Http\Requests\Manager\Concerns\ValidatesManagerScope;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    use ValidatesManagerScope;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', $this->tenantExists()],
            'stall_id' => ['required', $this->ownStallExists()],
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
        ];
    }
}
