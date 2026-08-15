<?php

namespace App\Http\Requests\Admin\Products;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')],
            'price' => ['required', 'numeric', 'min:0.01'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
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
