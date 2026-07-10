import { USE_MOCK } from '@/lib/config';
import { adminClient } from './client';
import type { DataEnvelope, Order, Paginated } from '@/types';
import { mockState, delay } from '@/mock/store';

export interface DashboardStats {
  products: number;
  orders: number;
  revenue: number;
  users: number;
  currency: string;
  recentOrders: Order[];
  salesByDay: { day: string; revenue: number; orders: number }[];
}

// Shape returned by GET /admin/v1/metrics (MetricsService::snapshot).
interface MetricsResponse {
  currency: string;
  revenue: { today: number; last_7_days: number; last_30_days: number };
  totals: { orders: number; products: number; customers: number };
  sales_by_day: { day: string; label: string; revenue: number; orders: number }[];
}

function buildFromOrders(
  orders: Order[],
  products: number,
  users: number,
): DashboardStats {
  const paid = orders.filter((o) => o.payment_status === 'paid');
  const revenue = paid.reduce((sum, o) => sum + o.total, 0);

  const days: { day: string; revenue: number; orders: number }[] = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0, 10);
    const label = d.toLocaleDateString('en-GB', { weekday: 'short' });
    const dayOrders = orders.filter((o) => o.created_at.slice(0, 10) === key);
    days.push({
      day: label,
      orders: dayOrders.length,
      revenue: dayOrders
        .filter((o) => o.payment_status === 'paid')
        .reduce((s, o) => s + o.total, 0),
    });
  }

  return {
    products,
    orders: orders.length,
    revenue,
    users,
    currency: orders[0]?.currency ?? 'EGP',
    recentOrders: [...orders]
      .sort(
        (a, b) =>
          new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
      )
      .slice(0, 5),
    salesByDay: days,
  };
}

export const dashboardService = {
  async stats(): Promise<DashboardStats> {
    if (USE_MOCK) {
      return delay(
        buildFromOrders(
          mockState.orders,
          mockState.products.length,
          mockState.users.length,
        ),
      );
    }

    // Real backend: KPIs come from /metrics; the recent-orders table is the
    // first page of /orders (newest first).
    const [metricsRes, ordersRes] = await Promise.all([
      adminClient.get<DataEnvelope<MetricsResponse>>('/metrics'),
      adminClient.get<Paginated<Order>>('/orders', { params: { per_page: 5 } }),
    ]);

    const m = metricsRes.data.data;

    return {
      products: m.totals.products,
      orders: m.totals.orders,
      revenue: m.revenue.last_30_days,
      users: m.totals.customers,
      currency: m.currency,
      recentOrders: ordersRes.data.data,
      salesByDay: m.sales_by_day.map((row) => ({
        day: row.label,
        revenue: row.revenue,
        orders: row.orders,
      })),
    };
  },
};
