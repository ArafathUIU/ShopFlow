<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('admin cannot access without authentication', function (): void {
    $this->getJson('/api/v1/admin/categories')->assertStatus(404);
});

it('forbids non-admin users from managing categories', function (): void {
    $user = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/categories')
        ->assertStatus(403);
});

function testCategory(array $attributes = []): Category
{
    return Category::factory()->create($attributes);
}

it('lists all categories including trashed ones for admins', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $visible = testCategory(['name' => 'Electronics', 'is_active' => true]);
    $trashed = testCategory(['name' => 'Deprecated', 'is_active' => true]);
    $trashed->delete();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/categories')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Electronics')
        ->assertJsonPath('data.1.name', 'Deprecated')
        ->assertJsonPath('meta.pagination.total', 2);
});

it('filters categories by active status', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    testCategory(['name' => 'Active Cat', 'is_active' => true]);
    testCategory(['name' => 'Inactive Cat', 'is_active' => false]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/categories?is_active=true')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active Cat');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/categories?is_active=false')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Inactive Cat');
});

it('creates a category with auto-generated slug', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Home & Kitchen',
            'description' => 'Home and kitchen essentials',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Home & Kitchen')
        ->assertJsonPath('data.slug', 'home-kitchen')
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('categories', [
        'name' => 'Home & Kitchen',
        'slug' => 'home-kitchen',
        'description' => 'Home and kitchen essentials',
        'is_active' => true,
    ]);
});

it('rejects duplicate slugs', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    testCategory(['name' => 'Old', 'slug' => 'existing']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Duplicate',
            'slug' => 'existing',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['slug']);
});

it('rejects a category without a name', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/categories', ['slug' => 'no-name'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('shows a single category to an admin', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = testCategory(['name' => 'Detailed Cat', 'is_active' => true]);

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Detailed Cat')
        ->assertJsonPath('data.is_active', true);
});

it('updates a category partially', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = testCategory(['name' => 'Old Name', 'is_active' => true]);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/admin/categories/{$category->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
});

it('allows updating a category to a new unique slug', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = testCategory(['name' => 'Original', 'slug' => 'original']);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/admin/categories/{$category->id}", ['slug' => 'new-slug'])
        ->assertOk()
        ->assertJsonPath('data.slug', 'new-slug');
});

it('deactivates and reactivates a category', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = testCategory(['name' => 'Seasonal', 'is_active' => true]);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->assertFalse($category->fresh()->is_active);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/categories/{$category->id}/activate")
        ->assertOk()
        ->assertJsonPath('data.is_active', true);

    $this->assertTrue($category->fresh()->is_active);
});

it('attaches and detaches parent categories', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $parent = testCategory(['name' => 'Parent', 'is_active' => true]);
    $child = testCategory(['name' => 'Child', 'is_active' => true]);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/admin/categories/{$child->id}", ['parent_id' => $parent->id])
        ->assertOk()
        ->assertJsonPath('data.parent.name', 'Parent');

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/categories/{$child->id}/deactivate")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/categories/{$child->id}/activate")
        ->assertOk()
        ->assertJsonPath('data.is_active', true);
});