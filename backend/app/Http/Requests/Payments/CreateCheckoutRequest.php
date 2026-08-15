<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCheckoutRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'integer',
                Rule::exists('orders', 'id'),
            ],
        ];
    }
}
