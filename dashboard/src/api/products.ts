import { apiClient } from './client';
import { USE_MOCK } from '@/lib/config';
import type { DataEnvelope, Product } from '@/types';
import { mockState, delay, nextId } from '@/mock/store';

export interface ProductFilters {
  category?: string;
  q?: string;
}

export type ProductInput = Omit<Product, 'id'>;

export const productsService = {
  async list(filters: ProductFilters = {}): Promise<Product[]> {
    if (USE_MOCK) {
      let items = [...mockState.products];
      if (filters.category) {
        items = items.filter((p) => p.category_id === filters.category);
      }
      if (filters.q) {
        const q = filters.q.toLowerCase();
        items = items.filter(
          (p) =>
            p.name.toLowerCase().includes(q) ||
            p.style.toLowerCase().includes(q),
        );
      }
      return delay(items);
    }
    const { data } = await apiClient.get<DataEnvelope<Product[]>>('/products', {
      params: { category: filters.category, q: filters.q },
    });
    return data.data;
  },

  async get(id: string): Promise<Product> {
    if (USE_MOCK) {
      const found = mockState.products.find((p) => p.id === id);
      if (!found) throw { response: { status: 404 } };
      return delay(found);
    }
    const { data } = await apiClient.get<DataEnvelope<Product>>(
      `/products/${id}`,
    );
    return data.data;
  },

  async create(input: ProductInput): Promise<Product> {
    if (USE_MOCK) {
      const product: Product = { ...input, id: nextId('p', mockState.products) };
      mockState.products.push(product);
      return delay(product);
    }
    const { data } = await apiClient.post<DataEnvelope<Product>>(
      '/products',
      input,
    );
    return data.data;
  },

  async update(id: string, input: ProductInput): Promise<Product> {
    if (USE_MOCK) {
      const idx = mockState.products.findIndex((p) => p.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      mockState.products[idx] = { ...input, id };
      return delay(mockState.products[idx]);
    }
    const { data } = await apiClient.put<DataEnvelope<Product>>(
      `/products/${id}`,
      input,
    );
    return data.data;
  },

  async remove(id: string): Promise<void> {
    if (USE_MOCK) {
      mockState.products = mockState.products.filter((p) => p.id !== id);
      await delay(null, 200);
      return;
    }
    await apiClient.delete(`/products/${id}`);
  },
};
