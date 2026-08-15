# ShopFlow — Architecture Overview

> Version 1.0 · 2026-08-15

## 1. System Context

```
                         ┌──────────────────────┐
                         │      Customer        │
                         └──────────┬───────────┘
                                    │
                                    ▼
                         ┌──────────────────────┐
                         │      Next.js         │
                         │   TypeScript / React │
                         └──────────┬───────────┘
                                    │
                              REST / JSON
                                    │
                                    ▼
                         ┌──────────────────────┐
                         │    Laravel API       │
                         │     Application      │
                         └──────────┬───────────┘
              ┌─────────────────────┼─────────────────────┐
              │                     │                     │
              ▼                     ▼                     ▼
       ┌─────────────┐       ┌─────────────┐       ┌─────────────┐
       │ PostgreSQL  │       │    Redis    │       │   Stripe    │
       │  Database   │       │ Cache/Queue │       │  Payments   │
       └─────────────┘       └─────────────┘       └─────────────┘
                                    │
                                    ▼
                           ┌──────────────────┐
                           │ Background Jobs   │
                           │ Notifications     │
                           │ Analytics         │
                           └──────────────────┘
```

## 2. Application Architecture

The backend is a **modular monolith**. Domains are clearly separated inside a
single deployable application; if a domain later needs to scale independently, the
boundaries already exist.

```
HTTP Request
    │
    ▼
Routes (routes/api.php — versioned /api/v1)
    │
    ▼
Middleware  (auth, role, throttle, log)
    │
    ▼
Controllers (thin — validate + delegate)
    │
    ▼
Services    (business logic — checkout, inventory, cart, payment)
    │
    ├── Policies        (authorization)
    ├── Models          (persistence)
    ├── Jobs / Events   (async side effects)
    └── Clients         (Stripe, S3, Mail)
```

### Separation of concerns

| Layer | Responsibility |
| --- | --- |
| Presentation | Next.js (storefront + admin). Consumes APIs only — never the DB. |
| HTTP / API | Controllers, Form Requests, middlewares, resources |
| Application | Services orchestrate domain operations |
| Domain | Business rules: pricing, stock reservation, order lifecycle |
| Persistence | Eloquent models, migrations |
| Infrastructure | Redis, PostgreSQL, Stripe, S3, mail, queue workers |

## 3. Key Patterns

| Concern | Pattern |
| --- | --- |
| Inventory writes | Transactions + `SELECT ... FOR UPDATE` row locks (see `docs/database/inventory-concurrency.md`) |
| Payments | Never trust the frontend — Stripe webhooks update order/payment state; idempotent via `webhook_events` unique constraint |
| Caching | Cache-aside on Redis with TTL; invalidated on writes |
| Async work | Redis queue → Jobs (email, invoices, analytics) |
| Search/filter | PostgreSQL-based (LIKE/ILIKE + indexes now; full-text later if needed) |
| Money | Integer cents stored, `Money` value object in PHP |
| API responses | Uniform JSON envelope + centralized error handling |

## 4. API Versioning

All routes are prefixed with a version: `/api/v1/...`. Versioning lives in the URL
(`routes/api.php` grouped under `prefix('v1')`) to keep clients unambiguous and
cheap to serve. Breaking changes increment the version.

```
/api/v1/auth        /api/v1/products     /api/v1/categories
/api/v1/cart        /api/v1/orders       /api/v1/payments
/api/v1/users       /api/v1/inventory    /api/v1/coupons
/api/v1/analytics   /api/v1/admin
```

## 5. Domain Boundaries

| Domain | Owns | Notes |
| --- | --- | --- |
| Identity | users, addresses, roles | Sanctum tokens; RBAC |
| Catalog | categories, products, product_images | S3-backed images |
| Inventory | inventories, inventory_transactions | Concurrency-critical |
| Cart | carts, cart_items, wishlist_items | |
| Pricing | coupons, coupon_usages | Percent/fixed, caps, limits |
| Sales | orders, order_items, order_status_history, payments | Snapshot-based |
| Payments | webhook_events, Stripe integration | Idempotent ingestion |
| Analytics | derived aggregates | Cached, non-blocking |

## 6. Data Flow: Checkout

```
1. POST /api/v1/checkout            (auth: customer)
2. Validate cart, prices, coupons, addresses
3. Begin transaction
4. Lock inventory rows (FOR UPDATE, ordered)
5. Reserve stock
6. Create order + items + history (snapshots)
7. Create Stripe PaymentIntent (amount from server)
8. Commit
9. Dispatch async: confirmation email, invoice, analytics
10. Customer pays → Stripe webhook → verify signature → mark paid → convert reservation
```

## 7. Scalability Notes

- **Stateless API** — any number of `shopflow-api` instances behind a load
  balancer.
- **Redis-backed cache + queue** — cache and jobs scale independently.
- **Background workers** — a separate queue worker container can be scaled without
  touching the web tier.
- **Docker Compose** provides the full stack locally; the same images deploy to
  production.

## 8. Observability

- Structured application logs (contextual: request id, user, duration).
- Metrics: API response times, failed requests, queue failures, payment failures.
- Logged events include authentication failures and sensitive admin actions
  (audit log).
