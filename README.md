# ShopFlow

Production-style e-commerce & inventory management platform.

ShopFlow demonstrates modern software engineering practices beyond basic CRUD: a
customer storefront, a role-based admin platform, transactional inventory
management, payment webhooks, Redis caching, background jobs, rate limiting,
notifications, CI/CD, and a planned AI shopping assistant — all on a modular,
scalable monorepo.

## Tech Stack

| Layer | Technology |
| --- | --- |
| Frontend | Next.js 16, TypeScript, React, Tailwind CSS v4, shadcn/ui |
| Backend | Laravel 13, PHP 8.3+, REST APIs, Sanctum, Policies, Queues |
| Database | PostgreSQL 16 |
| Cache / Queue | Redis 7 |
| Payments | Stripe (webhooks, idempotent) |
| Storage | AWS S3 |
| Tests | Pest (backend), Playwright (E2E) |
| DevOps | Docker Compose, GitHub Actions |

## Repository Layout

```
shopflow/
├── frontend/          Next.js storefront + admin dashboard
├── backend/           Laravel 13 REST API
├── docs/
│   ├── architecture/  System design, decisions
│   ├── api/           API documentation
│   └── database/      Schema design, concurrency strategy
├── docker/            Dockerfiles & infra config
├── .github/           CI/CD workflows
├── docker-compose.yml
└── README.md
```

## Local Development

Infrastructure (PostgreSQL + Redis) runs in Docker; Laravel and Next.js run
natively for fast iteration.

```bash
# 1. Start infrastructure
docker compose up -d postgres redis

# 2. Backend (see backend/README.md)
cd backend
composer install
cp .env.example .env          # configure DB/Redis
php artisan key:generate
php artisan migrate --seed
php artisan serve

# 3. Frontend (see frontend/README.md)
cd ../frontend
npm install
npm run dev
```

### Full-stack Docker

```bash
docker compose up
```

## Development Phases

| Phase | Scope | Status |
| --- | --- | --- |
| 1 | Foundation — repo, docs, scaffolding, Docker Compose | Complete |
| 2 | Backend core — schema, models, auth, RBAC, error handling | Complete |
| 3 | Commerce engine — catalog, cart, inventory, orders, payments | Complete |
| 4 | Frontend — storefront, auth, cart, checkout | Complete |
| 5 | Administration — dashboard, product/inventory/order management, analytics | Complete |
| 6 | Production engineering — caching, queues, webhooks, rate limiting, API docs, tests | In Progress |
| 7 | DevOps — Docker optimization, deployment, monitoring, production config | Pending |
| 8 | AI — AI shopping assistant over existing APIs | Deferred |

## Documentation

- [Architecture overview](docs/architecture/overview.md)
- [Database schema](docs/database/schema.md)
- [Inventory concurrency](docs/database/inventory-concurrency.md)

## License

MIT — see [LICENSE](LICENSE).
