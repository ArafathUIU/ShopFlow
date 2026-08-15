<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * @return array<int, array{
     *     name: string,
     *     category: string,
     *     sku: string,
     *     price: int,
     *     compare_at_price?: int,
     *     description: string,
     *     featured?: bool,
     *     stock: int,
     *     low_stock_threshold?: int,
     *     status?: ProductStatus,
     * }>
     */
    private function products(): array
    {
        return [
            [
                'name' => 'AeroBook Pro 14"',
                'category' => 'Laptops',
                'sku' => 'NB-AEROPRO-14',
                'price' => 149900,
                'compare_at_price' => 169900,
                'description' => 'A lightweight 14-inch ultrabook with a 12-core processor, 16GB RAM, and a 512GB NVMe SSD.',
                'featured' => true,
                'stock' => 24,
            ],
            [
                'name' => 'AeroBook Air 13"',
                'category' => 'Laptops',
                'sku' => 'NB-AEROAIR-13',
                'price' => 119900,
                'description' => 'Fanless design, all-day battery, and a stunning 2.5K display.',
                'featured' => true,
                'stock' => 35,
            ],
            [
                'name' => 'VoltBook Gaming 16"',
                'category' => 'Laptops',
                'sku' => 'NB-VOLT-G16',
                'price' => 219900,
                'description' => 'RTX-class graphics, 165Hz display, and a vapor-chamber cooling system.',
                'stock' => 8,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'PulsePhone 12 Pro',
                'category' => 'Smartphones',
                'sku' => 'PH-PULSE12-PRO',
                'price' => 99900,
                'compare_at_price' => 109900,
                'description' => 'Triple-lens camera system, 120Hz OLED, and wireless charging.',
                'featured' => true,
                'stock' => 60,
            ],
            [
                'name' => 'PulsePhone 12',
                'category' => 'Smartphones',
                'sku' => 'PH-PULSE12',
                'price' => 79900,
                'description' => 'The everyday flagship. Great cameras, great battery, great price.',
                'stock' => 45,
            ],
            [
                'name' => 'EchoBuds Max ANC',
                'category' => 'Headphones',
                'sku' => 'HP-ECHOBUD-MAX',
                'price' => 24900,
                'compare_at_price' => 29900,
                'description' => 'Adaptive noise cancellation, spatial audio, and 40-hour battery life.',
                'featured' => true,
                'stock' => 120,
            ],
            [
                'name' => 'SonicPro Studio Headphones',
                'category' => 'Headphones',
                'sku' => 'HP-SONIC-ST',
                'price' => 34900,
                'description' => 'Studio-grade sound tuned for producers and audiophiles.',
                'stock' => 3,
                'low_stock_threshold' => 4,
            ],
            [
                'name' => 'ChargePad 3-in-1',
                'category' => 'Accessories',
                'sku' => 'ACC-CHARGEPAD3',
                'price' => 5999,
                'description' => 'Charges your phone, watch, and earbuds simultaneously.',
                'stock' => 200,
            ],
            [
                'name' => 'ThunderCable USB-C 2m',
                'category' => 'Accessories',
                'sku' => 'ACC-THUNDERC2',
                'price' => 1999,
                'description' => 'Braided 100W USB-C cable with a lifetime warranty.',
                'stock' => 0,
            ],
            [
                'name' => 'Urban Cotton Tee',
                'category' => 'Men',
                'sku' => 'CL-MEN-URBAN-TEE',
                'price' => 2499,
                'description' => 'Premium combed cotton, relaxed fit, pre-shrunk.',
                'stock' => 150,
            ],
            [
                'name' => 'Trail Runner Sneakers',
                'category' => 'Fitness',
                'sku' => 'SP-FIT-TRAILRUN',
                'price' => 8999,
                'description' => 'Lightweight trail sneakers with grippy all-terrain outsoles.',
                'featured' => true,
                'stock' => 40,
            ],
            [
                'name' => 'CastIron 26cm Skillet',
                'category' => 'Cookware',
                'sku' => 'HK-CI26-SKILLET',
                'price' => 4999,
                'description' => 'Pre-seasoned cast iron that lasts generations.',
                'stock' => 75,
            ],
            [
                'name' => 'The Pragmatic Programmer',
                'category' => 'Technical',
                'sku' => 'BK-TPP-20TH',
                'price' => 4299,
                'compare_at_price' => 4999,
                'description' => 'Journey to mastery — 20th anniversary edition.',
                'featured' => true,
                'stock' => 5,
                'low_stock_threshold' => 6,
            ],
            [
                'name' => 'Core Camping Tent 2P',
                'category' => 'Camping',
                'sku' => 'SO-CAMP-TENT2P',
                'price' => 15900,
                'description' => 'Waterproof 2-person tent that sets up in under five minutes.',
                'stock' => 30,
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->products() as $data) {
            $category = Category::query()->where('slug', str($data['category'])->slug())->first();

            $product = Product::query()->updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'slug' => str($data['name'])->slug(),
                    'category_id' => $category?->id,
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'compare_at_price' => $data['compare_at_price'] ?? null,
                    'status' => $data['status'] ?? ProductStatus::Active,
                    'is_featured' => $data['featured'] ?? false,
                ]
            );

            Inventory::query()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity' => $data['stock'],
                    'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                    'reserved_quantity' => 0,
                ]
            );

            if (! $product->images()->exists()) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => "products/{$product->slug}.jpg",
                    'disk' => 'local',
                    'alt_text' => $product->name,
                    'sort_order' => 0,
                    'is_primary' => true,
                ]);
            }
        }
    }
}
