<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class TerminateContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:255',
        ];
    }
}
