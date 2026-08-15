<?php

namespace App\Http\Requests\Admin\Categories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAdminCategoriesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 24);
    }
}