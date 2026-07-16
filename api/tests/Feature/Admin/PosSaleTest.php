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
 * Payments default to a single cash tender covering the whole sale.
 *
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
        'payments' => [
            ['method' => 'cash', 'amount' => (float) $product->price * $quantity],
        ],
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
    $product = posProduct(stock: 4, price: 100);

    $response = $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payments' => [['method' => 'deferred', 'amount' => 200]],
    ]), adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'pending');
    $response->assertJsonPath('data.payment_status', 'pending');

    expect($product->fresh()->stock)->toBe(2);
});

// --- Split tenders ----------------------------------------------------------

it('splits one sale across several payment methods', function (): void {
    $product = posProduct(stock: 5, price: 100); // 2 × 100 = 200

    $response = $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payments' => [
            ['method' => 'cash', 'amount' => 120],
            ['method' => 'instapay', 'amount' => 50],
            ['method' => 'wallet', 'amount' => 30],
        ],
    ]), adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.total', 200.0);
    // Summarised as 'split'; the breakdown rides along.
    $response->assertJsonPath('data.payment_method', 'split');
    $response->assertJsonPath('data.payment_status', 'paid');
    expect($response->json('data.payments'))->toBe([
        ['method' => 'cash', 'amount' => 120.0],
        ['method' => 'instapay', 'amount' => 50.0],
        ['method' => 'wallet', 'amount' => 30.0],
    ]);
});

it('keeps the single method on the order when the sale is not split', function (): void {
    $product = posProduct(price: 100);

    $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payments' => [['method' => 'instapay', 'amount' => 200]],
    ]), adminHeaders())
        ->assertStatus(201)
        ->assertJsonPath('data.payment_method', 'instapay');
});

it('leaves the sale unpaid when part of it is deferred', function (): void {
    $product = posProduct(stock: 5, price: 100); // total 200

    $response = $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payments' => [
            ['method' => 'cash', 'amount' => 150],
            ['method' => 'deferred', 'amount' => 50], // 50 left on the tab
        ],
    ]), adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.payment_status', 'pending');
    $response->assertJsonPath('data.status', 'pending');
});

it('rejects tenders that do not add up to the sale total', function (): void {
    $product = posProduct(stock: 5, price: 100); // total 200

    $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payments' => [['method' => 'cash', 'amount' => 150]], // 50 short
    ]), adminHeaders())->assertStatus(422);

    // Nothing was recorded and stock is untouched.
    expect(Order::query()->count())->toBe(0);
    expect($product->fresh()->stock)->toBe(5);
});

it('rejects an overpayment', function (): void {
    $product = posProduct(stock: 5, price: 100); // total 200

    $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payments' => [['method' => 'cash', 'amount' => 500]],
    ]), adminHeaders())->assertStatus(422);
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
        'payments' => [['method' => 'cash', 'amount' => 10]],
    ], adminHeaders())->assertStatus(422);
});

it('rejects an unsupported payment method', function (): void {
    $product = posProduct();

    $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payments' => [['method' => 'bitcoin', 'amount' => 200]],
    ]), adminHeaders())->assertStatus(422);
});

it('rejects a sale with no tenders at all', function (): void {
    $product = posProduct();

    $this->postJson('/api/admin/v1/orders', posPayload($product, [
        'payments' => [],
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
