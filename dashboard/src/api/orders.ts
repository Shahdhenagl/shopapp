import { adminClient } from './client';
import { USE_MOCK } from '@/lib/config';
import type {
  DataEnvelope,
  Order,
  OrderStatus,
  Paginated,
  PosSaleInput,
} from '@/types';
import { mockState, delay } from '@/mock/store';

export const ordersService = {
  async list(status?: OrderStatus): Promise<Order[]> {
    if (USE_MOCK) {
      let items = [...mockState.orders].sort(
        (a, b) =>
          new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
      );
      if (status) items = items.filter((o) => o.status === status);
      return delay(items);
    }
    const { data } = await adminClient.get<Paginated<Order>>('/orders', {
      params: { status: status || undefined, per_page: 100 },
    });
    return data.data;
  },

  async get(id: string): Promise<Order> {
    if (USE_MOCK) {
      const found = mockState.orders.find((o) => o.id === id);
      if (!found) throw { response: { status: 404 } };
      return delay(found);
    }
    const { data } = await adminClient.get<DataEnvelope<Order>>(`/orders/${id}`);
    return data.data;
  },

  /** Ring up an in-store sale. Totals + stock are resolved server-side. */
  async createPosSale(input: PosSaleInput): Promise<Order> {
    if (USE_MOCK) {
      const total = input.payments.reduce((s, p) => s + p.amount, 0);
      const unpaid = input.payments.some((p) => p.method === 'deferred');
      const order: Order = {
        id: `POS-${Math.random().toString(36).slice(2, 8).toUpperCase()}`,
        user_id: input.user_id ? String(input.user_id) : null,
        user_name: input.customer_name ?? 'Walk-in',
        channel: 'pos',
        customer_name: input.customer_name ?? null,
        customer_phone: input.customer_phone ?? null,
        status: unpaid ? 'pending' : 'paid',
        payment_status: unpaid ? 'pending' : 'paid',
        payment_method:
          input.payments.length === 1 ? input.payments[0].method : 'split',
        payments: input.payments,
        subtotal: total,
        discount: 0,
        total,
        currency: 'EGP',
        promo_code: input.promo_code ?? null,
        items: [],
        created_at: new Date().toISOString(),
      };
      mockState.orders.unshift(order);
      return delay(order);
    }
    const { data } = await adminClient.post<DataEnvelope<Order>>(
      '/orders',
      input,
    );
    return data.data;
  },

  async updateStatus(id: string, status: OrderStatus): Promise<Order> {
    if (USE_MOCK) {
      const idx = mockState.orders.findIndex((o) => o.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      mockState.orders[idx] = { ...mockState.orders[idx], status };
      return delay(mockState.orders[idx]);
    }
    const { data } = await adminClient.patch<DataEnvelope<Order>>(
      `/orders/${id}`,
      { status },
    );
    return data.data;
  },
};
