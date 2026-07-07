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

// Modules whose admin API isn't built yet (Orders, Promos, Users, Dashboard
// stats) stay on demo data even when USE_MOCK is false, so they don't 404.
// Flip to false per-module as each real endpoint ships (Phase 2/3).
export const PENDING_MODULES_USE_MOCK: boolean = true;

export const TOKEN_STORAGE_KEY = 'modist_admin_token';
export const USER_STORAGE_KEY = 'modist_admin_user';
export const LOCALE_STORAGE_KEY = 'modist_admin_locale';
