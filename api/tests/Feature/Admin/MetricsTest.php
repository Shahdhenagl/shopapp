<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Models\Order;

it('returns the KPI snapshot shape', function (): void {
    $response = $this->getJson('/api/admin/v1/metrics', adminHeaders());

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'currency',
            'revenue' => ['today', 'last_7_days', 'last_30_days'],
            'orders_by_status',
            'totals' => ['orders', 'products', 'customers'],
            'new_customers_7_days',
            'top_products',
            'low_stock',
            'promo_usage',
            'sales_by_day',
        ],
    ]);
});

it('counts only paid revenue', function (): void {
    Order::factory()->create(['payment_status' => 'paid', 'amount' => 1000]);
    Order::factory()->create(['payment_status' => 'pending', 'amount' => 500]);

    $response = $this->getJson('/api/admin/v1/metrics', adminHeaders());

    expect((float) $response->json('data.revenue.last_30_days'))->toBe(1000.0);
});

it('reports totals only for the operator tenant', function (): void {
    Order::factory()->count(2)->create();
    $other = makeOtherTenant();
    withTenant($other, fn () => Order::factory()->count(3)->create());

    $response = $this->getJson('/api/admin/v1/metrics', adminHeaders());

    expect($response->json('data.totals.orders'))->toBe(2);
});

it('groups orders by status', function (): void {
    Order::factory()->create(['status' => 'paid', 'payment_status' => 'paid']);
    Order::factory()->create(['status' => 'shipped', 'payment_status' => 'paid']);

    $response = $this->getJson('/api/admin/v1/metrics', adminHeaders());

    $byStatus = $response->json('data.orders_by_status');
    expect($byStatus)->toHaveKey('paid');
    expect($byStatus)->toHaveKey('shipped');
});
