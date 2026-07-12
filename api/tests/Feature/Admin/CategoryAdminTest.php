<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;

it('returns the category tree with leaf + product-count flags', function (): void {
    $parent = Category::query()->create(['slug' => 'clothing', 'name' => ['en' => 'Clothing', 'ar' => 'ملابس'], 'sort_order' => 0]);
    Category::query()->create(['slug' => 'tees', 'parent_id' => (string) $parent->id, 'name' => ['en' => 'Tees', 'ar' => 'تيشيرت'], 'sort_order' => 0]);

    $response = $this->getJson('/api/admin/v1/categories', adminHeaders());

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [['id', 'slug', 'parent_id', 'is_leaf', 'product_count', 'children']],
    ]);
});

it('creates a top-level department', function (): void {
    $this->postJson('/api/admin/v1/categories', [
        'name' => ['en' => 'Electronics', 'ar' => 'إلكترونيات'],
    ], adminHeaders())->assertStatus(201)
        ->assertJsonPath('data.parent_id', null);
});

it('creates a child under a parent', function (): void {
    $parent = Category::query()->create(['slug' => 'home', 'name' => ['en' => 'Home', 'ar' => 'منزل'], 'sort_order' => 0]);

    $this->postJson('/api/admin/v1/categories', [
        'name' => ['en' => 'Kitchen', 'ar' => 'مطبخ'],
        'parent_id' => (string) $parent->id,
    ], adminHeaders())->assertStatus(201)
        ->assertJsonPath('data.parent_id', (string) $parent->id);
});

it('prevents a reparent cycle (§7.4)', function (): void {
    $a = Category::query()->create(['slug' => 'a', 'name' => ['en' => 'A', 'ar' => 'أ'], 'sort_order' => 0]);
    $b = Category::query()->create(['slug' => 'b', 'parent_id' => $a->id, 'name' => ['en' => 'B', 'ar' => 'ب'], 'sort_order' => 0]);

    // Making A a child of its own descendant B must be rejected.
    $this->patchJson("/api/admin/v1/categories/{$a->id}", ['parent_id' => (string) $b->id], adminHeaders())
        ->assertStatus(422);
});

it('blocks deleting a category that still has children (§7.5)', function (): void {
    $parent = Category::query()->create(['slug' => 'p', 'name' => ['en' => 'P', 'ar' => 'ب'], 'sort_order' => 0]);
    Category::query()->create(['slug' => 'c', 'parent_id' => (string) $parent->id, 'name' => ['en' => 'C', 'ar' => 'ج'], 'sort_order' => 0]);

    $this->deleteJson("/api/admin/v1/categories/{$parent->id}", [], adminHeaders())->assertStatus(422);
});

it('blocks deleting a category that still has products', function (): void {
    $cat = Category::query()->create(['slug' => 'withprod', 'name' => ['en' => 'WP', 'ar' => 'م'], 'sort_order' => 0]);
    Product::factory()->create(['category_id' => 'withprod']);

    $this->deleteJson("/api/admin/v1/categories/{$cat->id}", [], adminHeaders())->assertStatus(422);
});

it('soft-deletes an empty category', function (): void {
    $cat = Category::query()->create(['slug' => 'empty', 'name' => ['en' => 'E', 'ar' => 'ف'], 'sort_order' => 0]);

    $this->deleteJson("/api/admin/v1/categories/{$cat->id}", [], adminHeaders())->assertNoContent();
    $this->assertSoftDeleted('categories', ['id' => $cat->id]);
});

it('cascade-deletes a subtree when asked', function (): void {
    $parent = Category::query()->create(['slug' => 'root', 'name' => ['en' => 'R', 'ar' => 'ج'], 'sort_order' => 0]);
    $child = Category::query()->create(['slug' => 'child', 'parent_id' => (string) $parent->id, 'name' => ['en' => 'C', 'ar' => 'ط'], 'sort_order' => 0]);

    $this->deleteJson("/api/admin/v1/categories/{$parent->id}?cascade=1", [], adminHeaders())->assertNoContent();
    $this->assertSoftDeleted('categories', ['id' => $parent->id]);
    $this->assertSoftDeleted('categories', ['id' => $child->id]);
});
