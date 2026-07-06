<?php

declare(strict_types=1);

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
            ['id', 'label_key', 'icon_key'],
        ],
    ]);
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
