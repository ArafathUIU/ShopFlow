<?php

namespace App\Http\Requests\Admin\Orders;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isManager();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', new Enum(OrderStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
