import { USE_MOCK } from '@/lib/config';
import { apiClient } from './client';
import type { DataEnvelope, Order } from '@/types';
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

function buildFromOrders(
  orders: Order[],
  products: number,
  users: number,
): DashboardStats {
  const paid = orders.filter(
    (o) => o.payment_status === 'paid',
  );
  const revenue = paid.reduce((sum, o) => sum + o.total, 0);

  // Aggregate the last 7 days.
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
    // Real backend: a dedicated admin stats endpoint is assumed.
    const { data } = await apiClient.get<DataEnvelope<DashboardStats>>(
      '/admin/stats',
    );
    return data.data;
  },
};
