// The storefront is served from the API's own domain, so a relative base works
// in production; override for local dev against a remote API.
export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ?? '/api/v1';

export const TOKEN_KEY = 'modist_token';
export const REFRESH_KEY = 'modist_refresh_token';
