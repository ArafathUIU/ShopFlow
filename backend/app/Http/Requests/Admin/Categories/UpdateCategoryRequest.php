<?php

namespace App\Http\Requests\Admin\Categories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'parent_id' => ['sometimes', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}