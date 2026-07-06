<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductReview;

it('returns an empty array for a product with no reviews', function (): void {
    $product = Product::factory()->create();

    $response = $this->getJson("/api/v1/products/{$product->id}/reviews", ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});

it('lists reviews newest-first with the expected shape', function (): void {
    $product = Product::factory()->create();

    ProductReview::create([
        'product_id' => $product->id,
        'author_name' => 'Sara',
        'rating' => 5,
        'comment' => 'Lovely fit and fabric.',
    ]);

    $response = $this->getJson("/api/v1/products/{$product->id}/reviews", ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            ['id', 'author_name', 'rating', 'comment', 'created_at'],
        ],
    ]);
    $response->assertJsonPath('data.0.rating', 5);
});

it('returns 404 for reviews of an unknown product', function (): void {
    $response = $this->getJson('/api/v1/products/does-not-exist/reviews', ['Accept' => 'application/json']);

    $response->assertStatus(404);
});

it('requires auth to write a review', function (): void {
    $product = Product::factory()->create();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
        'rating' => 5,
        'comment' => 'Nice',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(401);
});

it('creates a review attributed to the user and recomputes the product rating', function (): void {
    $product = Product::factory()->create(['rating' => 0]);
    $user = User::factory()->create(['name' => 'Omar']);
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
        'rating' => 4,
        'comment' => 'Great quality.',
    ], [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.author_name', 'Omar');
    $response->assertJsonPath('data.rating', 4);

    expect((float) $product->fresh()->rating)->toBe(4.0);
});
