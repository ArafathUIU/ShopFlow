<?php

namespace App\Http\Requests\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;

class IndexUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isManager();
    }

    public function rules(): array
    {
        return [
            'role' => ['nullable', 'string', 'in:customer,admin,manager'],
            'active' => ['nullable', 'boolean'],
            'verified' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
