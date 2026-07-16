<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;

beforeEach(function (): void {
    $this->seed(CategorySeeder::class);
    $this->seed(ProductSeeder::class);
});

it('lists categories', function (): void {
    $response = $this->getJson('/api/v1/categories', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            ['id', 'parent_id', 'label_key', 'icon_key', 'image_url', 'sort_order'],
        ],
    ]);
});

// Regression: parent_id was commented out of CategoryResource and the endpoint
// was switched to ->paginate(10). Clients build the browse tree from parent_id
// over the full list, so either one silently flattens/truncates the catalog.
it('returns the whole tree with parent references, unpaginated', function (): void {
    $parent = Category::query()->create([
        'slug' => 'clothing', 'name' => ['en' => 'Clothing', 'ar' => 'ملابس'], 'sort_order' => 0,
    ]);
    Category::query()->create([
        'slug' => 'tees', 'parent_id' => $parent->id,
        'name' => ['en' => 'Tees', 'ar' => 'تيشيرت'], 'sort_order' => 1,
    ]);

    $response = $this->getJson('/api/v1/categories', ['Accept' => 'application/json'])
        ->assertStatus(200);

    $rows = collect($response->json('data'));

    // A child points at its parent's slug; a department points at nothing.
    expect($rows->firstWhere('id', 'tees')['parent_id'])->toBe('clothing');
    expect($rows->firstWhere('id', 'clothing')['parent_id'])->toBeNull();

    // Every seeded category is present — no page of 10.
    expect($rows)->toHaveCount(Category::query()->count());
    expect($response->json('meta.per_page'))->toBeNull();
});

it('lists products with the exact expected shape', function (): void {
    $response = $this->getJson('/api/v1/products', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            [
                'id',
                'name',
                'style',
                'description',
                'price',
                'currency',
                'images',
                'colors',
                'sizes',
                'category_id',
                'rating',
                'is_newest',
            ],
        ],
    ]);

    $firstColor = $response->json('data.0.colors.0');
    expect($firstColor)->toBeString();
    expect($firstColor)->toStartWith('#');
});

it('shows a single product', function (): void {
    $product = Product::query()->first();

    $response = $this->getJson('/api/v1/products/' . $product->id, ['Accept' => 'application/json']);

    $response->assertStatus(200);
});

it('returns 404 for an unknown product', function (): void {
    $response = $this->getJson('/api/v1/products/00000000-0000-0000-0000-000000000000', ['Accept' => 'application/json']);

    $response->assertStatus(404);
});
