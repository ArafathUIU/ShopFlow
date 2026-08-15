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
            // Laptops
            ['name' => 'AeroBook Pro 14"', 'category' => 'Laptops', 'sku' => 'NB-AEROPRO-14', 'price' => 149900, 'compare_at_price' => 169900, 'description' => 'A lightweight 14-inch ultrabook with a 12-core processor, 16GB RAM, and a 512GB NVMe SSD.', 'featured' => true, 'stock' => 24],
            ['name' => 'AeroBook Air 13"', 'category' => 'Laptops', 'sku' => 'NB-AEROAIR-13', 'price' => 119900, 'description' => 'Fanless design, all-day battery, and a stunning 2.5K display.', 'featured' => true, 'stock' => 35],
            ['name' => 'VoltBook Gaming 16"', 'category' => 'Laptops', 'sku' => 'NB-VOLT-G16', 'price' => 219900, 'description' => 'RTX-class graphics, 165Hz display, and a vapor-chamber cooling system.', 'stock' => 8, 'low_stock_threshold' => 5],
            ['name' => 'ZenBook 15 OLED', 'category' => 'Laptops', 'sku' => 'NB-ZEN-15', 'price' => 179900, 'compare_at_price' => 189900, 'description' => '4K OLED touchscreen, Intel Core Ultra, and a premium aluminum chassis.', 'featured' => true, 'stock' => 18],
            ['name' => 'CloudBook 14"', 'category' => 'Laptops', 'sku' => 'NB-CLOUD-14', 'price' => 89900, 'description' => 'Budget-friendly Chromebook for students and casual browsing.', 'stock' => 50],
            ['name' => 'ProBook 16"', 'category' => 'Laptops', 'sku' => 'NB-PRO-16', 'price' => 249900, 'description' => 'Professional-grade mobile workstation with NVIDIA RTX Ada and 32GB RAM.', 'featured' => true, 'stock' => 6, 'low_stock_threshold' => 4],

            // Smartphones
            ['name' => 'PulsePhone 12 Pro', 'category' => 'Smartphones', 'sku' => 'PH-PULSE12-PRO', 'price' => 99900, 'compare_at_price' => 109900, 'description' => 'Triple-lens camera system, 120Hz OLED, and wireless charging.', 'featured' => true, 'stock' => 60],
            ['name' => 'PulsePhone 12', 'category' => 'Smartphones', 'sku' => 'PH-PULSE12', 'price' => 79900, 'description' => 'The everyday flagship. Great cameras, great battery, great price.', 'stock' => 45],
            ['name' => 'PulsePhone SE', 'category' => 'Smartphones', 'sku' => 'PH-PULSE-SE', 'price' => 49900, 'description' => 'Compact design, powerful chip, and excellent value.', 'stock' => 80],
            ['name' => 'NovaPhone Fold', 'category' => 'Smartphones', 'sku' => 'PH-NOVA-FOLD', 'price' => 199900, 'description' => 'A foldable display that transforms from phone to tablet.', 'featured' => true, 'stock' => 12, 'low_stock_threshold' => 5],
            ['name' => 'PixelView 7', 'category' => 'Smartphones', 'sku' => 'PH-PIXEL-7', 'price' => 69900, 'compare_at_price' => 74900, 'description' => 'Pure Android experience with class-leading computational photography.', 'stock' => 30],

            // Headphones
            ['name' => 'EchoBuds Max ANC', 'category' => 'Headphones', 'sku' => 'HP-ECHOBUD-MAX', 'price' => 24900, 'compare_at_price' => 29900, 'description' => 'Adaptive noise cancellation, spatial audio, and 40-hour battery life.', 'featured' => true, 'stock' => 120],
            ['name' => 'SonicPro Studio Headphones', 'category' => 'Headphones', 'sku' => 'HP-SONIC-ST', 'price' => 34900, 'description' => 'Studio-grade sound tuned for producers and audiophiles.', 'stock' => 3, 'low_stock_threshold' => 4],
            ['name' => 'BassPulse Wireless Earbuds', 'category' => 'Headphones', 'sku' => 'HP-BASS-PULSE', 'price' => 12900, 'description' => 'Deep bass, secure fit, and 8-hour battery per charge.', 'stock' => 200],
            ['name' => 'TravelSound On-Ear', 'category' => 'Headphones', 'sku' => 'HP-TRAVEL-OE', 'price' => 8990, 'compare_at_price' => 11900, 'description' => 'Foldable, lightweight, and perfect for commuting.', 'stock' => 90],
            ['name' => 'StreamCast Mic', 'category' => 'Headphones', 'sku' => 'HP-STREAM-MIC', 'price' => 15900, 'description' => 'Broadcast-quality USB microphone with a cardioid pattern.', 'featured' => true, 'stock' => 40],

            // Accessories
            ['name' => 'ChargePad 3-in-1', 'category' => 'Accessories', 'sku' => 'ACC-CHARGEPAD3', 'price' => 5999, 'description' => 'Charges your phone, watch, and earbuds simultaneously.', 'stock' => 200],
            ['name' => 'ThunderCable USB-C 2m', 'category' => 'Accessories', 'sku' => 'ACC-THUNDERC2', 'price' => 1999, 'description' => 'Braided 100W USB-C cable with a lifetime warranty.', 'stock' => 0],
            ['name' => 'SmartWatch Series 9', 'category' => 'Accessories', 'sku' => 'ACC-SWATCH-S9', 'price' => 39900, 'compare_at_price' => 44900, 'description' => 'Track fitness, sleep, and notifications on your wrist.', 'featured' => true, 'stock' => 55],
            ['name' => 'LensKit Phone Camera', 'category' => 'Accessories', 'sku' => 'ACC-LENSKIT', 'price' => 2499, 'description' => 'Clip-on macro, wide, and telephoto lenses for mobile photography.', 'stock' => 150],
            ['name' => 'PowerBank 20000mAh', 'category' => 'Accessories', 'sku' => 'ACC-PB-20K', 'price' => 3499, 'description' => 'Fast-charge 65W output with dual USB-C ports.', 'stock' => 180],

            // Cameras
            ['name' => 'LumeCam Mirrorless 4K', 'category' => 'Cameras', 'sku' => 'CAM-LUME-4K', 'price' => 129900, 'description' => 'Full-frame sensor, 4K 60fps video, and in-body stabilization.', 'featured' => true, 'stock' => 15],
            ['name' => 'SnapShot Action Cam', 'category' => 'Cameras', 'sku' => 'CAM-SNAP-ACT', 'price' => 29900, 'compare_at_price' => 34900, 'description' => 'Waterproof, 4K action camera for adventure and sports.', 'stock' => 60],
            ['name' => 'LumeCam Lens 50mm f/1.8', 'category' => 'Cameras', 'sku' => 'CAM-LUME-50', 'price' => 19900, 'description' => 'Crisp prime lens perfect for portraits and low-light shooting.', 'stock' => 25],

            // Wearables
            ['name' => 'FitBand Pro', 'category' => 'Wearables', 'sku' => 'WB-FIT-PRO', 'price' => 14900, 'description' => 'Heart rate, SpO2, GPS, and 7-day battery life.', 'stock' => 100],
            ['name' => 'SmartRing Air', 'category' => 'Wearables', 'sku' => 'WB-RING-AIR', 'price' => 19900, 'compare_at_price' => 24900, 'description' => 'Titanium smart ring tracking sleep and readiness.', 'featured' => true, 'stock' => 70],

            // Men
            ['name' => 'Urban Cotton Tee', 'category' => 'Men', 'sku' => 'CL-MEN-URBAN-TEE', 'price' => 2499, 'description' => 'Premium combed cotton, relaxed fit, pre-shrunk.', 'stock' => 150],
            ['name' => 'Slim Fit Chinos', 'category' => 'Men', 'sku' => 'CL-MEN-CHINOS', 'price' => 4999, 'compare_at_price' => 5999, 'description' => 'Stretch cotton chinos with a modern slim silhouette.', 'stock' => 80],
            ['name' => 'Merino Wool Hoodie', 'category' => 'Men', 'sku' => 'CL-MEN-MERINO', 'price' => 7999, 'description' => 'Temperature-regulating merino wool blend for cool evenings.', 'featured' => true, 'stock' => 40],
            ['name' => 'Classic Leather Belt', 'category' => 'Men', 'sku' => 'CL-MEN-BELT', 'price' => 2999, 'description' => 'Full-grain leather with a brushed nickel buckle.', 'stock' => 120],

            // Women
            ['name' => 'FlowMax Running Leggings', 'category' => 'Women', 'sku' => 'CL-WOM-LEGGINGS', 'price' => 3999, 'description' => 'High-waist, squat-proof leggings with hidden pockets.', 'featured' => true, 'stock' => 95],
            ['name' => 'SilkBlend Wrap Dress', 'category' => 'Women', 'sku' => 'CL-WOM-WRAP', 'price' => 8999, 'compare_at_price' => 10999, 'description' => 'Elegant wrap dress in a luxe satin-back crepe.', 'stock' => 30],
            ['name' => 'Everyday Linen Shirt', 'category' => 'Women', 'sku' => 'CL-WOM-LINEN', 'price' => 3499, 'description' => 'Relaxed linen shirt perfect for warm-weather layering.', 'stock' => 60],

            // Kids
            ['name' => 'DinoPrint Graphic Tee', 'category' => 'Kids', 'sku' => 'CL-KID-DINO', 'price' => 1499, 'description' => 'Fun dinosaur print tee in soft organic cotton.', 'stock' => 200],
            ['name' => 'StarryNight Pajamas', 'category' => 'Kids', 'sku' => 'CL-KID-PJS', 'price' => 2499, 'description' => 'Glow-in-the-dark star print pajama set.', 'stock' => 110],

            // Shoes
            ['name' => 'Trail Runner Sneakers', 'category' => 'Shoes', 'sku' => 'SH-TRAIL-RUN', 'price' => 8999, 'description' => 'Lightweight trail sneakers with grippy all-terrain outsoles.', 'featured' => true, 'stock' => 40],
            ['name' => 'CloudStep Slides', 'category' => 'Shoes', 'sku' => 'SH-CLOUD-SLIDE', 'price' => 2999, 'description' => 'Cushioned recovery slides for post-workout comfort.', 'stock' => 150],
            ['name' => 'Metro Leather Oxford', 'category' => 'Shoes', 'sku' => 'SH-METRO-OXF', 'price' => 12999, 'compare_at_price' => 14999, 'description' => 'Hand-finished leather oxford with a durable rubber sole.', 'stock' => 25],

            // Cookware
            ['name' => 'CastIron 26cm Skillet', 'category' => 'Cookware', 'sku' => 'HK-CI26-SKILLET', 'price' => 4999, 'description' => 'Pre-seasoned cast iron that lasts generations.', 'stock' => 75],
            ['name' => 'Chef\'s Knife 8"', 'category' => 'Cookware', 'sku' => 'HK-KNIFE-8', 'price' => 6999, 'compare_at_price' => 8999, 'description' => 'High-carbon German steel with a full-tang construction.', 'featured' => true, 'stock' => 35],
            ['name' => 'NonStick Fry Pan 24cm', 'category' => 'Cookware', 'sku' => 'HK-FRY-24', 'price' => 3499, 'description' => 'PFOA-free nonstick coating with a bakelite handle.', 'stock' => 60],

            // Decor
            ['name' => 'Minimalist Table Lamp', 'category' => 'Decor', 'sku' => 'HK-DECOR-LAMP', 'price' => 5999, 'description' => 'Matte black metal lamp with a warm LED bulb.', 'stock' => 45],
            ['name' => 'Woven Wall Hanging', 'category' => 'Decor', 'sku' => 'HK-DECOR-WALL', 'price' => 3999, 'description' => 'Handwoven macramé wall hanging made from natural cotton.', 'stock' => 30],

            // Furniture
            ['name' => 'ErgoChair Pro', 'category' => 'Furniture', 'sku' => 'HK-ERG-CHAIR', 'price' => 39999, 'compare_at_price' => 49999, 'description' => 'Adjustable lumbar support, breathable mesh, and 5-year warranty.', 'featured' => true, 'stock' => 10],
            ['name' => 'Standing Desk 48"', 'category' => 'Furniture', 'sku' => 'HK-DESK-48', 'price' => 59999, 'description' => 'Electric height-adjustable desk with a bamboo top.', 'stock' => 8, 'low_stock_threshold' => 5],

            // Bedding
            ['name' => 'Cooling Bamboo Sheet Set', 'category' => 'Bedding', 'sku' => 'HK-BED-BAMBOO', 'price' => 8999, 'description' => 'Temperature-regulating bamboo viscose, 400 thread count.', 'stock' => 70],
            ['name' => 'Weighted Blanket 15lb', 'category' => 'Bedding', 'sku' => 'HK-BED-WEIGHT', 'price' => 6999, 'compare_at_price' => 8999, 'description' => 'Glass-bead weighted blanket for deeper sleep.', 'stock' => 50],

            // Fiction
            ['name' => 'The Midnight Library', 'category' => 'Fiction', 'sku' => 'BK-FIC-MIDNIGHT', 'price' => 1499, 'description' => 'A novel about all the choices that go into a life well lived.', 'stock' => 120],
            ['name' => 'Project Hail Mary', 'category' => 'Fiction', 'sku' => 'BK-FIC-HAIL', 'price' => 1699, 'description' => 'A lone astronaut must save humanity in this propulsive adventure.', 'featured' => true, 'stock' => 90],

            // Non-Fiction
            ['name' => 'Atomic Habits', 'category' => 'Non-Fiction', 'sku' => 'BK-NON-ATOMIC', 'price' => 1999, 'compare_at_price' => 2499, 'description' => 'An easy and proven way to build good habits and break bad ones.', 'stock' => 200],
            ['name' => 'Thinking, Fast and Slow', 'category' => 'Non-Fiction', 'sku' => 'BK-NON-THINK', 'price' => 1799, 'description' => 'Nobel Prize winner Daniel Kahneman takes us on a groundbreaking tour of the mind.', 'stock' => 85],

            // Technical
            ['name' => 'The Pragmatic Programmer', 'category' => 'Technical', 'sku' => 'BK-TPP-20TH', 'price' => 4299, 'compare_at_price' => 4999, 'description' => 'Journey to mastery — 20th anniversary edition.', 'featured' => true, 'stock' => 5, 'low_stock_threshold' => 6],
            ['name' => 'Clean Code', 'category' => 'Technical', 'sku' => 'BK-TECH-CLEAN', 'price' => 3999, 'description' => 'A handbook of agile software craftsmanship.', 'stock' => 60],
            ['name' => 'Design Patterns', 'category' => 'Technical', 'sku' => 'BK-TECH-DP', 'price' => 4499, 'description' => 'Elements of reusable object-oriented software.', 'stock' => 40],

            // Comics
            ['name' => 'Batman: Year One', 'category' => 'Comics', 'sku' => 'BK-COM-BATMAN', 'price' => 1299, 'description' => 'Frank Miller and David Mazzucchelli\'s defining Batman story.', 'stock' => 100],
            ['name' => 'Watchmen', 'category' => 'Comics', 'sku' => 'BK-COM-WATCH', 'price' => 1499, 'description' => 'The groundbreaking graphic novel that redefined the genre.', 'stock' => 75],

            // Fitness
            ['name' => 'Trail Runner Sneakers', 'category' => 'Fitness', 'sku' => 'SP-FIT-TRAILRUN', 'price' => 8999, 'description' => 'Lightweight trail sneakers with grippy all-terrain outsoles.', 'featured' => true, 'stock' => 40],
            ['name' => 'Resistance Band Set', 'category' => 'Fitness', 'sku' => 'SP-FIT-BANDS', 'price' => 1999, 'description' => 'Five resistance levels with door anchor and ankle straps.', 'stock' => 130],
            ['name' => 'Yoga Mat Premium 6mm', 'category' => 'Fitness', 'sku' => 'SP-FIT-YOGA', 'price' => 3499, 'compare_at_price' => 4499, 'description' => 'Non-slip, eco-friendly TPE mat with carrying strap.', 'stock' => 90],

            // Camping
            ['name' => 'Core Camping Tent 2P', 'category' => 'Camping', 'sku' => 'SO-CAMP-TENT2P', 'price' => 15900, 'description' => 'Waterproof 2-person tent that sets up in under five minutes.', 'stock' => 30],
            ['name' => 'Sleeping Bag 20°F', 'category' => 'Camping', 'sku' => 'SO-CAMP-SLEEP', 'price' => 8999, 'description' => 'Mummy-style sleeping bag rated to 20°F with a stuff sack.', 'stock' => 45],
            ['name' => 'CampStove Dual Fuel', 'category' => 'Camping', 'sku' => 'SO-CAMP-STOVE', 'price' => 5999, 'description' => 'Lightweight stove that runs on butane or propane.', 'stock' => 55],

            // Cycling
            ['name' => 'VeloBike Helmet', 'category' => 'Cycling', 'sku' => 'SP-CYC-HELMET', 'price' => 4999, 'description' => 'Aerodynamic road helmet with MIPS protection.', 'stock' => 35],
            ['name' => 'VeloBike Lock', 'category' => 'Cycling', 'sku' => 'SP-CYC-LOCK', 'price' => 2999, 'description' => 'Heavy-duty U-lock with a 16mm hardened steel shackle.', 'stock' => 60],

            // Running
            ['name' => 'SpeedStride Running Shoes', 'category' => 'Running', 'sku' => 'SP-RUN-SPEED', 'price' => 12999, 'compare_at_price' => 14999, 'description' => 'Carbon-plated race day shoes with responsive foam.', 'featured' => true, 'stock' => 20],
            ['name' => 'DryFit Running Socks 3-Pack', 'category' => 'Running', 'sku' => 'SP-RUN-SOCKS', 'price' => 1499, 'description' => 'Moisture-wicking, blister-resistant socks with arch support.', 'stock' => 200],

            // Skincare
            ['name' => 'Vitamin C Serum 30ml', 'category' => 'Skincare', 'sku' => 'BH-SKIN-VITC', 'price' => 2499, 'description' => 'Brightening serum with 15% vitamin C and hyaluronic acid.', 'featured' => true, 'stock' => 150],
            ['name' => 'Retinol Night Cream', 'category' => 'Skincare', 'sku' => 'BH-SKIN-RET', 'price' => 2999, 'description' => 'Encapsulated retinol in a hydrating cream base.', 'stock' => 80],

            // Makeup
            ['name' => 'Matte Lipstick Duo', 'category' => 'Makeup', 'sku' => 'BH-MAKE-LIP', 'price' => 1999, 'description' => 'Long-wearing matte lipstick in two versatile shades.', 'stock' => 120],
            ['name' => 'Foundation Stick', 'category' => 'Makeup', 'sku' => 'BH-MAKE-FOUND', 'price' => 2999, 'description' => 'Buildable coverage stick with a natural satin finish.', 'stock' => 65],

            // Haircare
            ['name' => 'Argan Oil Hair Mask', 'category' => 'Haircare', 'sku' => 'BH-HAIR-MASK', 'price' => 1999, 'description' => 'Deep conditioning mask with argan oil and keratin.', 'stock' => 100],
            ['name' => 'Volumizing Dry Shampoo', 'category' => 'Haircare', 'sku' => 'BH-HAIR-DRY', 'price' => 1499, 'description' => 'Oil-absorbing dry shampoo for fresh-feeling hair between washes.', 'stock' => 140],

            // Supplements
            ['name' => 'Omega-3 Fish Oil', 'category' => 'Supplements', 'sku' => 'BH-SUPP-OMEGA', 'price' => 1999, 'description' => 'Molecularly distilled fish oil with EPA and DHA.', 'stock' => 180],
            ['name' => 'Vitamin D3 5000 IU', 'category' => 'Supplements', 'sku' => 'BH-SUPP-D3', 'price' => 1299, 'description' => 'Supports bone health and immune function.', 'stock' => 220],
        ];
    }

    public function run(): void
    {
        foreach ($this->products() as $data) {
            $category = Category::query()->where('slug', str($data['category'])->slug())->first();
            $baseSlug = str($data['name'])->slug();
            $slug = $baseSlug;
            $counter = 1;

            while (Product::query()->where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $product = Product::query()->updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'slug' => $slug,
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

            ProductImage::query()->updateOrCreate(
                ['product_id' => $product->id, 'is_primary' => true],
                [
                    'path' => 'https://placehold.co/400x400/1e293b/ffffff?text=' . urlencode($product->name),
                    'disk' => 'public',
                    'alt_text' => $product->name,
                    'sort_order' => 0,
                ]
            );
        }
    }
}
