# MODIST Admin Dashboard

Production-quality React admin panel for the **MODIST** e-commerce backend
(Laravel 11 API, `/api/v1`). Built to speak the §4 API contract of
`MODIST-Laravel-Backend-Plan.md`, and ships with a built-in **MOCK mode** so it
runs immediately without a backend.

## Stack

- Vite + React 18 + TypeScript
- React Router v6, TanStack Query (react-query), Axios
- Zustand (auth / locale / toast stores)
- Tailwind CSS, Recharts, react-hook-form + zod, lucide-react

## Getting started

```bash
npm install
npm run dev        # http://localhost:5173
```

By default the app runs in **MOCK mode** (`VITE_USE_MOCK=true`), serving
realistic in-memory data that mirrors the backend seeders.

### Default mock login

```
Email:    admin@modist.test
Password: password
```

## Build

```bash
npm run build      # tsc + vite build -> dist/
npm run preview    # serve the production build
```

## Switching to the real backend

1. Copy `.env.example` to `.env`.
2. Set:
   ```env
   VITE_USE_MOCK=false
   VITE_API_BASE_URL=/api/v1
   ```
3. The Vite dev server proxies `/api` → `http://localhost:8000`
   (see `vite.config.ts`). Point this at your Laravel app, or set
   `VITE_API_BASE_URL` to the deployed API origin.

The API layer (`src/api/*`) is the single switch point: every service branches
on `VITE_USE_MOCK` behind the **same interface**, so no component changes are
needed to go live.

## API contract notes (§4.2)

- **Auth** responses are **flat** `{ token, user }` (NOT `data`-wrapped).
- Everything else uses the standard `{ data: ... }` envelope.
- Axios attaches `Authorization: Bearer <token>` and `Accept-Language`.
- A `401` clears the session and redirects to `/login`.

## Project layout

```
src/
  api/         Typed Axios client + per-module services (mock/live behind one interface)
  components/  Shared UI: Button, Badge, Modal, DataTable, StatCard, States, Toasts
  layouts/     AppLayout, Sidebar, Topbar, ProtectedRoute
  mock/        Seed data + mutable in-memory store (mirrors backend seeders)
  pages/       Login, Dashboard, Products, Categories, Orders, Promos, Users, Settings
  store/       Zustand stores (auth, locale, toast)
  lib/         config, i18n, formatting, status helpers
  types/       Domain types (Product, Category, Order, PromoCode, User, CartResource…)
```
