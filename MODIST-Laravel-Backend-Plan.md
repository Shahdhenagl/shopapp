# MODIST — Laravel Backend Architecture & Implementation Plan

> A best-practice, SOLID, loosely-coupled Laravel API for the MODIST (jozoog)
> Flutter e-commerce app. The contract below is reverse-engineered from the
> app's existing Dio remote data sources, models, and mock behavioral specs, so
> flipping `AppConfig.useMockData → false` makes the app talk to this backend
> with (almost) zero client changes.

---

## Context

The MODIST Flutter app is fully built against **mock data sources**. A complete
Dio-backed remote layer (`*RemoteDataSource`), an endpoint registry
(`api_endpoints.dart`), a `DioClient` (Bearer auth, `Accept-Language`, 401
handler), and tolerant JSON models already ship — they just need a server. This
plan specifies that server: a Laravel 11 API that **exactly matches the
contract the app already expects**, built with Clean Architecture + SOLID so it
stays maintainable as the storefront grows.

**Primary deliverable of this task:** this plan, rendered as a **PDF-ready
document** (see [§14](#14--deliverable-the-pdf-file)). Actual Laravel
scaffolding is an opt-in follow-up.

**Decisions locked with the user:**

| Area | Decision |
|---|---|
| Scope | **Full backend**, delivered in phases |
| Content i18n | **Bilingual (AR + EN)**, resolved by `Accept-Language` |
| Auth | **Laravel Sanctum** (personal access tokens) |
| Payments | **Paymob** (Egypt, EGP) behind a gateway interface |

---

## Table of Contents

1. [Tech stack](#1--tech-stack)
2. [Architectural principles (Clean + SOLID)](#2--architectural-principles)
3. [Project structure](#3--project-structure)
4. [The API contract](#4--the-api-contract)
5. [Database schema](#5--database-schema)
6. [Module designs](#6--module-designs)
7. [Cross-cutting concerns](#7--cross-cutting-concerns)
8. [SOLID & decoupling specifics](#8--solid--decoupling-specifics)
9. [Testing strategy](#9--testing-strategy)
10. [Tooling & DevOps](#10--tooling--devops)
11. [Phased delivery roadmap](#11--phased-delivery-roadmap)
12. [Flutter-side switchover](#12--flutter-side-switchover)
13. [Risks & decisions to confirm](#13--risks--decisions-to-confirm)
14. [Deliverable: PDF-ready file](#14--deliverable-the-pdf-file)
15. [Verification](#15--verification)

---

## 1 · Tech stack

| Concern | Choice | Why |
|---|---|---|
| Framework | **Laravel 11**, PHP 8.3+ | Latest, typed, first-class API tooling |
| Auth | **Laravel Sanctum** | Token auth purpose-built for mobile; simple issue/revoke |
| DB | **MySQL 8** (or PostgreSQL 15) | Standard; switchable via config |
| Payments | **Paymob** via `PaymentGateway` interface | EGP-native; isolated behind a port (OCP) |
| Content i18n | **`spatie/laravel-translatable`** (JSON translation columns) | Bilingual product/category fields, locale-resolved |
| Validation | **Form Requests** | Validation out of controllers (SRP) |
| Serialization | **API Resources** | Decouples DB schema from JSON contract |
| Tests | **Pest** (+ PHPUnit) | Expressive feature/contract tests |
| Static analysis | **Larastan** (PHPStan) lvl 6+ | Catch coupling/type bugs pre-runtime |
| Style | **Laravel Pint** | Enforced PSR-12 |
| Local env | **Laravel Sail** (Docker) | Parity, one-command bootstrap |
| API docs | **Scribe** → OpenAPI | Generated, always-in-sync docs |

---

## 2 · Architectural principles

Four layers, **dependencies point inward only** (mirrors the app's own Clean
Architecture so the two halves stay conceptually aligned):

```
HTTP  ──▶  Application  ──▶  Domain  ◀──  Infrastructure
(thin)     (Actions)        (Contracts,    (Eloquent repos,
                             Models, DTOs)   Paymob, Mail)
```

- **HTTP layer** — Controllers (thin, one action method each), Form Requests
  (validation), API Resources (response shaping), Middleware. Knows nothing
  about Eloquent queries or Paymob.
- **Application layer** — one **Action class per use case**
  (`LoginAction`, `AddItemToCartAction`, …), a 1:1 mirror of the app's
  `domain/usecases/`. Single public `execute()` (SRP). Orchestrates domain +
  repositories; contains no SQL and no HTTP.
- **Domain layer** — Eloquent **Models as entities**, **DTOs**, domain
  **Exceptions**, and **Contracts** (`ProductRepositoryInterface`,
  `PaymentGateway`, `OtpStore`). Pure business rules.
- **Infrastructure layer** — Eloquent repository **implementations**, the
  **PaymobGateway**, Mailers. Implements domain contracts (Dependency
  Inversion).

**SOLID applied:** Actions = SRP · gateway/repository interfaces = OCP & DIP ·
small focused contracts = ISP · Resources/DTOs keep the API contract stable
independent of the schema (low coupling).

---

## 3 · Project structure

Feature-first modules under `app/`, mirroring the Flutter `lib/features/*` split
so a developer can move between client and server with the same mental map.

```
app/
  Domain/
    Auth/        { Models/User.php, Contracts/, Actions/, DTOs/, Exceptions/ }
    Catalog/     { Models/{Product,Category}.php, Contracts/, Actions/ }
    Cart/        { Models/{Cart,CartItem,PromoCode}.php, Contracts/, Actions/, DTOs/ }
    Favorites/   { Contracts/, Actions/ }
    Checkout/    { Models/{Order,OrderItem}.php, Contracts/PaymentGateway.php, Actions/, DTOs/ }
    Profile/     { Actions/ }                       # reuses Auth\User
    Shared/      { ValueObjects/Money.php, Concerns/ }
  Infrastructure/
    Persistence/Eloquent/   # *RepositoryImpl classes
    Payment/Paymob/         # PaymobGateway + DTOs + signature verifier
    Otp/                    # DatabaseOtpStore
    Providers/DomainServiceProvider.php   # binds every interface → impl
  Http/
    Controllers/Api/V1/     # thin controllers, grouped by module
    Requests/Api/V1/        # one Form Request per write endpoint
    Resources/              # UserResource, ProductResource, CartResource, ...
    Middleware/SetLocaleFromHeader.php
routes/api.php              # versioned /api/v1 group
database/{migrations,factories,seeders}
lang/{en,ar}/               # localized API messages + validation
tests/{Feature,Unit}
```

> **Note on "modules":** this is Laravel's standard app folder with PSR-4
> sub-namespaces — **no extra package** needed. It buys module cohesion without
> the overhead of a full package-per-module setup.

---

## 4 · The API contract

Base URL = `AppConfig.apiBaseUrl`. All routes under **`/api/v1`**. Headers the
app sends: `Accept-Language: en|ar`, `Authorization: Bearer <token>` (after
auth), `Content-Type: application/json`.

### 4.1 Endpoint map (exactly what the app calls)

| Method | Path | Module | Auth |
|---|---|---|---|
| POST | `/auth/login` | Auth | public |
| POST | `/auth/register` | Auth | public |
| POST | `/auth/logout` | Auth | bearer |
| POST | `/auth/password/forgot` | Auth | public |
| POST | `/auth/password/verify` | Auth | public |
| POST | `/auth/password/resend` | Auth | public |
| POST | `/auth/password/reset` | Auth | public |
| GET | `/categories` | Catalog | public |
| GET | `/products` | Catalog | public |
| GET | `/products/{id}` | Catalog | public |
| GET | `/cart` | Cart | bearer |
| POST | `/cart` | Cart | bearer |
| PATCH | `/cart/{lineId}` | Cart | bearer |
| DELETE | `/cart/{lineId}` | Cart | bearer |
| DELETE | `/cart` | Cart | bearer |
| POST | `/cart/promo` | Cart | bearer |
| GET | `/favorites` | Favorites | bearer |
| POST | `/favorites` | Favorites | bearer |
| DELETE | `/favorites` | Favorites | bearer |
| POST | `/checkout` | Checkout | bearer |
| GET | `/me` | Profile | bearer |

### 4.2 Response envelope rules (critical — must match the app's parsers)

- **Auth (`login`/`register`)** → **flat, NOT `data`-wrapped.** The app's
  `_parseAuth` reads `data['token'] ?? data['access_token']` and
  `data['user'] ?? data['data'] ?? data` at the **top level**:
  ```json
  { "token": "1|xY...", "user": { "id":"7","name":"Sara","email":"s@x.com","phone":"+20...","avatar_url":null } }
  ```
- **Catalog / Cart / Favorites / Profile** → standard API-Resource
  **`{ "data": ... }`** wrapper (the app's `_asList` / favorites parser auto-
  extract `data`).
- **Validation errors** → Laravel default **422** `{ "message": "...",
  "errors": { "field": ["..."] } }`. The app surfaces its own localized string
  by HTTP status + the call's fallback key; `message` is debug-only.
- **401** anywhere → triggers the app's `onUnauthorized` (clears session →
  login). Reserve 401 strictly for invalid/expired tokens.

### 4.3 Key payloads & shapes

**Register** body `{name,email,phone,password}` · **Login** `{email,password}`
→ both return the flat `{token,user}` above.

**Password reset flow** (bodies): forgot `{email}` · verify `{email,code}` ·
resend `{email}` · reset `{email,password}`. All return `200 {message}`.

**Product** (`ProductResource`) — keys chosen as the app's **first-choice**
variants:
```json
{
  "id": "p1", "name": "Men's Casual Navy Shirt", "style": "Men Style",
  "description": "Casual men's navy shirt...", "price": 820, "currency": "EGP",
  "images": ["https://.../600/800"],
  "colors": ["#FF1B2A4A","#FF7B1E1E","#FF111111","#FF6B4A2B"],
  "sizes": ["S","M","L","XL","XXL","XXXL"],
  "category_id": "tshirt", "rating": 4.6, "is_newest": true
}
```
> `colors` returned as **hex strings** (`#AARRGGBB`) — the app's `_toIntList`
> handles `#`-prefixed hex and avoids signed-int32 ambiguity. `name`/`style`/
> `description` are **locale-resolved** from the `Accept-Language` header.

**Category** (`CategoryResource`): `{ "id":"tshirt", "label_key":"category_tshirt",
"icon_key":"tshirt" }` — categories stay **key-based** (the app maps
`label_key` → l10n and `icon_key` → icon). Seed the five fixed ids: `tshirt,
pants, jacket, shorts, shoes`.

**Cart** (`CartResource`) — defines the shape the app's *not-yet-implemented*
cart parser will adopt (see [§12](#12--flutter-side-switchover)):
```json
{ "data": {
  "items": [{
    "line_id": "p1|M|4279371338", "size": "M", "color": 4279371338,
    "quantity": 2, "line_total": 1640, "product": { ...ProductResource... }
  }],
  "summary": { "subtotal":1640, "discount":164, "total":1476, "currency":"EGP",
               "applied_promo": { "code":"MODIST10", "fraction":0.10 } }
} }
```
> `color` round-trips as the **int** the app sent on `POST /cart`
> (`{product_id,size,color:<int>,quantity}`); `line_id` = `product_id|size|color`
> so it matches the app's locally-computed `CartItem.lineId` for `PATCH/DELETE
> /cart/{lineId}`. The backend resolves that route by splitting the composite.

**Promo** `POST /cart/promo {code}` → `{ "fraction":0.10, "code":"MODIST10" }`
or **422** (`promoInvalid`). Seed mock codes: `MODIST10`=0.10, `WELCOME15`=0.15,
`XX032910`=0.20.

**Favorites** GET → `{ "data": ["p1","p3"] }` · POST `{product_id}` toggles →
returns the updated `{ "data":[...] }` · DELETE → `{ "data":[] }`.

**Checkout** `POST /checkout` (new contract — app feature not yet built):
creates an order from the user's cart, initiates Paymob, returns:
```json
{ "data": {
  "order": { "id":"...", "status":"pending_payment", "total":1476, "currency":"EGP", "items":[...] },
  "payment": { "provider":"paymob", "payment_key":"...", "iframe_url":"https://accept.paymob.com/api/acceptance/iframes/{id}?payment_token=..." }
} }
```

**Profile** `GET /me` → `{ "data": { ...UserResource... } }` (id, name, email,
phone, avatar_url).

---

## 5 · Database schema

Migrations (one per table; FKs + indexes noted):

- **users** — `id, name, email* (unique), phone (null), password, avatar_url
  (null), email_verified_at (null), timestamps`. Sanctum adds
  `personal_access_tokens`.
- **password_reset_codes** — `id, email (index), code_hash, expires_at,
  consumed_at (null), verified_at (null), attempts (default 0), timestamps`.
  Hashed 4–6 digit OTP; short TTL.
- **categories** — `id (slug, PK), label_key, icon_key, sort_order, name (JSON
  translations for admin), timestamps`.
- **products** — `id (uuid), category_id (FK→categories), price (decimal 10,2),
  currency (default EGP), rating (decimal 2,1), is_newest (bool, index),
  name (JSON i18n), style (JSON i18n), description (JSON i18n), timestamps,
  soft deletes`.
- **product_images** — `id, product_id (FK), url, position` (ordered; first =
  primary).
- **product_colors** — `id, product_id (FK), color_value (unsigned bigint ARGB),
  position`.
- **product_sizes** — `id, product_id (FK), size, position`.
  > Images/colors/sizes normalized into child tables for clean querying &
  > admin editing; Resources collapse them back into `images[]/colors[]/sizes[]`.
- **carts** — `id, user_id (FK, unique), timestamps`.
- **cart_items** — `id, cart_id (FK), product_id (FK), size, color_value
  (bigint), quantity, **unique(cart_id, product_id, size, color_value)**` →
  enforces the merge-by-line rule.
- **promo_codes** — `id, code* (unique, upper), type (percent|fixed), fraction
  (decimal), active, starts_at, ends_at, usage_limit, used_count`.
- **orders** — `id (uuid), user_id (FK), status (enum), subtotal, discount,
  total, currency, promo_code (null), payment_status (enum), paymob_order_id
  (null), paymob_txn_id (null), shipping_* fields, timestamps`.
- **order_items** — `id, order_id (FK), product_id, name_snapshot, size,
  color_value, quantity, unit_price, line_total`.

Seeders mirror the mock spec: 5 categories, ~8 demo products
(`picsum.photos/seed/modist{n}/600/800`, palette `#FF1B2A4A/#FF7B1E1E/#FF111111/
#FF6B4A2B`, sizes `S…XXXL`, price 820, rating 4.6), 3 promo codes.

---

## 6 · Module designs

Each module = Form Request(s) → thin Controller → **Action** → Repository
interface (Eloquent impl) → Resource. Business rules below come from the app's
mock data sources (the de-facto spec).

### 6.1 Auth (Sanctum)
- **Register** → validate (`email` unique, `password` min 8), create user, issue
  Sanctum token, return `{token,user}`.
- **Login** → validate, `Auth::attempt`; success → new token + `{token,user}`;
  fail → **401**. Throttle (e.g. 5/min/IP+email).
- **Forgot** → generate OTP, store **hashed** + TTL (10 min), dispatch queued
  `SendOtpMail`; **always 200** (don't leak account existence).
- **Verify** → match hashed, unexpired, attempt-limited code → mark
  `verified_at`, 200; else 422 (`authVerifyCode`).
- **Resend** → rate-limited regenerate + re-mail.
- **Reset** → require a **verified, unexpired, unconsumed** code for the email
  (the app's reset call sends only `{email,password}`), update password, consume
  code, optionally revoke existing tokens, 200.
- **Logout** → revoke current access token, 200.
- Contracts: `UserRepositoryInterface`, `OtpStore`. Actions: `Login`,
  `Register`, `SendResetCode`, `VerifyResetCode`, `ResendResetCode`,
  `ResetPassword`, `Logout` (1:1 with the app's usecases).

### 6.2 Catalog
- `GET /categories` → ordered `CategoryResource` collection.
- `GET /products` → `ProductResource` collection; optional server-side filters
  `?category=&q=&newest=` and pagination (`?page=&per_page=`). **Default returns
  the full active catalog** so the app's current client-side search/filter keeps
  working; server-side search is a later Flutter enhancement.
- `GET /products/{id}` → single `ProductResource`, **404** if missing
  (`productLoad`).
- Contracts: `ProductRepositoryInterface`, `CategoryRepositoryInterface`.
  Read-heavy → cache categories & product lists (tagged cache, busted on write).

### 6.3 Cart (per authenticated user)
- Lazily create the user's cart. **Add** merges by `(product_id,size,color)`
  (DB unique) summing quantity; **update** sets quantity (≤0 ⇒ remove);
  **remove** deletes the line; **clear** empties; all return the full
  `CartResource`. **Promo** validates against `promo_codes`
  (case-insensitive, active, in-window) → `{fraction,code}` or 422.
- Route model resolution parses the composite `{lineId}` (`product|size|color`).
- Contracts: `CartRepositoryInterface`, `PromoRepositoryInterface`. Money math
  in a `Money` value object (no float drift).

### 6.4 Favorites
- Pivot `favorites(user_id, product_id)`, unique. GET → ID array; POST toggles;
  DELETE clears. `FavoriteRepositoryInterface`.

### 6.5 Checkout + Orders (Paymob)
- `CreateOrderAction`: snapshot cart → `orders`/`order_items`, recompute totals
  server-side (never trust client), status `pending_payment`.
- `PaymentGateway` interface (`initiate(Order): PaymentSession`) with
  **`PaymobGateway`** impl (auth → order register → payment key). Controller
  returns `{order, payment:{payment_key, iframe_url}}`.
- **Webhook** `POST /api/v1/webhooks/paymob` (public, **HMAC-verified**) →
  `HandlePaymobCallbackAction` flips `payment_status`/`status`, empties cart on
  success. Idempotent by `paymob_txn_id`.
- Swappable: adding Stripe later = a new gateway impl + binding, **zero**
  checkout-logic change (OCP).

### 6.6 Profile
- `GET /me` → `UserResource`. (`PUT /me` for name/phone/avatar is a natural
  next step — error keys `profileLoad/profileUpdate` already exist client-side.)

---

## 7 · Cross-cutting concerns

- **Localization** — `SetLocaleFromHeader` middleware validates
  `Accept-Language ∈ {en,ar}` → `app()->setLocale()`. Translatable model fields
  resolve to the active locale inside Resources. API/validation messages live in
  `lang/en` + `lang/ar`.
- **Errors** — `bootstrap/app.php` exception handler renders JSON
  `{message, errors?}` with correct status (422/401/404/500). Domain exceptions
  map to statuses. (The app shows its **own** localized copy keyed by call +
  status; server `message` is for logs/other clients.)
- **Security** — hashed passwords (argon2id), Sanctum token abilities, rate
  limiting on auth + promo + webhook, mass-assignment guards, CORS for the app
  origin, HMAC on Paymob webhook, OTP hashed + TTL + attempt cap + generic
  responses, all secrets in `.env`.
- **Performance** — eager-load product children, tagged cache for catalog,
  DB indexes on hot columns, queued mail/webhook side-effects.

---

## 8 · SOLID & decoupling specifics

- **DIP** — every consumer depends on an **interface**; `DomainServiceProvider`
  binds `Interface → EloquentImpl` (and `PaymentGateway → PaymobGateway`). Swap
  persistence or PSP by changing one binding.
- **SRP** — one Action per use case; validation in Form Requests; shaping in
  Resources; controllers just wire request → action → resource.
- **OCP** — new payment provider / new repository = new class + binding, no edits
  to existing callers.
- **ISP** — narrow contracts (`OtpStore` ≠ `UserRepository`); no fat "god"
  service.
- **Low coupling** — DTOs cross layer boundaries; Resources isolate the JSON
  contract from the schema, so DB refactors never break the app.

---

## 9 · Testing strategy

- **Feature tests (Pest)** per endpoint: full auth flow (register/login/OTP
  reset/logout), catalog read, cart merge/update/remove/promo, favorites toggle,
  checkout order creation, Paymob webhook (signed + replayed).
- **Contract tests** assert the exact JSON keys the app parses
  (`token`,`user.avatar_url`,`images`,`colors`,`category_id`,`is_newest`,
  `fraction`, cart `line_id`) — guards the client integration.
- **Unit tests** for `Money`, promo validation, OTP store, totals math.
- **Factories + seeders** reproduce the mock dataset. Target: green
  `pest`, Larastan clean, Pint clean.

---

## 10 · Tooling & DevOps

- **Laravel Sail** (Docker: app + MySQL + Redis + Mailpit) — one-command local.
- **CI** (GitHub Actions): Pint → Larastan → Pest on every push.
- **Scribe** generates API docs/OpenAPI from the routes + Form Requests.
- `.env.example` documents `APP_URL`, DB, `PAYMOB_*`, mail, locale defaults.
- Queue worker (Redis) for mail + webhook processing.

---

## 11 · Phased delivery roadmap

| Phase | Delivers | Unblocks in-app |
|---|---|---|
| **0 · Foundation** | Laravel 11 + Sail + Sanctum + middleware + error handler + CI/Pint/Larastan/Pest + `DomainServiceProvider` | — |
| **1 · Auth** | All 7 auth endpoints, OTP store, queued mail | Login/Signup/Forgot/OTP/Reset go live |
| **2 · Catalog** | products/categories + seeders + i18n + caching | Home/catalog/product detail |
| **3 · Cart + Favorites** | cart CRUD, promo, favorites; finalize cart JSON | Cart + wishlist (needs §12 client patch) |
| **4 · Checkout + Paymob** | orders, gateway, webhook | New checkout feature |
| **5 · Profile + hardening** | `GET /me` (+`PUT /me`), rate-limit tuning, Scribe docs | Profile screen |

---

## 12 · Flutter-side switchover

Flipping to the live backend is config-only **except one known gap**:

1. **Config** — add a production `AppConfig` (`useMockData:false`,
   `apiBaseUrl:<laravel url>`, `demoOtpCode:null`).
2. **Cart parsing (the one code change)** — the app's `CartRemoteDataSource`
   currently throws `_notImplemented()` for response parsing. Complete it to map
   the §4.3 `CartResource` shape (`items[].product/size/color/quantity`,
   `summary`) into `CartItem`/`AppliedPromo`. The shape is purpose-designed so
   `CartItem.lineId` (`product.id|size|colorValue`) reproduces server `line_id`.
3. Everything else (auth, catalog, favorites, promo, error handling) already
   matches and needs no client change.

---

## 13 · Risks & decisions to confirm

- **OTP without a reset token** — the app's `reset` sends only `{email,password}`,
  so the server binds to a recently-**verified** code record (short TTL). Slightly
  weaker than a one-time reset token; acceptable for this UX. Flag if stricter
  security is required.
- **Pagination vs. client-side search** — catalog returns the full set initially
  to preserve the app's client-side search; revisit when adding server search.
- **Paymob completion UX** — the app's checkout screen isn't built yet; the
  `payment.iframe_url`/`payment_key` contract assumes a WebView/redirect step to
  be designed in the Flutter checkout feature.

---

## 14 · Deliverable: the PDF file

On approval the plan can be produced as an **actual PDF** —
`MODIST-Laravel-Backend-Plan.pdf` in this folder (with its `.md` and `.html`
sources) — as follows:

1. Write this plan as a **self-contained, print-styled HTML** (cover page,
   numbered Table of Contents, page-break CSS, styled tables + monospace code
   blocks, Cairo/system font).
2. **Render it to PDF with Microsoft Edge headless** — guaranteed present on
   Windows 11 — via
   `msedge --headless --disable-gpu --print-to-pdf="…\MODIST-Laravel-Backend-Plan.pdf"
   --no-pdf-header-footer "…\MODIST-Laravel-Backend-Plan.html"`.
   Fallback order if Edge isn't found: Chrome headless → `pandoc` →
   `npx md-to-pdf`.
3. **Confirm** the `.pdf` exists and is non-empty, then open it for review.

---

## 15 · Verification

1. **PDF** — open `MODIST-Laravel-Backend-Plan.pdf` and confirm the cover, TOC,
   tables, and code blocks render and paginate cleanly across pages.
2. **(If/when implemented)** — `pest` green; `pint --test` + `larastan` clean;
   `php artisan migrate:fresh --seed` boots the demo dataset; hit each endpoint
   with the app pointed at the server (`useMockData:false`) and confirm
   login → browse catalog → add to cart → apply `MODIST10` → checkout works
   end-to-end; replay a signed Paymob webhook and confirm idempotent order
   transition.

---

*End of plan.*
