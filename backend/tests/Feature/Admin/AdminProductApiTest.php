<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

function adminCustomer(): User
{
    return User::factory()->create(['role' => UserRole::Customer]);
}

function adminProduct(array $attributes = []): Product
{
    return Product::factory()->create($attributes);
}

it('requires authentication for admin product endpoints', function (): void {
    $this->getJson('/api/v1/admin/products')->assertStatus(401);
});

it('forbids non-admin users from managing products', function (): void {
    $user = adminCustomer();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/products')
        ->assertStatus(403);
});

it('lists all products including trashed ones for admins', function (): void {
    $admin = adminUser();
    $trashed = adminProduct(['name' => 'Trashed Product']);
    $trashed->delete();
    adminProduct(['name' => 'Visible Product']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/products')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Visible Product')
        ->assertJsonPath('data.1.name', 'Trashed Product')
        ->assertJsonPath('meta.pagination.total', 2);
});

it('filters products by trashed status', function (): void {
    $admin = adminUser();
    $trashed = adminProduct(['name' => 'Gone']);
    $trashed->delete();
    adminProduct(['name' => 'Here']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/products?trashed_only=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Gone')
        ->assertJsonPath('data.0.deleted_at', $trashed->fresh()->deleted_at->toISOString());
});

it('creates a product with an auto-generated slug', function (): void {
    $admin = adminUser();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', [
            'name' => 'Wireless Mouse',
            'sku' => 'MOUSE-001',
            'price' => 24.99,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Wireless Mouse')
        ->assertJsonPath('data.slug', 'wireless-mouse')
        ->assertJsonPath('data.price.cents', 2499)
        ->assertJsonPath('data.status', 'draft');

    $this->assertDatabaseHas('products', [
        'name' => 'Wireless Mouse',
        'slug' => 'wireless-mouse',
        'sku' => 'MOUSE-001',
        'price' => 2499,
        'status' => ProductStatus::Draft->value,
    ]);
});

it('creates an active featured product with images', function (): void {
    $admin = adminUser();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'slug' => 'mech-keyboard',
            'sku' => 'KB-001',
            'price' => 89.00,
            'compare_at_price' => 99.00,
            'status' => 'active',
            'is_featured' => true,
            'images' => [
                ['path' => 'products/kb-1.jpg', 'is_primary' => true],
                ['path' => 'products/kb-2.jpg'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonCount(2, 'data.images');

    $product = Product::query()->where('slug', 'mech-keyboard')->first();
    expect($product->price->cents())->toBe(8900)
        ->and($product->compare_at_price->cents())->toBe(9900)
        ->and($product->is_featured)->toBeTrue();

    $this->assertDatabaseCount('product_images', 2);
    $this->assertDatabaseHas('product_images', ['product_id' => $product->id, 'path' => 'products/kb-1.jpg', 'is_primary' => true]);
});

it('rejects duplicate skus and slugs', function (): void {
    $admin = adminUser();
    adminProduct(['sku' => 'SKU-X', 'slug' => 'existing']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', [
            'name' => 'Duplicate',
            'sku' => 'SKU-X',
            'price' => 10,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sku']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', [
            'name' => 'Another',
            'slug' => 'existing',
            'sku' => 'SKU-Y',
            'price' => 10,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['slug']);
});

it('rejects a product without a name', function (): void {
    $admin = adminUser();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', ['sku' => 'SKU-N', 'price' => 10])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('updates a product partially', function (): void {
    $admin = adminUser();
    $product = adminProduct(['name' => 'Old Name', 'price' => 1000]);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/admin/products/{$product->id}", ['price' => 15.5, 'is_featured' => true])
        ->assertOk()
        ->assertJsonPath('data.name', 'Old Name')
        ->assertJsonPath('data.price.cents', 1550)
        ->assertJsonPath('data.is_featured', true);
});

it('allows updating a product to another unique slug', function (): void {
    $admin = adminUser();
    $product = adminProduct(['slug' => 'original-slug']);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/admin/products/{$product->id}", ['slug' => 'new-slug'])
        ->assertOk()
        ->assertJsonPath('data.slug', 'new-slug');
});

it('shows a single product to an admin', function (): void {
    $admin = adminUser();
    $product = adminProduct(['name' => 'Detail Product']);
    $product->images()->create(['path' => 'products/detail.jpg']);

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Detail Product')
        ->assertJsonCount(1, 'data.images');
});

it('soft deletes and restores a product', function (): void {
    $admin = adminUser();
    $product = adminProduct(['name' => 'Doomed']);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/products/{$product->id}")
        ->assertOk();

    $this->assertSoftDeleted('products', ['id' => $product->id]);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/products/{$product->id}/restore")
        ->assertOk()
        ->assertJsonPath('data.name', 'Doomed');

    $this->assertNotSoftDeleted('products', ['id' => $product->id]);
});

it('archives and unarchives a product', function (): void {
    $admin = adminUser();
    $product = adminProduct(['name' => 'Seasonal']);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/products/{$product->id}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    $this->assertNotNull($product->fresh()->archived_at);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/products/{$product->id}/unarchive")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->assertNull($product->fresh()->archived_at);
});

it('attaches and detaches product images', function (): void {
    $admin = adminUser();
    $product = adminProduct(['name' => 'Photogenic']);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/products/{$product->id}/images", [
            'path' => 'products/photo.jpg',
            'disk' => 'public',
            'is_primary' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.path', 'products/photo.jpg');

    $image = $product->fresh()->images()->first();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/products/{$product->id}/images/{$image->id}")
        ->assertOk();

    $this->assertDatabaseCount('product_images', 0);
});
