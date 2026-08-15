<?php

namespace App\Http\Requests\Admin\Products;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'category_id' => ['sometimes', 'integer', Rule::exists(Category::class, 'id')],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'sku' => ['sometimes', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($productId)],
            'price' => ['sometimes', 'numeric', 'min:0.01'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'draft', 'archived'])],
            'is_featured' => ['sometimes', 'boolean'],
            'images' => ['sometimes', 'array', 'max:20'],
            'images.*.path' => ['required_with:images', 'string', 'max:2048'],
            'images.*.disk' => ['nullable', 'string', 'max:50'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'images.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function priceCents(): int
    {
        return (int) round((float) $this->input('price') * 100);
    }

    public function compareAtPriceCents(): ?int
    {
        if (! $this->filled('compare_at_price')) {
            return null;
        }

        return (int) round((float) $this->input('compare_at_price') * 100);
    }
}
