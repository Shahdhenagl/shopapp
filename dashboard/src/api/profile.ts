import { apiClient } from './client';
import { USE_MOCK } from '@/lib/config';
import type { DataEnvelope, User } from '@/types';
import { mockState, delay } from '@/mock/store';

export const profileService = {
  async me(): Promise<User> {
    if (USE_MOCK) return delay(mockState.users[0]);
    const { data } = await apiClient.get<DataEnvelope<User>>('/me');
    return data.data;
  },
};
