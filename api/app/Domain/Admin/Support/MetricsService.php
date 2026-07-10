<?php

declare(strict_types=1);

namespace App\Domain\Admin\Support;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Models\PromoCode;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Models\Order;
use App\Domain\Checkout\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * §3.12 — dashboard KPIs. Every model here is tenant-scoped by its global scope,
 * so all figures are for the current tenant. Kept as straightforward aggregate
 * queries; swap for a materialized view if a tenant's order volume outgrows it.
 */
final class MetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $now = Carbon::now();
        $currency = (string) config('app.currency', 'EGP');

        return [
            'currency' => $currency,
            'revenue' => [
                'today' => $this->revenueSince($now->copy()->startOfDay()),
                'last_7_days' => $this->revenueSince($now->copy()->subDays(7)),
                'last_30_days' => $this->revenueSince($now->copy()->subDays(30)),
            ],
            'orders_by_status' => $this->ordersByStatus(),
            'totals' => [
                'orders' => Order::query()->count(),
                'products' => Product::query()->count(),
                'customers' => User::query()->count(),
            ],
            'new_customers_7_days' => User::query()
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->count(),
            'top_products' => $this->topProducts(),
            'low_stock' => $this->lowStock(),
            'promo_usage' => $this->promoUsage(),
            'sales_by_day' => $this->salesByDay(7),
        ];
    }

    private function revenueSince(Carbon $since): float
    {
        return round((float) Order::query()
            ->where('payment_status', Order::PAYMENT_PAID)
            ->where('created_at', '>=', $since)
            ->sum('amount'), 2);
    }

    /**
     * @return array<string, int>
     */
    private function ordersByStatus(): array
    {
        return Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v): int => (int) $v)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(): array
    {
        // OrderItem is not tenant-scoped itself (no tenant_id column), so it is
        // constrained to this tenant's orders via the tenant-scoped Order query.
        return OrderItem::query()
            ->select('product_id', 'name_snapshot', DB::raw('sum(quantity) as units'), DB::raw('sum(line_total) as revenue'))
            ->whereNotNull('product_id')
            ->whereIn('order_id', Order::query()->select('id'))
            ->groupBy('product_id', 'name_snapshot')
            ->orderByDesc('units')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'product_id' => (string) $row->product_id,
                'name' => $row->name_snapshot,
                'units' => (int) $row->units,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lowStock(int $threshold = 5): array
    {
        return Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->limit(10)
            ->get()
            ->map(fn (Product $p): array => [
                'id' => (string) $p->id,
                'name' => (string) $p->name,
                'stock' => (int) $p->stock,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function promoUsage(): array
    {
        return PromoCode::query()
            ->where('used_count', '>', 0)
            ->orderByDesc('used_count')
            ->limit(10)
            ->get()
            ->map(fn (PromoCode $p): array => [
                'code' => $p->code,
                'used_count' => (int) $p->used_count,
                'usage_limit' => $p->usage_limit !== null ? (int) $p->usage_limit : null,
            ])
            ->all();
    }

    /**
     * Paid revenue + order count per day for the last $days days, oldest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function salesByDay(int $days): array
    {
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        $rows = Order::query()
            ->select(
                DB::raw('date(created_at) as day'),
                DB::raw('count(*) as orders'),
                DB::raw("sum(case when payment_status = '" . Order::PAYMENT_PAID . "' then amount else 0 end) as revenue"),
            )
            ->where('created_at', '>=', $start)
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $orderCounts = Order::query()
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as orders'))
            ->where('created_at', '>=', $start)
            ->groupBy('day')
            ->pluck('orders', 'day');

        $series = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();

            $series[] = [
                'day' => $key,
                'label' => $date->format('D'),
                'revenue' => round((float) ($rows[$key] ?? 0), 2),
                'orders' => (int) ($orderCounts[$key] ?? 0),
            ];
        }

        return $series;
    }
}
