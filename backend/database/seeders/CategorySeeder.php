<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics' => ['Laptops', 'Smartphones', 'Headphones', 'Accessories'],
            'Clothing' => ['Men', 'Women', 'Kids'],
            'Home & Kitchen' => ['Cookware', 'Decor', 'Furniture'],
            'Books' => ['Fiction', 'Non-Fiction', 'Technical'],
            'Sports & Outdoors' => ['Fitness', 'Camping', 'Cycling'],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = Category::query()->firstOrCreate(
                ['slug' => str($parentName)->slug()],
                ['name' => $parentName, 'description' => "$parentName products.", 'is_active' => true, 'sort_order' => 0]
            );

            foreach ($children as $index => $childName) {
                Category::query()->firstOrCreate(
                    ['slug' => str($childName)->slug()],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'description' => "$childName for every budget.",
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            }
        }
    }
}
