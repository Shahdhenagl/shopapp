import { api } from './client';
import type {
  Address,
  AppSettings,
  AuthResponse,
  Banner,
  Cart,
  Category,
  DataEnvelope,
  Order,
  Product,
  User,
} from '@/types';

export { api, getErrorMessage, setUnauthorizedHandler } from './client';

// --- Storefront config & catalog (public) -----------------------------------

export const catalog = {
  async settings(): Promise<AppSettings> {
    const { data } = await api.get<DataEnvelope<AppSettings>>('/settings/app');
    return data.data;
  },

  async categories(): Promise<Category[]> {
    const { data } = await api.get<DataEnvelope<Category[]>>('/categories');
    return data.data;
  },

  async banners(): Promise<Banner[]> {
    const { data } = await api.get<DataEnvelope<Banner[]>>('/home/banners');
    return data.data;
  },

  async products(params: {
    category?: string;
    search?: string;
    newest?: boolean;
    per_page?: number;
  } = {}): Promise<Product[]> {
    const { data } = await api.get<DataEnvelope<Product[]>>('/products', {
      params: {
        category_id: params.category || undefined,
        search: params.search || undefined,
        is_newest: params.newest ? 1 : undefined,
        per_page: params.per_page ?? 40,
      },
    });
    return data.data;
  },

  async product(id: string): Promise<Product> {
    const { data } = await api.get<DataEnvelope<Product>>(`/products/${id}`);
    return data.data;
  },
};

// --- Auth -------------------------------------------------------------------

export const auth = {
  async login(email: string, password: string): Promise<AuthResponse> {
    const { data } = await api.post<AuthResponse>('/auth/login', {
      email,
      password,
    });
    return data;
  },

  async register(input: {
    name: string;
    email: string;
    phone?: string;
    password: string;
  }): Promise<AuthResponse> {
    const { data } = await api.post<AuthResponse>('/auth/register', input);
    return data;
  },

  async me(): Promise<User> {
    const { data } = await api.get<DataEnvelope<User>>('/me');
    return data.data;
  },

  async logout(): Promise<void> {
    await api.post('/auth/logout');
  },
};

// --- Cart -------------------------------------------------------------------

export const cartApi = {
  async get(): Promise<Cart> {
    const { data } = await api.get<DataEnvelope<Cart>>('/cart');
    return data.data;
  },

  async add(input: {
    product_id: string;
    size: string;
    color: number;
    quantity: number;
  }): Promise<Cart> {
    const { data } = await api.post<DataEnvelope<Cart>>('/cart', input);
    return data.data;
  },

  async setQuantity(lineId: string, quantity: number): Promise<Cart> {
    const { data } = await api.patch<DataEnvelope<Cart>>(
      `/cart/${encodeURIComponent(lineId)}`,
      { quantity },
    );
    return data.data;
  },

  async remove(lineId: string): Promise<Cart> {
    const { data } = await api.delete<DataEnvelope<Cart>>(
      `/cart/${encodeURIComponent(lineId)}`,
    );
    return data.data;
  },

  async clear(): Promise<Cart> {
    const { data } = await api.delete<DataEnvelope<Cart>>('/cart');
    return data.data;
  },

  async applyPromo(code: string): Promise<Cart> {
    const { data } = await api.post<DataEnvelope<Cart>>('/cart/promo', { code });
    return data.data;
  },
};

// --- Account ----------------------------------------------------------------

export const account = {
  async orders(): Promise<Order[]> {
    const { data } = await api.get<DataEnvelope<Order[]>>('/me/orders');
    return data.data;
  },

  async addresses(): Promise<Address[]> {
    const { data } = await api.get<DataEnvelope<Address[]>>('/addresses');
    return data.data;
  },

  async addAddress(input: Partial<Address>): Promise<Address> {
    const { data } = await api.post<DataEnvelope<Address>>('/addresses', input);
    return data.data;
  },
};

export const checkout = {
  async place(input: {
    payment_method: string;
    address: {
      address: string;
      city?: string | null;
      area?: string | null;
      branch?: string | null;
    };
  }): Promise<Order> {
    const { data } = await api.post<DataEnvelope<Order>>('/checkout', input);
    return data.data;
  },
};
