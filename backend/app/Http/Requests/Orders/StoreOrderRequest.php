<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'shipping_address' => ['required', 'array'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.state' => ['required', 'string', 'max:255'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.country' => ['required', 'string', 'size:2'],

            'billing_address' => ['sometimes', 'array'],
            'billing_address.line1' => ['sometimes', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['sometimes', 'string', 'max:255'],
            'billing_address.state' => ['sometimes', 'string', 'max:255'],
            'billing_address.postal_code' => ['sometimes', 'string', 'max:20'],
            'billing_address.country' => ['sometimes', 'string', 'size:2'],

            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
