<?php

namespace App\Http\Requests\Catalog;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductsRequest extends FormRequest
{
    public const SORTS = [
        'price_asc',
        'price_desc',
        'newest',
        'oldest',
        'name_asc',
        'name_desc',
        'featured',
        'popular',
    ];

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 24);
    }

    /**
     * Price filter converted to integer cents.
     */
    public function minPriceCents(): ?int
    {
        if (! $this->filled('min_price')) {
            return null;
        }

        return (int) round((float) $this->input('min_price') * 100);
    }

    public function maxPriceCents(): ?int
    {
        if (! $this->filled('max_price')) {
            return null;
        }

        return (int) round((float) $this->input('max_price') * 100);
    }
}
