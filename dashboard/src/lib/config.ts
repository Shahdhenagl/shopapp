// Runtime configuration resolved from Vite env vars.

export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL?.trim() || '/api/v1';

// Default to mock mode when the flag is unset so the app runs without a backend.
export const USE_MOCK =
  (import.meta.env.VITE_USE_MOCK ?? 'true').toLowerCase() !== 'false';

export const TOKEN_STORAGE_KEY = 'modist_admin_token';
export const USER_STORAGE_KEY = 'modist_admin_user';
export const LOCALE_STORAGE_KEY = 'modist_admin_locale';
