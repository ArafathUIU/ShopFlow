# ShopFlow API

Laravel 13 REST API powering ShopFlow's storefront, admin dashboard, payments,
and inventory.

## Stack

- Laravel 13 / PHP 8.3+ · PostgreSQL 16 · Redis 7 (cache + queue) · Sanctum
- Pest (tests) · Laravel Pint (style)

## Requirements

- PHP 8.3+ (compatible with 8.4)
- Composer 2.x
- Docker (for PostgreSQL + Redis) — `docker compose up -d postgres redis` from repo root

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

> The Docker Postgres maps to host port **5433** by default to avoid conflicts
> with local Postgres installs (e.g. Laravel Herd). Override via `POSTGRES_PORT`
> in the root `.env` if needed.

## Commands

```bash
php artisan migrate            # run migrations
php artisan db:seed            # seed roles, admin user, demo catalog
php artisan test               # Pest test suite
php artisan pint               # code style fixer
php artisan queue:work         # process background jobs
```

## API

All endpoints live under `/api/v1` (see `routes/api.php`). Domain route files
under `routes/api/v1/*.php` are registered as features are built.

### Response Envelope

Every endpoint returns a uniform JSON envelope (see `App\Support\ApiResponse`):

```json
{ "success": true,  "message": "OK", "data": { }, "meta": { } }  // success
{ "success": false, "message": "Error", "errors": { } }          // error
```

Errors are centralized in `bootstrap/app.php`: validation → 422, unauthenticated
→ 401, forbidden → 403, not found → 404, wrong method → 405, throttled → 429.

### Authentication

Sanctum bearer tokens (`Authorization: Bearer <token>`):

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/api/v1/auth/register` | Create account, returns `data.user` + `data.token` |
| POST | `/api/v1/auth/login` | Login, returns `data.user` + `data.token` |
| POST | `/api/v1/auth/logout` | Revoke current token |
| GET | `/api/v1/auth/me` | Current user |
| POST | `/api/v1/auth/email/verification-notification` | Resend verification email |
| POST | `/api/v1/auth/email/verify` | Verify email (`id` + `hash` from the email link) |
| POST | `/api/v1/auth/forgot-password` | Send password reset link |
| POST | `/api/v1/auth/reset-password` | Reset password (`token`, `email`, `password`) |

`register`, `login`, `forgot-password`, and the verification endpoint are rate
limited via `throttle` middleware.

Authorization: routes restricted with the `role` middleware, e.g.
`middleware('role:admin')` or `middleware('role:admin,manager')`.

## Demo Credentials

Seeded with `php artisan db:seed` (all passwords: `password`):

| Role | Email |
| --- | --- |
| Admin | `admin@shopflow.dev` |
| Manager | `manager@shopflow.dev` |
| Customer | `customer@shopflow.dev` |

## Conventions

- Money is stored as integer cents (see `docs/database/schema.md`).
- Responses use a uniform envelope; errors are centralized.
- Authentication via Laravel Sanctum bearer tokens; role-based authorization.
- Inventory mutations use transactions + `SELECT ... FOR UPDATE` row locks.
