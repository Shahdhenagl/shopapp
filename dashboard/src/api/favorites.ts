import { apiClient } from './client';
import { USE_MOCK } from '@/lib/config';
import type { DataEnvelope } from '@/types';
import { delay } from '@/mock/store';

// In-mock favorites are simple ID lists per §4.3.
let mockFavorites: string[] = ['p1', 'p3'];

export const favoritesService = {
  async list(): Promise<string[]> {
    if (USE_MOCK) return delay([...mockFavorites]);
    const { data } = await apiClient.get<DataEnvelope<string[]>>('/favorites');
    return data.data;
  },

  async toggle(productId: string): Promise<string[]> {
    if (USE_MOCK) {
      mockFavorites = mockFavorites.includes(productId)
        ? mockFavorites.filter((id) => id !== productId)
        : [...mockFavorites, productId];
      return delay([...mockFavorites]);
    }
    const { data } = await apiClient.post<DataEnvelope<string[]>>('/favorites', {
      product_id: productId,
    });
    return data.data;
  },

  async clear(): Promise<string[]> {
    if (USE_MOCK) {
      mockFavorites = [];
      return delay([]);
    }
    const { data } = await apiClient.delete<DataEnvelope<string[]>>(
      '/favorites',
    );
    return data.data;
  },
};
