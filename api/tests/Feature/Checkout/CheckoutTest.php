<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Models\Order;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Sanctum::actingAs(User::factory()->create());
});

it('checks out a cash order and empties the cart', function (): void {
    $product = Product::factory()->create();

    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'size' => 'M',
        'color' => 4279371338,
        'quantity' => 1,
    ], ['Accept' => 'application/json'])->assertStatus(200);

    $response = $this->postJson('/api/v1/checkout', [
        'amount' => 1800,
        'currency' => 'EGP',
        'payment_method' => 'cash',
        'address' => [
            'address' => '12 Tahrir St',
            'city' => 'Cairo',
            'area' => 'Downtown',
            'branch' => 'Main',
        ],
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201);

    $id = $response->json('data.id');
    expect($id)->toBeString();
    expect($id)->toStartWith('MOD-');
    expect($response->json('data.amount'))->toBeNumeric();

    $cart = $this->getJson('/api/v1/cart', ['Accept' => 'application/json']);
    expect($cart->json('data.items'))->toBeEmpty();
});

it('checks out a credit card order and marks it paid', function (): void {
    $product = Product::factory()->create();

    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'size' => 'M',
        'color' => 4279371338,
        'quantity' => 1,
    ], ['Accept' => 'application/json'])->assertStatus(200);

    $response = $this->postJson('/api/v1/checkout', [
        'payment_method' => 'creditCard',
        'address' => [
            'address' => '12 Tahrir St',
            'city' => 'Cairo',
        ],
        'card' => [
            'payment_token' => 'tok_visa',
        ],
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201);

    $order = Order::query()->find($response->json('data.id'));
    expect($order)->not->toBeNull();
    expect($order->payment_status)->toBe(Order::PAYMENT_PAID);
});

it('rejects checkout when the cart is empty', function (): void {
    $response = $this->postJson('/api/v1/checkout', [
        'payment_method' => 'cash',
        'address' => [
            'address' => '12 Tahrir St',
            'city' => 'Cairo',
        ],
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422);
});
