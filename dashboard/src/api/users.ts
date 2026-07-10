import { adminClient } from './client';
import { USE_MOCK } from '@/lib/config';
import type { DataEnvelope, Paginated, User } from '@/types';
import { mockState, delay } from '@/mock/store';

export const usersService = {
  async list(): Promise<User[]> {
    if (USE_MOCK) return delay([...mockState.users]);
    const { data } = await adminClient.get<Paginated<User>>('/customers', {
      params: { per_page: 100 },
    });
    return data.data;
  },

  async get(id: string): Promise<User> {
    if (USE_MOCK) {
      const found = mockState.users.find((u) => u.id === id);
      if (!found) throw { response: { status: 404 } };
      return delay(found);
    }
    const { data } = await adminClient.get<DataEnvelope<User>>(
      `/customers/${id}`,
    );
    return data.data;
  },

  async setStatus(id: string, status: 'active' | 'suspended'): Promise<User> {
    if (USE_MOCK) {
      const found = mockState.users.find((u) => u.id === id);
      if (!found) throw { response: { status: 404 } };
      return delay(found);
    }
    const { data } = await adminClient.patch<DataEnvelope<User>>(
      `/customers/${id}`,
      { status },
    );
    return data.data;
  },
};
