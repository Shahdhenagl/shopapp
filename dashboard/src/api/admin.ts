import { adminClient } from './client';
import { USE_MOCK } from '@/lib/config';
import { delay } from '@/mock/store';
import type {
  AdminAuthResponse,
  AdminProduct,
  AdminProductInput,
  AdminUser,
  CategoryNode,
  CategoryNodeInput,
  DataEnvelope,
  LocalizedText,
  Paginated,
  StoreSettings,
  StoreSettingsUpdate,
} from '@/types';
import type { LoginPayload } from './auth';

// ===========================================================================
// Mock fixtures (used when VITE_USE_MOCK !== 'false'). The real path is default.
// ===========================================================================

const MOCK_ADMIN_USER: AdminUser = {
  id: 'adm_1',
  name: 'Admin MODIST',
  email: 'admin@modist.test',
  role: 'owner',
  tenant_id: 'tnt_1',
};

let mockSettings: StoreSettings = {
  app_name: 'MODIST',
  currency: 'EGP',
  storefront_mode: 'single',
  logo_url: null,
  shipping_fee: 50,
  brand: { primary: '#1B2A4A', on_primary: '#FFFFFF', accent: '#6B4A2B' },
  flags: {
    card_payment: true,
    cash_payment: true,
    promo_codes: true,
    favorites: true,
  },
};

// Flat category rows; the tree (children/is_leaf/product_count) is derived.
interface MockCat {
  id: string;
  slug: string;
  parent_id: string | null;
  name: LocalizedText;
  icon_key: string | null;
  image_url: string | null;
  sort_order: number;
}

let mockCats: MockCat[] = [
  { id: 'clothing', slug: 'clothing', parent_id: null, name: { en: 'Clothing', ar: 'ملابس' }, icon_key: 'shirt', image_url: null, sort_order: 1 },
  { id: 'tshirt', slug: 'tshirt', parent_id: 'clothing', name: { en: 'T-Shirts', ar: 'تيشيرت' }, icon_key: 'tshirt', image_url: null, sort_order: 1 },
  { id: 'pants', slug: 'pants', parent_id: 'clothing', name: { en: 'Pants', ar: 'بناطيل' }, icon_key: 'pants', image_url: null, sort_order: 2 },
  { id: 'jacket', slug: 'jacket', parent_id: 'clothing', name: { en: 'Jackets', ar: 'جواكيت' }, icon_key: 'jacket', image_url: null, sort_order: 3 },
  { id: 'shorts', slug: 'shorts', parent_id: 'clothing', name: { en: 'Shorts', ar: 'شورتات' }, icon_key: 'shorts', image_url: null, sort_order: 4 },
  { id: 'footwear', slug: 'footwear', parent_id: null, name: { en: 'Footwear', ar: 'أحذية' }, icon_key: 'shoe', image_url: null, sort_order: 2 },
  { id: 'shoes', slug: 'shoes', parent_id: 'footwear', name: { en: 'Shoes', ar: 'أحذية' }, icon_key: 'shoes', image_url: null, sort_order: 1 },
];

function mockProductCount(slug: string): number {
  return mockProducts.filter((p) => p.category_id === slug).length;
}

function toNode(cat: MockCat): CategoryNode {
  const children = mockCats
    .filter((c) => c.parent_id === cat.id)
    .sort((a, b) => a.sort_order - b.sort_order)
    .map(toNode);
  return {
    id: cat.id,
    slug: cat.slug,
    parent_id: cat.parent_id,
    name: { ...cat.name },
    label_key: `category_${cat.slug}`,
    icon_key: cat.icon_key,
    image_url: cat.image_url,
    sort_order: cat.sort_order,
    is_leaf: children.length === 0,
    product_count: mockProductCount(cat.slug),
    children,
  };
}

function buildMockTree(): CategoryNode[] {
  return mockCats
    .filter((c) => c.parent_id === null)
    .sort((a, b) => a.sort_order - b.sort_order)
    .map(toNode);
}

function normalizeName(name: LocalizedText | string): LocalizedText {
  if (typeof name === 'string') return { en: name, ar: name };
  return { en: name.en, ar: name.ar };
}

let mockProducts: AdminProduct[] = [
  { id: 'p1', name: { en: "Men's Casual Navy Shirt", ar: 'قميص رجالي كاجوال كحلي' }, style: { en: 'Men Style', ar: 'ستايل رجالي' }, description: { en: 'Premium casual navy shirt.', ar: 'قميص كاجوال كحلي فاخر.' }, price: 820, currency: 'EGP', rating: 4.6, is_newest: true, category_id: 'tshirt', images: ['https://picsum.photos/seed/modist1/600/800'], sizes: ['M', 'L'], colors: ['#FF1B2A4A'], created_at: new Date().toISOString() },
  { id: 'p2', name: { en: 'Classic White Tee', ar: 'تيشيرت أبيض كلاسيكي' }, style: { en: 'Unisex', ar: 'للجنسين' }, description: { en: 'Everyday classic white tee.', ar: 'تيشيرت أبيض كلاسيكي يومي.' }, price: 450, currency: 'EGP', rating: 4.4, is_newest: false, category_id: 'tshirt', images: ['https://picsum.photos/seed/modist2/600/800'], sizes: ['S', 'M', 'L'], colors: ['#FFFFFFFF'], created_at: new Date().toISOString() },
  { id: 'p3', name: { en: 'Slim Fit Chino Pants', ar: 'بنطلون تشينو ضيق' }, style: { en: 'Men Style', ar: 'ستايل رجالي' }, description: { en: 'Slim fit chino pants.', ar: 'بنطلون تشينو ضيق.' }, price: 990, currency: 'EGP', rating: 4.7, is_newest: true, category_id: 'pants', images: ['https://picsum.photos/seed/modist3/600/800'], sizes: ['M', 'L', 'XL'], colors: ['#FF6B4A2B'], created_at: new Date().toISOString() },
  { id: 'p6', name: { en: 'Leather Sneakers', ar: 'حذاء رياضي جلد' }, style: { en: 'Sport', ar: 'رياضي' }, description: { en: 'Leather sneakers.', ar: 'حذاء رياضي جلد.' }, price: 1500, currency: 'EGP', rating: 4.8, is_newest: false, category_id: 'shoes', images: ['https://picsum.photos/seed/modist6/600/800'], sizes: [], colors: [], created_at: new Date().toISOString() },
];

function nextMockId(prefix: string, list: { id: string }[]): string {
  let max = 0;
  for (const item of list) {
    const n = Number(item.id.replace(/\D/g, ''));
    if (!Number.isNaN(n) && n > max) max = n;
  }
  return `${prefix}${max + 1}`;
}

function toHexColor(c: string | number): string {
  if (typeof c === 'number') {
    return `#${(c >>> 0).toString(16).toUpperCase().padStart(8, '0')}`;
  }
  return c;
}

// ===========================================================================
// Auth — flat { token, admin }
// ===========================================================================

export const adminAuthService = {
  async login(payload: LoginPayload): Promise<AdminAuthResponse> {
    if (USE_MOCK) {
      if (
        payload.email.trim().toLowerCase() === MOCK_ADMIN_USER.email &&
        payload.password === 'password'
      ) {
        return delay({ token: `mock|${Date.now()}`, admin: MOCK_ADMIN_USER });
      }
      await delay(null, 300);
      throw { response: { status: 401, data: { message: 'Invalid credentials' } } };
    }
    const { data } = await adminClient.post<AdminAuthResponse>(
      '/auth/login',
      payload,
    );
    return data;
  },

  async logout(): Promise<void> {
    if (USE_MOCK) {
      await delay(null, 150);
      return;
    }
    await adminClient.post('/auth/logout');
  },

  async me(): Promise<AdminUser> {
    if (USE_MOCK) return delay(MOCK_ADMIN_USER);
    const { data } = await adminClient.get<DataEnvelope<AdminUser>>('/me');
    return data.data;
  },
};

// ===========================================================================
// Settings
// ===========================================================================

export const settingsService = {
  async get(): Promise<StoreSettings> {
    if (USE_MOCK) return delay({ ...mockSettings });
    const { data } = await adminClient.get<DataEnvelope<StoreSettings>>(
      '/settings',
    );
    return data.data;
  },

  async update(patch: StoreSettingsUpdate): Promise<StoreSettings> {
    if (USE_MOCK) {
      const { brand_primary, brand_on_primary, brand_accent, flags, ...rest } =
        patch;
      mockSettings = {
        ...mockSettings,
        ...rest,
        brand: {
          primary: brand_primary ?? mockSettings.brand.primary,
          on_primary: brand_on_primary ?? mockSettings.brand.on_primary,
          accent: brand_accent ?? mockSettings.brand.accent,
        },
        flags: { ...mockSettings.flags, ...(flags ?? {}) },
      };
      return delay({ ...mockSettings });
    }
    const { data } = await adminClient.patch<DataEnvelope<StoreSettings>>(
      '/settings',
      patch,
    );
    return data.data;
  },
};

// ===========================================================================
// Categories tree
// ===========================================================================

export const adminCategoriesService = {
  async tree(): Promise<CategoryNode[]> {
    if (USE_MOCK) return delay(buildMockTree());
    const { data } = await adminClient.get<DataEnvelope<CategoryNode[]>>(
      '/categories',
    );
    return data.data;
  },

  async create(input: CategoryNodeInput): Promise<CategoryNode> {
    if (USE_MOCK) {
      const name = normalizeName(input.name);
      const slug =
        input.slug?.trim().toLowerCase() ||
        name.en.trim().toLowerCase().replace(/\s+/g, '-');
      if (mockCats.some((c) => c.slug === slug)) {
        throw { response: { status: 422, data: { message: 'Slug already exists' } } };
      }
      const cat: MockCat = {
        id: slug,
        slug,
        parent_id: input.parent_id ?? null,
        name,
        icon_key: input.icon_key ?? null,
        image_url: input.image_url ?? null,
        sort_order: input.sort_order ?? 0,
      };
      mockCats = [...mockCats, cat];
      return delay(toNode(cat));
    }
    const { data } = await adminClient.post<DataEnvelope<CategoryNode>>(
      '/categories',
      input,
    );
    return data.data;
  },

  async update(id: string, input: CategoryNodeInput): Promise<CategoryNode> {
    if (USE_MOCK) {
      const idx = mockCats.findIndex((c) => c.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      const current = mockCats[idx];
      const updated: MockCat = {
        ...current,
        name: input.name !== undefined ? normalizeName(input.name) : current.name,
        slug: input.slug?.trim().toLowerCase() || current.slug,
        parent_id:
          input.parent_id !== undefined ? input.parent_id : current.parent_id,
        icon_key: input.icon_key !== undefined ? input.icon_key : current.icon_key,
        image_url:
          input.image_url !== undefined ? input.image_url : current.image_url,
        sort_order:
          input.sort_order !== undefined ? input.sort_order : current.sort_order,
      };
      mockCats = mockCats.map((c) => (c.id === id ? updated : c));
      return delay(toNode(updated));
    }
    const { data } = await adminClient.patch<DataEnvelope<CategoryNode>>(
      `/categories/${id}`,
      input,
    );
    return data.data;
  },

  async remove(id: string): Promise<void> {
    if (USE_MOCK) {
      if (mockCats.some((c) => c.parent_id === id)) {
        throw {
          response: {
            status: 422,
            data: { message: 'Category still has children' },
          },
        };
      }
      const slug = mockCats.find((c) => c.id === id)?.slug;
      if (slug && mockProductCount(slug) > 0) {
        throw {
          response: {
            status: 422,
            data: { message: 'Category still has products' },
          },
        };
      }
      mockCats = mockCats.filter((c) => c.id !== id);
      await delay(null, 200);
      return;
    }
    await adminClient.delete(`/categories/${id}`);
  },
};

// Flatten a tree into a list with a depth marker (for pickers / rows).
export function flattenTree(
  nodes: CategoryNode[],
  depth = 0,
): Array<{ node: CategoryNode; depth: number }> {
  const out: Array<{ node: CategoryNode; depth: number }> = [];
  for (const node of nodes) {
    out.push({ node, depth });
    if (node.children.length) out.push(...flattenTree(node.children, depth + 1));
  }
  return out;
}

// Collect a node's id plus all descendant ids (to exclude when reparenting).
export function subtreeIds(node: CategoryNode): string[] {
  const out = [node.id];
  for (const child of node.children) out.push(...subtreeIds(child));
  return out;
}

export interface ProductQuery {
  search?: string;
  category?: string;
  per_page?: number;
}

// ===========================================================================
// Products
// ===========================================================================

export const adminProductsService = {
  async list(query: ProductQuery = {}): Promise<AdminProduct[]> {
    if (USE_MOCK) {
      let items = [...mockProducts];
      if (query.category) {
        items = items.filter((p) => p.category_id === query.category);
      }
      if (query.search) {
        const q = query.search.toLowerCase();
        items = items.filter(
          (p) =>
            p.name.en.toLowerCase().includes(q) ||
            p.name.ar.includes(q) ||
            p.style.en.toLowerCase().includes(q),
        );
      }
      return delay(items);
    }
    const { data } = await adminClient.get<Paginated<AdminProduct>>('/products', {
      params: {
        search: query.search || undefined,
        category: query.category || undefined,
        per_page: query.per_page ?? 100,
      },
    });
    return data.data;
  },

  async get(id: string): Promise<AdminProduct> {
    if (USE_MOCK) {
      const found = mockProducts.find((p) => p.id === id);
      if (!found) throw { response: { status: 404 } };
      return delay(found);
    }
    const { data } = await adminClient.get<DataEnvelope<AdminProduct>>(
      `/products/${id}`,
    );
    return data.data;
  },

  async create(input: AdminProductInput): Promise<AdminProduct> {
    if (USE_MOCK) {
      const product: AdminProduct = {
        id: nextMockId('p', mockProducts),
        name: input.name,
        style: input.style ?? { en: '', ar: '' },
        description: input.description ?? { en: '', ar: '' },
        price: input.price,
        currency: input.currency ?? mockSettings.currency,
        rating: input.rating ?? 0,
        is_newest: input.is_newest ?? false,
        category_id: input.category_id,
        images: input.images,
        sizes: input.sizes,
        colors: input.colors.map(toHexColor),
        created_at: new Date().toISOString(),
      };
      mockProducts = [...mockProducts, product];
      return delay(product);
    }
    const { data } = await adminClient.post<DataEnvelope<AdminProduct>>(
      '/products',
      input,
    );
    return data.data;
  },

  async update(id: string, input: AdminProductInput): Promise<AdminProduct> {
    if (USE_MOCK) {
      const idx = mockProducts.findIndex((p) => p.id === id);
      if (idx === -1) throw { response: { status: 404 } };
      const updated: AdminProduct = {
        ...mockProducts[idx],
        name: input.name,
        style: input.style ?? mockProducts[idx].style,
        description: input.description ?? mockProducts[idx].description,
        price: input.price,
        currency: input.currency ?? mockProducts[idx].currency,
        rating: input.rating ?? mockProducts[idx].rating,
        is_newest: input.is_newest ?? false,
        category_id: input.category_id,
        images: input.images,
        sizes: input.sizes,
        colors: input.colors.map(toHexColor),
      };
      mockProducts = mockProducts.map((p) => (p.id === id ? updated : p));
      return delay(updated);
    }
    const { data } = await adminClient.patch<DataEnvelope<AdminProduct>>(
      `/products/${id}`,
      input,
    );
    return data.data;
  },

  async remove(id: string): Promise<void> {
    if (USE_MOCK) {
      mockProducts = mockProducts.filter((p) => p.id !== id);
      await delay(null, 200);
      return;
    }
    await adminClient.delete(`/products/${id}`);
  },
};

// ===========================================================================
// Media upload — multipart { file } → { data: { url } }
// ===========================================================================

export async function uploadMedia(file: File): Promise<string> {
  if (USE_MOCK) {
    // Return a local object URL so previews work without a backend.
    return delay(URL.createObjectURL(file), 400);
  }
  const form = new FormData();
  form.append('file', file);
  // Let the browser set Content-Type (with the multipart boundary) for FormData.
  const { data } = await adminClient.post<DataEnvelope<{ url: string }>>(
    '/media',
    form,
  );
  return data.data.url;
}
