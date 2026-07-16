import axios from 'axios';
import { api } from './client';
import type {
  Address,
  AppNotification,
  AppSettings,
  AuthResponse,
  Banner,
  Cart,
  Category,
  DataEnvelope,
  Order,
  Product,
  Review,
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

  // Param names are the API's: ?category=&q=&newest= (ProductController@index).
  async products(params: {
    category?: string;
    search?: string;
    newest?: boolean;
    per_page?: number;
  } = {}): Promise<Product[]> {
    const { data } = await api.get<DataEnvelope<Product[]>>('/products', {
      params: {
        category: params.category || undefined,
        q: params.search || undefined,
        newest: params.newest ? 'true' : undefined,
        per_page: params.per_page ?? 40,
      },
    });
    return data.data;
  },

  async product(id: string): Promise<Product> {
    const { data } = await api.get<DataEnvelope<Product>>(`/products/${id}`);
    return data.data;
  },

  /** 404 degrades to no reviews rather than an error, as the app does. */
  async reviews(productId: string): Promise<Review[]> {
    try {
      const { data } = await api.get<DataEnvelope<Review[]>>(
        `/products/${productId}/reviews`,
      );
      return data.data;
    } catch (error) {
      if (axios.isAxiosError(error) && error.response?.status === 404) return [];
      throw error;
    }
  },

  async addReview(
    productId: string,
    input: { rating: number; comment?: string | null },
  ): Promise<Review> {
    const { data } = await api.post<DataEnvelope<Review>>(
      `/products/${productId}/reviews`,
      input,
    );
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

  async updateProfile(input: {
    name?: string;
    phone?: string | null;
  }): Promise<User> {
    const { data } = await api.patch<DataEnvelope<User>>('/me', input);
    return data.data;
  },

  /** Single call: uploads the image and returns the updated user. */
  async uploadAvatar(file: File): Promise<User> {
    const form = new FormData();
    form.append('image', file);
    const { data } = await api.post<DataEnvelope<User>>('/me/avatar', form);
    return data.data;
  },

  // Verification is a soft nudge — the account works unverified; only checkout
  // is gated, server-side.
  async sendEmailCode(): Promise<void> {
    await api.post('/auth/email/verify/send');
  },

  async verifyEmail(code: string): Promise<void> {
    await api.post('/auth/email/verify', { code });
  },

  async logout(): Promise<void> {
    await api.post('/auth/logout');
  },
};

// --- Notifications ----------------------------------------------------------

export const notifications = {
  async list(): Promise<AppNotification[]> {
    const { data } = await api.get<DataEnvelope<AppNotification[]>>(
      '/notifications',
    );
    return data.data;
  },

  /** Returns the server's updated list, which we adopt wholesale. */
  async markAllRead(): Promise<AppNotification[]> {
    const { data } = await api.post<DataEnvelope<AppNotification[]>>(
      '/notifications/read',
    );
    return data.data;
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

// --- Favorites --------------------------------------------------------------
// The API stores ids only; product detail comes from the catalog.

export const favorites = {
  async ids(): Promise<string[]> {
    const { data } = await api.get<DataEnvelope<string[]>>('/favorites');
    return data.data;
  },

  /** Toggle — returns the new id list. */
  async toggle(productId: string): Promise<string[]> {
    const { data } = await api.post<DataEnvelope<string[]>>('/favorites', {
      product_id: productId,
    });
    return data.data;
  },

  async clear(): Promise<void> {
    await api.delete('/favorites');
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

  async updateAddress(id: string, input: Partial<Address>): Promise<Address> {
    const { data } = await api.patch<DataEnvelope<Address>>(
      `/addresses/${id}`,
      input,
    );
    return data.data;
  },

  async removeAddress(id: string): Promise<void> {
    await api.delete(`/addresses/${id}`);
  },

  async makeDefault(id: string): Promise<Address> {
    const { data } = await api.post<DataEnvelope<Address>>(
      `/addresses/${id}/default`,
    );
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
