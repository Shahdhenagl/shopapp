<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductReview;

it('hides non-active products from the storefront list', function (): void {
    $active = Product::factory()->create(['status' => 'active']);
    $hidden = Product::factory()->create(['status' => 'hidden']);

    $response = $this->getJson('/api/v1/products', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($active->id);
    expect($ids)->not->toContain($hidden->id);
});

it('returns 404 for a hidden product detail', function (): void {
    $hidden = Product::factory()->create(['status' => 'hidden']);

    $this->getJson("/api/v1/products/{$hidden->id}", ['Accept' => 'application/json'])
        ->assertStatus(404);
});

it('shows only approved reviews on the storefront', function (): void {
    $product = Product::factory()->create();

    ProductReview::query()->create([
        'product_id' => $product->id, 'author_name' => 'Approved',
        'rating' => 5, 'comment' => 'Great', 'status' => 'approved',
    ]);
    ProductReview::query()->create([
        'product_id' => $product->id, 'author_name' => 'Pending',
        'rating' => 1, 'comment' => 'Hidden', 'status' => 'pending',
    ]);

    $response = $this->getJson("/api/v1/products/{$product->id}/reviews", ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $authors = collect($response->json('data'))->pluck('author_name');
    expect($authors)->toContain('Approved');
    expect($authors)->not->toContain('Pending');
});
