import { apiClient } from './client';
import { USE_MOCK } from '@/lib/config';
import type { Category, DataEnvelope } from '@/types';
import { mockState, delay } from '@/mock/store';

export type CategoryInput = Category;

export const categoriesService = {
  async list(): Promise<Category[]> {
    if (USE_MOCK) {
      return delay(
        [...mockState.categories].sort(
          (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0),
        ),
      );
    }
    const { data } = await apiClient.get<DataEnvelope<Category[]>>(
      '/categories',
    );
    return data.data;
  },

  async create(input: CategoryInput): Promise<Category> {
    if (USE_MOCK) {
      if (mockState.categories.some((c) => c.id === input.id)) {
        throw { response: { status: 422, data: { message: 'Slug already exists' } } };
      }
      mockState.categories.push(input);
      return delay(input);
    }
    const { data } = await apiClient.post<DataEnvelope<Category>>(
      '/categories',
      input,
    );
    return data.data;
  },

  async update(id: string, input: CategoryInput): Promise<Category> {
    if (USE_MOCK) {
      const idx = mockState.categories.findIndex((c) => c.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      mockState.categories[idx] = input;
      return delay(input);
    }
    const { data } = await apiClient.put<DataEnvelope<Category>>(
      `/categories/${id}`,
      input,
    );
    return data.data;
  },

  async remove(id: string): Promise<void> {
    if (USE_MOCK) {
      mockState.categories = mockState.categories.filter((c) => c.id !== id);
      await delay(null, 200);
      return;
    }
    await apiClient.delete(`/categories/${id}`);
  },
};
