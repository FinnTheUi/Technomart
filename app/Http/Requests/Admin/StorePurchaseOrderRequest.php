<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Ignore blank rows left in the dynamic item table.
        $this->merge([
            'items' => array_values(array_filter(
                (array) $this->input('items', []),
                fn ($item) => filled($item['product_id'] ?? null),
            )),
        ]);
    }

    /**
     * @return array<int, array{product_id:int, quantity:int, unit_cost:float}>
     */
    public function items(): array
    {
        return array_map(fn ($item) => [
            'product_id' => (int) $item['product_id'],
            'quantity' => (int) $item['quantity'],
            'unit_cost' => (float) $item['unit_cost'],
        ], $this->validated('items'));
    }
}
