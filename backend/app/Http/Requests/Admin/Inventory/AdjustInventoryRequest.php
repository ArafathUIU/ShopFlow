<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:-999999', 'max:999999'],
            'reason' => ['required', 'string', 'max:255'],
            'product_id' => ['sometimes', 'exists:products,id'],
        ];
    }
}