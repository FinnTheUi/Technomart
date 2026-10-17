<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'counted_stock' => 'required|integer|min:0',
            'note' => 'required|string|max:255',
        ];
    }
}
