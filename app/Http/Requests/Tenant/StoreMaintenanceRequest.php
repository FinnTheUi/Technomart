<?php

namespace App\Http\Requests\Tenant;

use App\Enums\ContractStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'stall_id' => [
                'required',
                Rule::exists('stalls', 'id')->where(function (Builder $query): void {
                    $query->whereExists(function (Builder $contracts): void {
                        $contracts->selectRaw('1')
                            ->from('contracts')
                            ->whereColumn('contracts.stall_id', 'stalls.id')
                            ->where('contracts.tenant_id', $this->user()->id)
                            ->where('contracts.status', ContractStatus::Active->value);
                    });
                }),
            ],
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
        ];
    }
}
