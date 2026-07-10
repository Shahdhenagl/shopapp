// Runtime configuration resolved from Vite env vars.

export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL?.trim() || '/api/v1';

// Base URL for the Admin API. The dashboard is served at /dashboard and the
// admin endpoints live at /api/admin/v1 on the same host (relative → no CORS).
export const ADMIN_API_BASE_URL =
  import.meta.env.VITE_ADMIN_API_BASE_URL?.trim() || '/api/admin/v1';

// Default to mock mode when the flag is unset so the app runs without a backend.
export const USE_MOCK =
  (import.meta.env.VITE_USE_MOCK ?? 'true').toLowerCase() !== 'false';

// Every admin module (Orders, Promos, Customers, Dashboard KPIs included) now
// has a real endpoint, so USE_MOCK=false wires the whole dashboard to the API.

export const TOKEN_STORAGE_KEY = 'modist_admin_token';
export const USER_STORAGE_KEY = 'modist_admin_user';
export const LOCALE_STORAGE_KEY = 'modist_admin_locale';
