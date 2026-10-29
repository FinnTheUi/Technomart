<?php

namespace App\Http\Requests\Manager;

use App\Http\Requests\Manager\Concerns\ValidatesManagerScope;
use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Validation\Validator;
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

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if (! Contract::where('status', ContractStatus::Active)
                ->where('tenant_id', $this->integer('tenant_id'))->where('stall_id', $this->integer('stall_id'))
                ->whereHas('stall', fn ($query) => $query->where('manager_id', $this->user()->id))->exists()) {
                $validator->errors()->add('stall_id', 'The selected stall is not currently leased by the selected tenant.');
            }
        }];
    }
}
