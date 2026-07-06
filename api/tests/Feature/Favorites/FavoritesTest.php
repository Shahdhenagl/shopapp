<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use App\Domain\Catalog\Models\Product;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Sanctum::actingAs(User::factory()->create());
});

it('returns an empty favorites list initially', function (): void {
    $response = $this->getJson('/api/v1/favorites', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonPath('data', []);
});

it('toggles a favorite on and off', function (): void {
    $product = Product::factory()->create();

    $response = $this->postJson('/api/v1/favorites', [
        'product_id' => $product->id,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect($response->json('data'))->toContain($product->id);

    // Toggling again removes it.
    $response = $this->postJson('/api/v1/favorites', [
        'product_id' => $product->id,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonPath('data', []);
});

it('clears all favorites', function (): void {
    $product = Product::factory()->create();

    $this->postJson('/api/v1/favorites', [
        'product_id' => $product->id,
    ], ['Accept' => 'application/json'])->assertStatus(200);

    $response = $this->deleteJson('/api/v1/favorites', [], ['Accept' => 'application/json']);

    $response->assertNoContent();

    // The list is empty afterwards.
    $this->getJson('/api/v1/favorites', ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJsonPath('data', []);
});
