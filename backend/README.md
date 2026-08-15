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

All endpoints live under `/api/v1` (see `routes/api.php`). Health check:
`GET /api/v1/health`.

Domain route files under `routes/api/v1/*.php` are registered as features are
built.

## Conventions

- Money is stored as integer cents (see `docs/database/schema.md`).
- Responses use a uniform envelope; errors are centralized.
- Authentication via Laravel Sanctum bearer tokens; role-based authorization.
- Inventory mutations use transactions + `SELECT ... FOR UPDATE` row locks.
