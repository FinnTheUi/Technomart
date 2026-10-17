<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Stall details a manager can change. The name and position come from the floor layout.
 */
class UpdateStallRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'price' => 'required|numeric|min:0|max:1000000',
            'size' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
        ];
    }
}
