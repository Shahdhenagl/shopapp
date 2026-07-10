<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Models\Order;

it('lists orders newest-first with admin detail', function (): void {
    Order::factory()->create(['status' => 'paid', 'payment_status' => 'paid']);

    $response = $this->getJson('/api/admin/v1/orders', adminHeaders());

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [['id', 'status', 'payment_status', 'total', 'allowed_transitions', 'items']],
    ]);
});

it('filters orders by status', function (): void {
    Order::factory()->create(['status' => 'paid', 'payment_status' => 'paid']);
    Order::factory()->create(['status' => 'delivered', 'payment_status' => 'paid']);

    $response = $this->getJson('/api/admin/v1/orders?status=delivered', adminHeaders());

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('status')->unique()->all())->toBe(['delivered']);
});

it('shows a single order', function (): void {
    $order = Order::factory()->create();

    $this->getJson("/api/admin/v1/orders/{$order->id}", adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.id', $order->id);
});

it('advances an order through a valid transition', function (): void {
    $order = Order::factory()->create(['status' => 'paid', 'payment_status' => 'paid']);

    $this->patchJson("/api/admin/v1/orders/{$order->id}", ['status' => 'shipped'], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'shipped');
});

it('rejects an illegal status transition', function (): void {
    $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'pending']);

    $this->patchJson("/api/admin/v1/orders/{$order->id}", ['status' => 'delivered'], adminHeaders())
        ->assertStatus(422);

    expect($order->fresh()->status)->toBe('pending');
});

it('settles payment when an order is marked paid', function (): void {
    $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'pending']);

    $this->patchJson("/api/admin/v1/orders/{$order->id}", ['status' => 'paid'], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.payment_status', 'paid');
});

it('refunds payment when an order is refunded', function (): void {
    $order = Order::factory()->create(['status' => 'delivered', 'payment_status' => 'paid']);

    $this->patchJson("/api/admin/v1/orders/{$order->id}", ['status' => 'refunded'], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.payment_status', 'refunded');
});

it('lets staff read and fulfil orders', function (): void {
    $staff = makeAdmin(\App\Domain\Admin\Models\AdminUser::ROLE_STAFF);
    $order = Order::factory()->create(['status' => 'paid', 'payment_status' => 'paid']);

    $this->getJson('/api/admin/v1/orders', adminHeaders($staff))->assertStatus(200);
    $this->patchJson("/api/admin/v1/orders/{$order->id}", ['status' => 'shipped'], adminHeaders($staff))
        ->assertStatus(200);
});

it('hides another tenant orders from the operator', function (): void {
    $other = makeOtherTenant();
    withTenant($other, fn () => Order::factory()->create());

    // Default-tenant admin sees none of the other tenant's orders.
    $response = $this->getJson('/api/admin/v1/orders', adminHeaders());

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});
