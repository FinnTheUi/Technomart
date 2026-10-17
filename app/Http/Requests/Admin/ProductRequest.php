<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create / update a catalog product. On update, a changed stock value is recorded
 * as a stock adjustment (see InventoryService::adjustTo).
 */
class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'nullable|string|max:100',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reorder_level' => 'nullable|integer|min:0',
        ];
    }

    /** Attributes other than stock. */
    public function productAttributes(): array
    {
        return array_merge(
            $this->safe()->only(['name', 'description', 'price', 'category', 'supplier_id']),
            ['reorder_level' => $this->filled('reorder_level') ? (int) $this->input('reorder_level') : 10],
        );
    }
}
