import { apiClient } from './client';
import { USE_MOCK, PENDING_MODULES_USE_MOCK } from '@/lib/config';
import type { DataEnvelope, User } from '@/types';
import { mockState, delay } from '@/mock/store';

export const usersService = {
  async list(): Promise<User[]> {
    if (USE_MOCK || PENDING_MODULES_USE_MOCK) return delay([...mockState.users]);
    const { data } = await apiClient.get<DataEnvelope<User[]>>('/users');
    return data.data;
  },

  async get(id: string): Promise<User> {
    if (USE_MOCK || PENDING_MODULES_USE_MOCK) {
      const found = mockState.users.find((u) => u.id === id);
      if (!found) throw { response: { status: 404 } };
      return delay(found);
    }
    const { data } = await apiClient.get<DataEnvelope<User>>(`/users/${id}`);
    return data.data;
  },
};
