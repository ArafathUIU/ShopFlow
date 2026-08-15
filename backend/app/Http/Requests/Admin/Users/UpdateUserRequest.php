<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'string', new Enum(UserRole::class)],
        ];
    }
}
