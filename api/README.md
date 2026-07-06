# MODIST API

MODIST is a Laravel 11 **multi-tenant** e-commerce REST API built with Clean Architecture and SOLID principles. It provides token-based authentication via Laravel Sanctum (with rotating refresh tokens), a fully bilingual (Arabic / English) product catalog, a server-side cart with promo codes and favorites, an in-app notification inbox, a white-label storefront-config endpoint, and checkout through a pluggable payment-processor layer (tokenized card / cash on delivery). Responses are localized through the `Accept-Language` header, and the domain logic is decoupled from the framework so business rules stay testable and framework-agnostic.

### Multi-tenancy

Every domain table carries a `tenant_id`, and a global `BelongsToTenant` scope isolates each query to the current tenant — a query can never cross tenants. The tenant is resolved per request (in order) from the `X-Tenant` header, the request subdomain (`acme.api.example.com`), or the configured default (`DEFAULT_TENANT_SLUG`, default `modist`). The Flutter app sends no tenant today, so it resolves to the default tenant; its per-tenant white-label config is served by `GET /settings/app`.

## Requirements

- PHP 8.3+
- Composer
- MySQL 8 (or SQLite for tests)

## Setup on Windows

```
cd C:\Users\HP\SHOP\api
composer install
copy .env.example .env
php artisan key:generate
# configure DB_* in .env, then:
php artisan migrate --seed
php artisan serve
```

## API base URL

All endpoints are served under:

```
http://localhost:8000/api/v1
```

## Headers

Send the following headers with your requests:

| Header            | Value                       | Notes                                      |
| ----------------- | --------------------------- | ------------------------------------------ |
| `Accept-Language` | `en` or `ar`                | Selects the language of localized messages |
| `Authorization`   | `Bearer <token>`            | Required on authenticated endpoints        |
| `Content-Type`    | `application/json`          | For requests with a JSON body              |

## Architecture

The codebase is organized into four cooperating layers. Dependencies always point inward toward the Domain, keeping business rules independent of the framework and infrastructure details.

1. **HTTP layer** (`app/Http`) — Controllers, Form Requests, API Resources, and middleware. Translates HTTP requests into Action calls and serializes the results back to JSON. No business logic lives here.
2. **Application / Actions layer** (`app/Application`) — Single-purpose Action classes orchestrate use cases (register, login, add-to-cart, checkout, etc.). They coordinate domain objects and infrastructure through interfaces.
3. **Domain layer** (`app/Domain`) — Entities, value objects, domain services, and repository/contract interfaces. Pure business rules with no framework dependencies.
4. **Infrastructure layer** (`app/Infrastructure`) — Eloquent repository implementations, the payment processors (card / cash), the refresh-token store, mailers, and other adapters that fulfill Domain contracts.

Folder map:

```
app/
├── Application/        # Action classes (use cases)
├── Domain/             # Entities, value objects, services, contracts
│   ├── Auth/
│   ├── Catalog/
│   ├── Cart/
│   ├── Order/
│   └── Payment/
├── Infrastructure/     # Eloquent repositories, payment processors, adapters
│   ├── Persistence/
│   ├── Payment/
│   └── Notifications/
└── Http/               # Controllers, Requests, Resources, Middleware
    ├── Controllers/
    ├── Requests/
    ├── Resources/
    └── Middleware/
```

## Endpoints

Summary of the §4.1 routes (all paths are relative to `http://localhost:8000/api/v1`).

| Method   | Path                      | Auth              |
| -------- | ------------------------- | ----------------- |
| POST     | `/auth/register`          | Public (no token — emails OTP) |
| POST     | `/auth/register/verify`   | Public (→ token + user)        |
| POST     | `/auth/register/resend`   | Public            |
| POST     | `/auth/login`             | Public            |
| POST     | `/auth/refresh`           | Public            |
| POST     | `/auth/logout`            | Bearer (→ 204)    |
| POST     | `/auth/password/forgot`   | Public            |
| POST     | `/auth/password/verify`   | Public            |
| POST     | `/auth/password/resend`   | Public            |
| POST     | `/auth/password/reset`    | Public            |
| GET      | `/settings/app`           | Public            |
| GET      | `/categories`             | Public            |
| GET      | `/products`               | Public            |
| GET      | `/products/{id}`          | Public            |
| GET      | `/products/{id}/reviews`  | Public            |
| POST     | `/products/{id}/reviews`  | Bearer            |
| GET      | `/home/banners`           | Public            |
| GET      | `/cart`                   | Bearer            |
| POST     | `/cart`                   | Bearer            |
| PATCH    | `/cart/{lineId}`          | Bearer            |
| DELETE   | `/cart/{lineId}`          | Bearer            |
| DELETE   | `/cart`                   | Bearer            |
| POST     | `/cart/promo`             | Bearer            |
| GET      | `/favorites`              | Bearer            |
| POST     | `/favorites`              | Bearer            |
| DELETE   | `/favorites`              | Bearer (→ 204)    |
| POST     | `/checkout`               | Bearer            |
| GET      | `/notifications`          | Bearer            |
| POST     | `/notifications/read`     | Bearer            |
| GET      | `/notifications/count`    | Bearer            |
| POST     | `/notifications/devices`  | Bearer (→ 204)    |
| DELETE   | `/notifications/devices`  | Bearer (→ 204)    |
| GET      | `/me`                     | Bearer            |
| PATCH    | `/me`                     | Bearer            |
| GET      | `/me/orders`              | Bearer            |

## Testing

```
php artisan test
```

The test suite runs against an in-memory SQLite database, so no external database is required.

## Static analysis

```
vendor/bin/phpstan analyse
```

## Code style

```
vendor/bin/pint
```

## Seeded demo data

Running `php artisan migrate --seed` populates the database with demo content (all scoped to the default tenant):

- **Tenant:** `modist` (the default store) plus its white-label settings served by `GET /settings/app`.
- **Promo codes:** `MODIST10` (10% off), `WELCOME15` (15% off), `XX032910` (20% off).
- **Categories (5):** `tshirt`, `pants`, `jacket`, `shorts`, `shoes`.
- **Products:** roughly 8 demo products spread across the categories, each with Arabic and English fields.

## Payments

Checkout (`POST /checkout`) recomputes the order total **server-side** from the user's cart (the client `amount` is a display hint only) and routes payment through a pluggable `PaymentProcessor`:

- `payment_method: "cash"` — cash on delivery; the order is created with `payment_status: pending`.
- `payment_method: "creditCard"` — the client tokenizes the card with the PSP SDK and sends only `card.payment_token`. The `CardPaymentProcessor` charges it server-side (currently a stub that marks the order paid when a token is present — wire a real PSP such as Stripe / Moyasar / Checkout.com in `app/Infrastructure/Payment/CardPaymentProcessor.php`).

Adding a new provider is a new `PaymentProcessor` implementation plus a tag entry in `DomainServiceProvider` — no change to the checkout action (OCP). Never POST raw card data; tokenize client-side to stay out of PCI scope.

## Auth & refresh tokens

Sign-up is OTP-gated: `POST /auth/register` creates an **unverified** account and emails a 4-digit code — it returns **no token**. The client posts the code to `POST /auth/register/verify`, which activates the account and returns `{ token, refresh_token, user }` (same shape as login). Login is rejected for an unverified account (`403 auth.email_not_verified`). Login / register-verify return `{ token, refresh_token, user }` (flat, not `data`-wrapped). The access token is a Sanctum token; the `refresh_token` is an opaque, hashed, rotating credential. `POST /auth/refresh` with `{ refresh_token }` revokes the presented token and returns a fresh `{ token, refresh_token, user }`. Logout revokes the current access token and all of the user's refresh tokens. Lifetime is controlled by `AUTH_REFRESH_TTL_DAYS` (default 30).

## Storefront config (`GET /settings/app`)

Serves the current tenant's white-label config — `app_name`, `currency`, `brand` colours (only the overridden ones are returned), and feature `flags` (each defaults to `true`). Drives theming and feature gating in the app with no rebuild.
