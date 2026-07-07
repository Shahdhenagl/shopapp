import { apiClient } from './client';
import { USE_MOCK, PENDING_MODULES_USE_MOCK } from '@/lib/config';
import type { DataEnvelope, PromoCode } from '@/types';
import { mockState, delay, nextId } from '@/mock/store';

export type PromoInput = Omit<PromoCode, 'id' | 'used_count'> & {
  used_count?: number;
};

export const promosService = {
  async list(): Promise<PromoCode[]> {
    if (USE_MOCK || PENDING_MODULES_USE_MOCK) return delay([...mockState.promos]);
    const { data } = await apiClient.get<DataEnvelope<PromoCode[]>>('/promos');
    return data.data;
  },

  async create(input: PromoInput): Promise<PromoCode> {
    if (USE_MOCK || PENDING_MODULES_USE_MOCK) {
      const promo: PromoCode = {
        ...input,
        code: input.code.toUpperCase(),
        used_count: input.used_count ?? 0,
        id: nextId('pr', mockState.promos),
      };
      mockState.promos.push(promo);
      return delay(promo);
    }
    const { data } = await apiClient.post<DataEnvelope<PromoCode>>(
      '/promos',
      input,
    );
    return data.data;
  },

  async update(id: string, input: PromoInput): Promise<PromoCode> {
    if (USE_MOCK || PENDING_MODULES_USE_MOCK) {
      const idx = mockState.promos.findIndex((p) => p.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      mockState.promos[idx] = {
        ...mockState.promos[idx],
        ...input,
        code: input.code.toUpperCase(),
        id,
      };
      return delay(mockState.promos[idx]);
    }
    const { data } = await apiClient.put<DataEnvelope<PromoCode>>(
      `/promos/${id}`,
      input,
    );
    return data.data;
  },

  async toggleActive(id: string, active: boolean): Promise<PromoCode> {
    if (USE_MOCK || PENDING_MODULES_USE_MOCK) {
      const idx = mockState.promos.findIndex((p) => p.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      mockState.promos[idx] = { ...mockState.promos[idx], active };
      return delay(mockState.promos[idx]);
    }
    const { data } = await apiClient.patch<DataEnvelope<PromoCode>>(
      `/promos/${id}`,
      { active },
    );
    return data.data;
  },

  async remove(id: string): Promise<void> {
    if (USE_MOCK || PENDING_MODULES_USE_MOCK) {
      mockState.promos = mockState.promos.filter((p) => p.id !== id);
      await delay(null, 200);
      return;
    }
    await apiClient.delete(`/promos/${id}`);
  },
};
