import axios, { AxiosError } from 'axios';
import { API_BASE_URL, TOKEN_KEY } from '@/lib/config';

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: { Accept: 'application/json' },
});

// Both clients authenticate with Sanctum bearer tokens — never cookies.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) config.headers.Authorization = `Bearer ${token}`;

  const locale = document.documentElement.lang || 'ar';
  config.headers['Accept-Language'] = locale;

  return config;
});

let onUnauthorized: (() => void) | null = null;

export function setUnauthorizedHandler(handler: () => void) {
  onUnauthorized = handler;
}

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) onUnauthorized?.();
    return Promise.reject(error);
  },
);

/**
 * The API renders every error as { message, errors? } (bootstrap/app.php), so
 * surface that rather than Axios's own wording.
 */
export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as
      | { message?: string; errors?: Record<string, string[]> }
      | undefined;

    const firstFieldError = data?.errors
      ? Object.values(data.errors)[0]?.[0]
      : undefined;

    return firstFieldError ?? data?.message ?? error.message;
  }
  return error instanceof Error ? error.message : 'Something went wrong';
}
