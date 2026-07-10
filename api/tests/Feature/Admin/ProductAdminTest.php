<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;

function leafCategory(string $slug = 'tshirt'): Category
{
    return Category::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => ['en' => 'Tees', 'ar' => 'تيشيرت'], 'sort_order' => 0],
    );
}

it('creates a product with stock and status', function (): void {
    leafCategory();

    $response = $this->postJson('/api/admin/v1/products', [
        'name' => ['en' => 'Blender', 'ar' => 'خلاط'],
        'price' => 1200,
        'category_id' => 'tshirt',
        'status' => 'hidden',
        'stock' => 8,
        'images' => [],
        'sizes' => [],
        'colors' => [],
    ], adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'hidden');
    $response->assertJsonPath('data.stock', 8);
});

it('allows a variant-less product (no sizes/colors)', function (): void {
    leafCategory();

    $this->postJson('/api/admin/v1/products', [
        'name' => ['en' => 'Book', 'ar' => 'كتاب'],
        'price' => 100,
        'category_id' => 'tshirt',
        'images' => [], 'sizes' => [], 'colors' => [],
    ], adminHeaders())->assertStatus(201)
        ->assertJsonPath('data.sizes', [])
        ->assertJsonPath('data.colors', []);
});

it('refuses to assign a product to a non-leaf category (§7.1)', function (): void {
    $parent = Category::query()->create(['slug' => 'dept', 'name' => ['en' => 'Dept', 'ar' => 'قسم'], 'sort_order' => 0]);
    Category::query()->create(['slug' => 'leaf', 'parent_id' => $parent->id, 'name' => ['en' => 'Leaf', 'ar' => 'ورقة'], 'sort_order' => 0]);

    $this->postJson('/api/admin/v1/products', [
        'name' => ['en' => 'X', 'ar' => 'س'],
        'price' => 10,
        'category_id' => 'dept', // a branch, not a leaf
        'images' => [], 'sizes' => [], 'colors' => [],
    ], adminHeaders())->assertStatus(422);
});

it('refuses an unknown category', function (): void {
    $this->postJson('/api/admin/v1/products', [
        'name' => ['en' => 'X', 'ar' => 'س'],
        'price' => 10,
        'category_id' => 'nope',
        'images' => [], 'sizes' => [], 'colors' => [],
    ], adminHeaders())->assertStatus(422);
});

it('updates stock and status on an existing product', function (): void {
    $product = Product::factory()->create(['stock' => 0, 'status' => 'active']);

    $this->patchJson("/api/admin/v1/products/{$product->id}", ['stock' => 3, 'status' => 'hidden'], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.stock', 3)
        ->assertJsonPath('data.status', 'hidden');
});

it('filters the admin product list by status', function (): void {
    Product::factory()->create(['status' => 'active']);
    Product::factory()->create(['status' => 'hidden']);

    $response = $this->getJson('/api/admin/v1/products?status=hidden', adminHeaders());

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('status')->unique()->all())->toBe(['hidden']);
});

it('returns low-stock active products under the threshold', function (): void {
    Product::factory()->create(['status' => 'active', 'stock' => 2]);
    Product::factory()->create(['status' => 'active', 'stock' => 50]);
    Product::factory()->create(['status' => 'hidden', 'stock' => 0]); // hidden excluded

    $response = $this->getJson('/api/admin/v1/inventory/low-stock?threshold=5', adminHeaders());

    $response->assertStatus(200);
    $stocks = collect($response->json('data'))->pluck('stock');
    expect($stocks)->toContain(2);
    expect($stocks->max())->toBeLessThanOrEqual(5);
});
