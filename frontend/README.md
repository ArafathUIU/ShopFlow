# ShopFlow Storefront

Next.js 16 storefront and admin dashboard for ShopFlow.

## Stack

- Next.js 16 (App Router) · React 19 · TypeScript
- Tailwind CSS v4 · shadcn/ui · Zustand · Axios · Lucide icons

## Getting Started

```bash
npm install
npm run dev
```

Open [http://localhost:3000](http://localhost:3000).

## Scripts

| Command | Description |
| --- | --- |
| `npm run dev` | Start development server |
| `npm run build` | Build for production |
| `npm run start` | Start production server |
| `npm run lint` | Run ESLint |

## Environment

The frontend expects the backend API at `NEXT_PUBLIC_API_URL` (defaults to
`http://localhost:8000/api/v1`).

## Structure

- `src/app/(shop)/` — customer storefront pages
- `src/app/(admin)/` — admin dashboard pages
- `src/app/dashboard/` — customer dashboard pages
- `src/app/auth/` — login, register, forgot password
- `src/components/` — shared UI components (product cards, cart, checkout)
- `src/lib/` — API client, stores, utilities
