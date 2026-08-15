<?php

namespace App\Http\Requests\Cart;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
