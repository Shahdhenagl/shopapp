<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use App\Domain\Catalog\Models\Product;
use Database\Seeders\PromoSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Sanctum::actingAs(User::factory()->create());
});

it('adds an item to the cart', function (): void {
    $product = Product::factory()->create();

    $response = $this->postJson('/api/v1/cart', [
        'product_id' => (string) $product->id,
        'size' => 'M',
        'color' => 4279371338,
        'quantity' => 2,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);

    $lineId = $product->id . '|M|4279371338';
    $items = collect($response->json('data.items'));

    expect($items->pluck('line_id'))->toContain($lineId);
});

it('merges quantity when the same line is added twice', function (): void {
    $product = Product::factory()->create();

    $payload = [
        'product_id' => (string) $product->id,
        'size' => 'M',
        'color' => 4279371338,
        'quantity' => 2,
    ];

    $this->postJson('/api/v1/cart', $payload, ['Accept' => 'application/json'])->assertStatus(200);
    $response = $this->postJson('/api/v1/cart', $payload, ['Accept' => 'application/json']);

    $response->assertStatus(200);

    $lineId = $product->id . '|M|4279371338';
    $line = collect($response->json('data.items'))->firstWhere('line_id', $lineId);

    expect($line)->not->toBeNull();
    expect($line['quantity'])->toBe(4);
});

it('applies a valid promo code', function (): void {
    $this->seed(PromoSeeder::class);

    $response = $this->postJson('/api/v1/cart/promo', [
        'code' => 'MODIST10',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonPath('fraction', 0.1);
    $response->assertJsonPath('code', 'MODIST10');
});

it('rejects an invalid promo code', function (): void {
    $this->seed(PromoSeeder::class);

    $response = $this->postJson('/api/v1/cart/promo', [
        'code' => 'NOPE',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422);
});
