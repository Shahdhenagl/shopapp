import axios, {
  AxiosError,
  type AxiosInstance,
  type InternalAxiosRequestConfig,
} from 'axios';
import {
  ADMIN_API_BASE_URL,
  API_BASE_URL,
  LOCALE_STORAGE_KEY,
  TOKEN_STORAGE_KEY,
} from '@/lib/config';

// Callback registered by the auth store to handle 401 (clear session).
let onUnauthorized: (() => void) | null = null;
export function setUnauthorizedHandler(fn: () => void): void {
  onUnauthorized = fn;
}

export const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: { 'Content-Type': 'application/json' },
});

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = localStorage.getItem(TOKEN_STORAGE_KEY);
  if (token) {
    config.headers.set('Authorization', `Bearer ${token}`);
  }
  const locale = localStorage.getItem(LOCALE_STORAGE_KEY) || 'en';
  config.headers.set('Accept-Language', locale);
  return config;
});

apiClient.interceptors.response.use(
  (res) => res,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      onUnauthorized?.();
    }
    return Promise.reject(error);
  },
);

// ---------------------------------------------------------------------------
// Admin API client (/api/admin/v1). Sends Bearer <admin token> + Accept JSON,
// and shares the same 401 → clear-session handler as the storefront client.
// ---------------------------------------------------------------------------
export const adminClient: AxiosInstance = axios.create({
  baseURL: ADMIN_API_BASE_URL,
  headers: { Accept: 'application/json' },
});

adminClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = localStorage.getItem(TOKEN_STORAGE_KEY);
  if (token) {
    config.headers.set('Authorization', `Bearer ${token}`);
  }
  config.headers.set('Accept', 'application/json');
  const locale = localStorage.getItem(LOCALE_STORAGE_KEY) || 'en';
  config.headers.set('Accept-Language', locale);
  return config;
});

adminClient.interceptors.response.use(
  (res) => res,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      onUnauthorized?.();
    }
    return Promise.reject(error);
  },
);

// Normalize an axios/error into a user-facing message.
export function getErrorMessage(error: unknown, fallback = 'Request failed'): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as
      | { message?: string; errors?: Record<string, string[]> }
      | undefined;
    if (data?.errors) {
      const first = Object.values(data.errors)[0];
      if (first?.[0]) return first[0];
    }
    if (data?.message) return data.message;
    return error.message || fallback;
  }
  if (error instanceof Error) return error.message;
  return fallback;
}
