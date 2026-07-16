<?php

declare(strict_types=1);

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Auth\Models\User;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Models\Order;

/**
 * An in-store sale: the cashier picks products directly, so there's no cart and
 * no app account required. Prices/stock are resolved server-side.
 */
function posProduct(int $stock = 10, float $price = 100): Product
{
    Category::query()->firstOrCreate(
        ['slug' => 'tees'],
        ['name' => ['en' => 'Tees', 'ar' => 'تيشيرت'], 'sort_order' => 0],
    );

    return Product::factory()->create([
        'category_id' => 'tees',
        'price' => $price,
        'stock' => $stock,
        'status' => 'active',
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function posPayload(Product $product, array $overrides = [], int $quantity = 2): array
{
    return array_merge([
        'items' => [[
            'product_id' => $product->id,
            'size' => 'M',
            'color_value' => 4279371338,
            'quantity' => $quantity,
        ]],
        'payment_method' => 'cash',
    ], $overrides);
}

it('records a walk-in cash sale as paid and deducts stock', function (): void {
    $product = posProduct(stock: 10, price: 100);

    $response = $this->postJson('/api/admin/v1/orders', posPayload($product), adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.channel', 'pos');
    $response->assertJsonPath('data.status', 'paid');
    $response->assertJsonPath('data.payment_status', 'paid');
    $response->assertJsonPath('data.total', 200.0);
    // A walk-in has no account behind it.
    $response->assertJsonPath('data.user_id', null);

    expect($product->fresh()->stock)->toBe(8);
});

it('prices the sale from the catalog, ignoring any client totals', function (): void {
    $product = posProduct(stock: 5, price: 250);

    $response = $this->postJson('/api/admin/v1/orders', posPayload($product, [
        // Nonsense the client should never be able to impose.
        'subtotal' => 1,
        'total' => 1,
    ], quantity: 2), adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.subtotal', 500.0);
    $response->assertJsonPath('data.total', 500.0);
});

it('blocks the sale when stock is insufficient and leaves stock untouched', function (): void {
    $product = posProduct(stock: 1);

    $this->postJson('/api/admin/v1/orders', posPayload($product, quantity: 3), adminHeaders())
        ->assertStatus(422);

    expect($product->fresh()->stock)->toBe(1);
    expect(Order::query()->count())->toBe(0);
});

it('records a deferred sale as unpaid but still deducts stock', function (): void {
    $product = posProduct(stock: 4);

    $response = $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payment_method' => 'deferred',
    ]), adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'pending');
    $response->assertJsonPath('data.payment_status', 'pending');

    expect($product->fresh()->stock)->toBe(2);
});

it('links the sale to a registered customer when one is picked', function (): void {
    $product = posProduct();
    $customer = User::factory()->create();

    $response = $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'user_id' => $customer->id,
    ]), adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.user_id', (string) $customer->id);
});

it('keeps the walk-in name/phone the cashier typed', function (): void {
    $product = posProduct();

    $response = $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'customer_name' => 'Walk-in Sara',
        'customer_phone' => '+201000000000',
    ]), adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.customer_name', 'Walk-in Sara');
    $response->assertJsonPath('data.customer_phone', '+201000000000');
});

it('rejects an empty sale', function (): void {
    $this->postJson('/api/admin/v1/orders', [
        'items' => [],
        'payment_method' => 'cash',
    ], adminHeaders())->assertStatus(422);
});

it('rejects an unsupported payment method', function (): void {
    $product = posProduct();

    $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payment_method' => 'bitcoin',
    ]), adminHeaders())->assertStatus(422);
});

it('lets staff (not just admins) ring up a sale', function (): void {
    $product = posProduct();
    $staff = makeAdmin(AdminUser::ROLE_STAFF);

    $this->postJson('/api/admin/v1/orders', posPayload($product), adminHeaders($staff))
        ->assertStatus(201);
});

it('rejects an unauthenticated sale', function (): void {
    $product = posProduct();

    $this->postJson('/api/admin/v1/orders', posPayload($product), ['Accept' => 'application/json'])
        ->assertStatus(401);
});
