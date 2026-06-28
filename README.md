# Tenant SaaS — Subscription Management & Accounting

Multi-tenant SaaS backend for **subscription billing** with **double-entry bookkeeping** and **deferred revenue** recognition. Built as a technical assessment project: Laravel 12 REST API + PostgreSQL, with an optional Vue 3 admin panel.

---

## Live demo & repository

| Deliverable | Link |
|-------------|------|
| GitHub (public) | _Add your repo URL here_ |
| Live demo | _Add your deployed URL here_ |

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, Eloquent ORM |
| Database | **PostgreSQL** (required) |
| Auth | Laravel Sanctum (session cookie + SPA) |
| Permissions | Spatie Laravel Permission |
| Frontend (optional) | Vue 3, Pinia, PrimeVue, Skote RTL Arabic UI |
| Multi-tenancy | Shared database, `tenant_id` column scoping |

---

## Requirements coverage

| Requirement | Status |
|-------------|--------|
| Company registration → Tenant + Admin user | ✅ `POST /api/register` |
| Data isolation per tenant | ✅ Repository/service scoping |
| Subscription plans CRUD | ✅ |
| Customers CRUD | ✅ |
| Subscriptions CRUD | ✅ |
| Automated monthly invoicing (cron simulation) | ✅ API + Artisan |
| Payment recording | ✅ |
| Chart of accounts (Cash, AR, Deferred Revenue, Subscription Revenue) | ✅ Auto-seeded |
| Double-entry: invoice issued | ✅ Dr AR / Cr Deferred Revenue |
| Double-entry: payment received | ✅ Dr Cash / Cr AR |
| Revenue recognition (end of month) | ✅ Dr Deferred / Cr Revenue |
| Income statement report | ✅ |
| Balance sheet report | ✅ |

---

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- PostgreSQL 14+

Create the database before migrating:

```sql
CREATE DATABASE tenant_saas;
```

---

## Local setup

```bash
# 1. Clone & install
git clone <your-repo-url>
cd tenant-saas
composer install
cp .env.example .env
php artisan key:generate

# 2. Configure PostgreSQL in .env (see below), then:
php artisan migrate --seed

# 3. Frontend
npm install
npm run build          # production
# npm run dev          # development with HMR

# 4. Run
php artisan serve
```

Open **http://127.0.0.1:8000** → login or register.

### Environment (`.env`)

Copy from `.env.example`. Minimum PostgreSQL settings:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tenant_saas
DB_USERNAME=postgres
DB_PASSWORD=your_password

APP_URL=http://127.0.0.1:8000
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,127.0.0.1:8000
```

For local development, `VITE_DEMO_HELPERS=true` shows the email verification code on screen (code defaults to `123456`).

---

## Demo accounts

After `php artisan migrate --seed`:

| Email | Password | Role |
|-------|----------|------|
| superadmin@gmail.com | 123456 | Super Admin (platform) |
| companyadmin@gmail.com | 123456 | Company Admin (Company 1) |

**Company 1 demo data** (customers, plans, subscriptions, invoices, payments, journal entries):

| Item | Details |
|------|---------|
| Customers | أحمد محمد (Pro), سارة علي (Basic) |
| Plans | Basic $50/mo, Pro $100/mo |
| Paid invoice | Full cycle: issued → paid → revenue recognized |
| Pending invoice | $50 — AR + Deferred Revenue only |

Re-seed demo data only:

```bash
php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder
```

---

## Authentication

All admin APIs require an authenticated session.

1. **Register** a new company: `POST /api/register`
2. **Login**: `POST /api/login`
3. **Verify email code**: `POST /api/verify-code` (after register)
4. Send cookies with every request (`credentials: include` in fetch/axios)

### Register payload

```json
POST /api/register
{
  "company": {
    "name": "Acme Corp",
    "email": "info@acme.com",
    "country": "EG",
    "city": "Cairo",
    "phone": "+201234567890"
  },
  "admin": {
    "name": "John Admin",
    "email": "admin@acme.com",
    "password": "password",
    "password_confirmation": "password"
  }
}
```

On success: creates **Tenant**, **Company_Admin** user, and default **Chart of Accounts**.

### Login payload

```json
POST /api/login
{
  "email": "companyadmin@gmail.com",
  "password": "123456",
  "remember": true
}
```

### Other auth routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user` | Current user |
| POST | `/api/logout` | Logout |
| POST | `/api/verify-code` | `{ "code": "123456" }` |
| POST | `/api/verify-code/resend` | Resend verification code |
| POST | `/api/forgot-password` | `{ "email": "..." }` |
| POST | `/api/reset-password` | Password reset |

---

## API overview

**Base URL:** `/api/admin`  
**Auth:** Sanctum session (cookie)  
**Response format:**

```json
{
  "status": 200,
  "message": "Success message",
  "data": { },
  "paginate": { "total": 0, "count": 0, "per_page": 10, "current_page": 1, "total_pages": 1 }
}
```

**Query params (list endpoints):** `search`, `page`, `paginate` (max 50)

**Super Admin:** users without `tenant_id` see all tenants; pass `tenant_id` on billing/reports when acting on a specific company.

---

### Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/dashboard` | Stats + recent records |

---

### Customers

| Method | Endpoint |
|--------|----------|
| GET | `/api/admin/customers` |
| GET | `/api/admin/customers/options` |
| POST | `/api/admin/customers` |
| GET | `/api/admin/customers/{id}` |
| PUT/PATCH | `/api/admin/customers/{id}` |
| DELETE | `/api/admin/customers/{id}` |

---

### Subscription plans

| Method | Endpoint |
|--------|----------|
| GET | `/api/admin/subscription-plans` |
| GET | `/api/admin/subscription-plans/options` |
| POST | `/api/admin/subscription-plans` |
| GET | `/api/admin/subscription-plans/{id}` |
| PUT/PATCH | `/api/admin/subscription-plans/{id}` |
| DELETE | `/api/admin/subscription-plans/{id}` |

---

### Subscriptions

| Method | Endpoint |
|--------|----------|
| GET | `/api/admin/subscriptions` |
| GET | `/api/admin/subscriptions/options` |
| POST | `/api/admin/subscriptions` |
| GET | `/api/admin/subscriptions/{id}` |
| PUT/PATCH | `/api/admin/subscriptions/{id}` |
| DELETE | `/api/admin/subscriptions/{id}` |

Body example for create:

```json
{
  "customer_id": 1,
  "subscription_plan_id": 1,
  "start_date": "2026-01-01",
  "status": "active"
}
```

---

### Invoices

| Method | Endpoint |
|--------|----------|
| GET | `/api/admin/invoices` |
| POST | `/api/admin/invoices` |
| GET | `/api/admin/invoices/{id}` |
| PUT/PATCH | `/api/admin/invoices/{id}` |
| DELETE | `/api/admin/invoices/{id}` |

Non-draft invoices automatically post: **Dr Accounts Receivable / Cr Deferred Revenue**.

---

### Payments

| Method | Endpoint |
|--------|----------|
| GET | `/api/admin/payments` |
| POST | `/api/admin/payments` |
| GET | `/api/admin/payments/{id}` |
| PUT/PATCH | `/api/admin/payments/{id}` |
| DELETE | `/api/admin/payments/{id}` |

When `status` is `paid`, posts: **Dr Cash / Cr Accounts Receivable**.

---

### Accounts & journal entries

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST/PUT/DELETE | `/api/admin/accounts` | Chart of accounts |
| GET | `/api/admin/journal-entries` | List entries |
| POST | `/api/admin/journal-entries` | Manual balanced entry |
| GET | `/api/admin/journal-entries/{id}` | Entry detail |
| DELETE | `/api/admin/journal-entries/{id}` | Delete manual entry only |

---

### Billing (cron simulation)

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| POST | `/api/admin/billing/generate-invoices` | `{ "as_of": "2026-06-01" }` | Bill active subscriptions where `next_billing_date <= as_of` |
| POST | `/api/admin/billing/recognize-revenue` | `{ "as_of": "2026-06-30", "tenant_id": 1 }` | End-of-month revenue recognition |

`tenant_id` required for Super Admin. `as_of` is optional (defaults to today).

---

### Financial reports

| Method | Endpoint | Query | Description |
|--------|----------|-------|-------------|
| GET | `/api/admin/reports/income-statement` | `from`, `to` | Subscription Revenue for period |
| GET | `/api/admin/reports/balance-sheet` | `as_of` | Cash, AR, Deferred Revenue balances |

Super Admin: pass `tenant_id` query param.

---

### Tenants (Super Admin only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/tenants` | List companies |
| GET | `/api/admin/tenants/options` | Dropdown options |
| GET | `/api/admin/tenants/{id}` | Company detail |
| DELETE | `/api/admin/tenants/{id}` | Suspend or force-delete (`action` in body) |

---

### Users & roles

| Method | Endpoint |
|--------|----------|
| GET/POST/PUT/DELETE | `/api/admin/users` |
| GET/POST/PUT/DELETE | `/api/admin/roles` |
| GET | `/api/admin/roles/options` |
| GET | `/api/admin/permissions` |
| GET/POST | `/api/admin/profile` |
| POST | `/api/admin/profile/company` |

---

## Accounting flow

Default chart of accounts (seeded on company registration):

| Code | Account | Type |
|------|---------|------|
| 1000 | Cash | Asset |
| 1100 | Accounts Receivable | Asset |
| 2100 | Deferred Revenue | Liability |
| 4000 | Subscription Revenue | Revenue |

### Automated journal entries

| Event | Entry |
|-------|-------|
| **Invoice issued** (status ≠ draft) | Dr AR / Cr Deferred Revenue |
| **Payment received** (status = paid) | Dr Cash / Cr AR |
| **Revenue recognition** (end of month) | Dr Deferred Revenue / Cr Subscription Revenue |

Idempotent: duplicate entries for the same invoice/payment are rejected.

---

## Artisan commands (scheduled billing)

Simulates cron jobs for production:

```bash
# Generate recurring invoices
php artisan subscriptions:generate-invoices
php artisan subscriptions:generate-invoices --as-of=2026-06-01

# Recognize deferred revenue
php artisan subscriptions:recognize-revenue
php artisan subscriptions:recognize-revenue --as-of=2026-06-30 --tenant=1
```

Optional Laravel scheduler (add to `routes/console.php` for production):

```php
Schedule::command('subscriptions:generate-invoices')->daily();
Schedule::command('subscriptions:recognize-revenue')->monthlyOn(28, '23:59');
```

---

## Admin UI (bonus)

Vue SPA served by Laravel at `/admin/*`:

| Page | Route |
|------|-------|
| Dashboard | `/admin/dashboard` |
| Customers | `/admin/customers` |
| Subscription plans | `/admin/subscription-plans` |
| Subscriptions | `/admin/subscriptions` |
| Invoices | `/admin/invoices` |
| Payments | `/admin/payments` |
| Billing operations | `/admin/billing` |
| Journal entries | `/admin/journal-entries` |
| Income statement | `/admin/income-statement` |
| Balance sheet | `/admin/balance-sheet` |
| Tenants | `/admin/tenants` (Super Admin) |

---

## Design decisions

### Multi-tenancy

- **Shared database, shared schema** with `tenant_id` on all tenant-owned tables.
- Scoping enforced in `TenantScopedRepository` + `HandlesTenantScopedAdmin` trait — every query filters by the authenticated user's `tenant_id`. Super Admin (`tenant_id = null`) bypasses scope but must pass `tenant_id` for tenant-specific billing/reports.
- Company registration atomically creates Tenant, owner user, role, and chart of accounts.

### Deferred revenue model

Subscription revenue is **not** recognized at invoice time. The invoice creates a liability (Deferred Revenue). Revenue is recognized at month-end via `recognize-revenue`, matching accrual accounting for prepaid subscriptions.

### Architecture

```
Route → Controller → Service → Repository → Model
```

Form Requests for validation, API Resources for JSON shape, Enums for statuses.

### Auth

Sanctum **stateful** SPA authentication (session cookies) rather than bearer tokens — simpler for the bundled Vue admin and CSRF protection.

---

## Deployment (Render / Railway / Heroku)

General steps:

1. Push to public GitHub repo
2. Create PostgreSQL instance on the platform
3. Set environment variables from `.env.example`
4. Build command: `composer install --no-dev && npm ci && npm run build`
5. Start command: `php artisan migrate --force --seed && php artisan serve --host=0.0.0.0 --port=$PORT`
6. Set `APP_URL`, `SANCTUM_STATEFUL_DOMAINS` to your production domain
7. Set `VITE_DEMO_HELPERS=false` in production

Health check: `GET /up`

---

## Project structure

```
app/
├── Http/Controllers/Admin/    # REST controllers
├── Http/Requests/             # Validation
├── Http/Resources/            # API transformers
├── Services/Admin/            # Business logic + accounting
├── Repositories/Admin/        # Data access + tenant scoping
├── Support/                   # TenantScope, ChartOfAccounts
└── Console/Commands/          # Billing artisan commands

routes/
├── api.php                    # Auth routes
└── admin.php                  # /api/admin/* routes

resources/js/                  # Vue 3 admin SPA
database/seeders/              # Roles, users, demo data
```

---

## License

MIT — assessment project.
