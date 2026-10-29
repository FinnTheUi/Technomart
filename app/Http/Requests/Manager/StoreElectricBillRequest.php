<?php

namespace App\Http\Requests\Manager;

use App\Enums\ContractStatus;
use App\Http\Requests\Manager\Concerns\ValidatesManagerScope;
use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreElectricBillRequest extends FormRequest
{
    use ValidatesManagerScope;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', $this->tenantExists()],
            'stall_id' => ['required', $this->ownStallExists()],
            'meter_number' => 'nullable|string|max:100',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'billing_date' => 'required|date',
            'reading_present' => 'required|numeric|min:0|gte:reading_previous',
            'reading_previous' => 'required|numeric|min:0',
            'rate_per_kwh' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'prepared_by' => 'nullable|string|max:255',
            'noted_by' => 'nullable|string|max:255',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['tenant_id', 'stall_id'])) {
                return;
            }

            $matchesActiveLease = Contract::where('tenant_id', $this->integer('tenant_id'))
                ->where('stall_id', $this->integer('stall_id'))
                ->where('status', ContractStatus::Active)
                ->whereHas('stall', fn ($query) => $query->where('manager_id', $this->user()->id))
                ->exists();

            if (! $matchesActiveLease) {
                $validator->errors()->add('stall_id', 'The selected stall is not currently leased by the selected tenant.');
            }
        }];
    }

    public function kwhUsed(): float
    {
        return max(0, (float) $this->reading_present - (float) $this->reading_previous);
    }

    public function totalAmount(): float
    {
        return round($this->kwhUsed() * (float) $this->rate_per_kwh, 2);
    }
}
