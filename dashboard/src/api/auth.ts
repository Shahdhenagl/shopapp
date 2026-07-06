import { apiClient } from './client';
import { USE_MOCK } from '@/lib/config';
import type { AuthResponse } from '@/types';
import { mockState, delay } from '@/mock/store';
import { MOCK_ADMIN } from '@/mock/data';

export interface LoginPayload {
  email: string;
  password: string;
}

/**
 * Auth responses are FLAT (NOT data-wrapped) per §4.2:
 *   { token, user }
 */
export const authService = {
  async login(payload: LoginPayload): Promise<AuthResponse> {
    if (USE_MOCK) {
      if (
        payload.email.trim().toLowerCase() === MOCK_ADMIN.email &&
        payload.password === MOCK_ADMIN.password
      ) {
        const user = mockState.users[0];
        return delay({ token: `mock|${Date.now()}`, user });
      }
      await delay(null, 300);
      throw { response: { status: 401, data: { message: 'Invalid credentials' } } };
    }
    const { data } = await apiClient.post<AuthResponse>('/auth/login', payload);
    return data;
  },

  async logout(): Promise<void> {
    if (USE_MOCK) {
      await delay(null, 150);
      return;
    }
    await apiClient.post('/auth/logout');
  },
};
