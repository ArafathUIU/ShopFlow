<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics' => ['Laptops', 'Smartphones', 'Headphones', 'Accessories', 'Cameras', 'Wearables'],
            'Clothing' => ['Men', 'Women', 'Kids', 'Shoes'],
            'Home & Kitchen' => ['Cookware', 'Decor', 'Furniture', 'Bedding'],
            'Books' => ['Fiction', 'Non-Fiction', 'Technical', 'Comics'],
            'Sports & Outdoors' => ['Fitness', 'Camping', 'Cycling', 'Running'],
            'Beauty & Health' => ['Skincare', 'Makeup', 'Haircare', 'Supplements'],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = Category::query()->firstOrCreate(
                ['slug' => str($parentName)->slug()],
                ['name' => $parentName, 'description' => "Shop the best {$parentName} products online.", 'is_active' => true, 'sort_order' => 0]
            );

            foreach ($children as $index => $childName) {
                Category::query()->firstOrCreate(
                    ['slug' => str($childName)->slug()],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'description' => "Top-rated {$childName} for every budget.",
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            }
        }
    }
}
