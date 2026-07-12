import { adminClient } from './client';
import { USE_MOCK } from '@/lib/config';
import { delay } from '@/mock/store';
import type { AdminBanner, AdminBannerInput, DataEnvelope } from '@/types';

// In-memory fixtures for mock mode (USE_MOCK). Real path uses the admin API.
let mockBanners: AdminBanner[] = [
  {
    id: 'b1',
    image_url: 'https://picsum.photos/seed/modisthero/960/600',
    title: 'Summer Collection',
    subtitle: 'Up to 40% off',
    cta_text: 'Shop now',
    link_type: 'category',
    link_value: 'tshirt',
    sort_order: 1,
    is_active: true,
    starts_at: null,
    ends_at: null,
  },
];

function nextId(): string {
  let max = 0;
  for (const b of mockBanners) {
    const n = Number(b.id.replace(/\D/g, ''));
    if (!Number.isNaN(n) && n > max) max = n;
  }
  return `b${max + 1}`;
}

function normalize(input: AdminBannerInput): Omit<AdminBanner, 'id'> {
  return {
    image_url: input.image_url,
    title: input.title ?? null,
    subtitle: input.subtitle ?? null,
    cta_text: input.cta_text ?? null,
    link_type: input.link_type,
    link_value: input.link_type === 'none' ? null : (input.link_value ?? null),
    sort_order: input.sort_order ?? 0,
    is_active: input.is_active ?? true,
    starts_at: input.starts_at ?? null,
    ends_at: input.ends_at ?? null,
  };
}

export const bannersService = {
  async list(): Promise<AdminBanner[]> {
    if (USE_MOCK) {
      return delay(
        [...mockBanners].sort((a, b) => a.sort_order - b.sort_order),
      );
    }
    const { data } = await adminClient.get<DataEnvelope<AdminBanner[]>>(
      '/banners',
    );
    return data.data;
  },

  async create(input: AdminBannerInput): Promise<AdminBanner> {
    if (USE_MOCK) {
      const banner: AdminBanner = { id: nextId(), ...normalize(input) };
      mockBanners.push(banner);
      return delay(banner);
    }
    const { data } = await adminClient.post<DataEnvelope<AdminBanner>>(
      '/banners',
      input,
    );
    return data.data;
  },

  async update(id: string, input: AdminBannerInput): Promise<AdminBanner> {
    if (USE_MOCK) {
      const idx = mockBanners.findIndex((b) => b.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      mockBanners[idx] = { id, ...normalize(input) };
      return delay(mockBanners[idx]);
    }
    const { data } = await adminClient.patch<DataEnvelope<AdminBanner>>(
      `/banners/${id}`,
      input,
    );
    return data.data;
  },

  async toggleActive(id: string, is_active: boolean): Promise<AdminBanner> {
    if (USE_MOCK) {
      const idx = mockBanners.findIndex((b) => b.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      mockBanners[idx] = { ...mockBanners[idx], is_active };
      return delay(mockBanners[idx]);
    }
    const { data } = await adminClient.patch<DataEnvelope<AdminBanner>>(
      `/banners/${id}`,
      { is_active },
    );
    return data.data;
  },

  async remove(id: string): Promise<void> {
    if (USE_MOCK) {
      mockBanners = mockBanners.filter((b) => b.id !== id);
      await delay(null, 200);
      return;
    }
    await adminClient.delete(`/banners/${id}`);
  },
};
