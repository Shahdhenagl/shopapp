import { adminClient } from './client';
import { USE_MOCK } from '@/lib/config';
import type { DataEnvelope, Order, OrderStatus, Paginated } from '@/types';
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
