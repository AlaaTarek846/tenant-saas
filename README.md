# Tenant SaaS — Subscription Management & Accounting

Multi-tenant SaaS backend for **subscription billing** with **double-entry bookkeeping** and **deferred revenue** recognition. Built as a technical assessment project: Laravel 12 REST API + PostgreSQL, with an optional Vue 3 admin panel.

---

## Live demo & repository

| Deliverable | Link |
|-------------|------|
| GitHub (public) | https://github.com/AlaaTarek846/tenant-saas |
| Live demo | _Add your deployed URL here_ |

---

## How to run the project (local)

### 1) Prerequisites

| Tool | Version |
|------|---------|
| PHP | 8.2+ (with extensions: `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`) |
| Composer | latest |
| Node.js & npm | 18+ |
| PostgreSQL | 14+ |

Create the database once:

```sql
CREATE DATABASE tenant_saas;
```

### 2) Clone & install dependencies

```bash
git clone https://github.com/AlaaTarek846/tenant-saas.git
cd tenant-saas

composer install
cp .env.example .env
php artisan key:generate
```

### 3) Configure `.env`

Open `.env` and set PostgreSQL credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tenant_saas
DB_USERNAME=postgres
DB_PASSWORD=your_password

APP_URL=http://127.0.0.1:8000
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,127.0.0.1:8000,localhost:8000
```

For local development (shows verification code on screen):

```env
VITE_DEMO_HELPERS=true
VITE_DEMO_VERIFY_CODE=123456
```

### 4) Database & storage

```bash
php artisan migrate --seed
php artisan storage:link
```

**Why `storage:link`?**  
Creates a symbolic link from `public/storage` → `storage/app/public`. Required so uploaded files (user avatars, company logos) are accessible in the browser. Run it once after clone; run again on a new server if the link is missing.

On **Windows (Laragon)**: run the terminal **as Administrator** if the command fails with a permission/symlink error.

### 5) Frontend assets

**Production / first run (single server):**

```bash
npm install
npm run build
```

**Development (hot reload):**

```bash
npm install
npm run dev
```

Keep `npm run dev` running in a **second terminal** while developing the Vue admin UI.

### 6) Start the application

**Option A — Laravel built-in server:**

```bash
php artisan serve
```

Open: **http://127.0.0.1:8000**

**Option B — Laragon:**  
Point the document root to `public/` (e.g. virtual host `tenant-saas.test`) and ensure PostgreSQL is running in Laragon. You still need steps 2–5 above.

### 7) Log in

After `migrate --seed`, use:

| Email | Password | Role |
|-------|----------|------|
| superadmin@gmail.com | 123456 | Super Admin |
| companyadmin@gmail.com | 123456 | Company Admin |

Admin panel: **http://127.0.0.1:8000/admin/dashboard**

---

### Quick start (copy-paste)

```bash
git clone https://github.com/AlaaTarek846/tenant-saas.git
cd tenant-saas
composer install
cp .env.example .env
php artisan key:generate
# edit .env → DB_* and APP_URL

php artisan migrate --seed
php artisan storage:link

npm install
npm run build
php artisan serve
```

With Vite dev server (two terminals):

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

---

### Troubleshooting

| Problem | Fix |
|---------|-----|
| `could not connect to server` (PostgreSQL) | Start PostgreSQL; verify `DB_*` in `.env` |
| 419 / CSRF on login | Match `APP_URL` and `SANCTUM_STATEFUL_DOMAINS` to the URL in the browser |
| Avatars/logos not showing | Run `php artisan storage:link` |
| Blank page / no styles | Run `npm run build` or keep `npm run dev` running |
| `storage:link` fails on Windows | Run terminal as Administrator, or enable Developer Mode in Windows settings |

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

See **[How to run the project (local)](#how-to-run-the-project-local)** above for the full step-by-step guide.

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- PostgreSQL 14+

---

## Demo accounts

After `php artisan migrate --seed` and `php artisan storage:link`:

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
5. Release/start command (run migrations, link storage, then serve):

```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=$PORT
```

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
