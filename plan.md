# MedCore — Multi-Tenant Healthcare & Hospital ERP: Architecture & Implementation Plan

## Context

MedCore is a greenfield, enterprise-grade, **multi-tenant** Healthcare & Hospital ERP. The working directory (`/Users/sakib/Projects/MedCore`) is currently empty, so this plan establishes the system from zero.

**Locked decisions (confirmed with stakeholder):**
- **Tenancy model:** Single-database, **row-level tenancy** enforced by Laravel Global Scopes + a `BelongsToTenant` trait + tenant-resolution middleware. Subdomain-based tenant identification (`tenant1.medcore.local`).
- **Stack:** PHP 8.3+, Laravel 11+, MySQL 8.0, Redis, Docker, Nginx, Inertia.js (**React 18 + TypeScript + Tailwind CSS**).
- **RBAC:** `spatie/laravel-permission`, made tenant-aware via the team-id (`tenant_id`) feature.
- **Real-time:** **Laravel Reverb** (first-party, self-hosted WebSocket server) for Phase 4.
- **Compliance posture:** **HIPAA-aligned** — field-level PHI encryption, blind-index search, immutable audit trail, access logging, retention controls.
- **Deployment scope:** `docker-compose` for local dev **+** a hardened single-host production compose (Nginx TLS termination).

**Intended outcome:** A production-ready, phase-sequenced engineering roadmap with concrete, copy-ready architectural decisions — not generic advice.

---

## 1. SYSTEM ARCHITECTURE & DATA ISOLATION PLAN

### 1.1 Single-Database Row-Level Tenancy

Every tenant-owned table carries a non-nullable, indexed `tenant_id` (`BIGINT UNSIGNED`, FK → `tenants.id`). Isolation is enforced at three layers so a missed `where` clause can never leak cross-tenant data.

**Layer 1 — Tenant resolution middleware (`IdentifyTenant`)**
- Registered early in the `web` group, after session start, before auth.
- Resolves tenant from the request **subdomain** (`tenant1.medcore.local` → `tenant1`). Looks up `tenants` by `slug`/`domain`, rejects unknown/suspended tenants with 404/403.
- Binds the resolved `Tenant` into the container as a scoped singleton: `app()->instance('currentTenant', $tenant)`. Also stored on a static `TenantManager` (`Tenant::current()`).
- Central/admin domain (`admin.medcore.local`, `medcore.local`) bypasses tenant binding and routes to the Super Admin panel.

**Layer 2 — `BelongsToTenant` trait + Global Scope (the core guarantee)**
- A `TenantScope` (implements `Illuminate\Database\Eloquent\Scope`) auto-injects `where tenant_id = <current>` into **every** query for models using the trait.
- The trait's `booted()` hook:
  - `static::addGlobalScope(new TenantScope)` — read isolation.
  - `static::creating(...)` — auto-stamps `tenant_id` from `Tenant::current()` on insert, so application code never sets it manually.
- When no tenant context exists (CLI/queue), the scope must **fail loud** unless explicitly running in a central context — guards against accidental global queries. Queue jobs re-establish tenant context from a serialized `tenant_id` (see §4 race-condition / jobs note).

```php
trait BelongsToTenant {
    protected static function bootBelongsToTenant(): void {
        static::addGlobalScope(new TenantScope);
        static::creating(function ($model) {
            if (!$model->tenant_id && app()->bound('currentTenant')) {
                $model->tenant_id = app('currentTenant')->id;
            }
        });
    }
}
```

**Layer 3 — Defense in depth**
- MySQL FK constraints on `tenant_id` prevent orphan rows.
- A test-suite invariant (architecture test) asserts every PHI/tenant table model uses `BelongsToTenant`.
- Optional: a global query observer in non-prod that logs any tenant-table query missing a `tenant_id` predicate.

**Escaping the scope (rare, deliberate):** `Model::withoutTenant()` helper wrapping `withoutGlobalScope(TenantScope::class)` — used only in Super Admin reporting and seeders, never in tenant request paths.

### 1.2 Authentication & RBAC

- **Auth:** Laravel session-based auth via Inertia (`laravel/breeze` Inertia-React starter as the seed). 2FA (TOTP) for clinical/admin roles. Optional SSO (SAML/OIDC) deferred.
- **Users & tenancy:** `users.tenant_id` scopes a user to one tenant. **Super Admins** have `tenant_id = NULL` and a global guard. A user belongs to exactly one tenant (simplest, safest for PHI); cross-tenant staff handled as separate accounts.
- **RBAC:** `spatie/laravel-permission` with **teams mode enabled**, mapping the team key to `tenant_id`. This makes the same role name (e.g. "Doctor") resolve to tenant-scoped permission rows, so Tenant A's "Doctor" cannot inherit Tenant B's grants.

| Role | Scope | Representative permissions |
|---|---|---|
| **Super Admin** | Global (no tenant) | Provision/suspend tenants, manage plans, platform metrics, impersonate (audited) |
| **Tenant Admin** | One tenant | Manage tenant users/roles, departments, billing config, all module access |
| **Doctor** | One tenant | View/edit assigned patients' EMR, write prescriptions, orders, notes |
| **Receptionist** | One tenant | Register patients, appointments, check-in/out, basic billing — **no clinical notes** |
| **Pharmacist** | One tenant | Dispense, manage inventory/batches — no clinical EMR edit |
| **Nurse / Lab / Cashier** | One tenant | Module-scoped (added per phase) |

- Enforcement: route middleware (`role:`, `permission:`) + **Policies** for record-level checks (e.g. a Doctor can only open EMRs for patients in their department/assignment). Inertia shares the resolved permission set to the frontend (`HandleInertiaRequests::share`) so the UI hides unauthorized actions — server Policies remain the source of truth.

### 1.3 Secure Handling of Sensitive Data (HIPAA-aligned)

- **Encryption at rest — field level:** PHI columns (name, DOB, SSN/national ID, address, phone, diagnoses, notes) use Laravel's `encrypted` cast (AES-256-GCM via `APP_KEY`). Cast at the model so plaintext never hits logs/queries.
- **Searchable encryption (blind index):** Encrypted columns aren't queryable. For lookups (e.g. find patient by national ID/phone), store a deterministic **HMAC-SHA256 blind index** column (`national_id_index`) keyed by a separate secret; query by hashing the search term. Exact-match only — acceptable for identifiers.
- **Key management:** `APP_KEY` + a distinct PHI/blind-index key, injected via Docker secrets / env, never committed. Plan for key rotation via Laravel's key-rotation support (re-encrypt job).
- **Encryption in transit:** TLS everywhere — Nginx terminates HTTPS; internal MySQL/Redis on a private Docker network.
- **Immutable audit trail:** An `audit_logs` table (append-only; DB user lacks UPDATE/DELETE on it) capturing actor, tenant, action, model, before/after (PHI redacted to field names), IP, timestamp. Driven by Eloquent model events via a `Auditable` trait (or `owen-it/laravel-auditing`). **Access logging** specifically records every PHI *read* for HIPAA.
- **Data lifecycle:** Soft deletes + configurable retention; encrypted backups; least-privilege MySQL accounts (app account has no DDL).

---

## 2. INFRASTRUCTURE & DOCKER ORCHESTRATION

### 2.1 Container topology (`docker-compose.yml`)

| Service | Image / base | Role |
|---|---|---|
| **nginx** | `nginx:1.27-alpine` | Reverse proxy, TLS termination, static asset serving, wildcard subdomain routing, WS upgrade proxy to Reverb |
| **app** (php-fpm) | Custom `Dockerfile` (`php:8.3-fpm-alpine` + ext: pdo_mysql, redis, gd, bcmath, opcache, intl) | Serves Laravel via FastCGI; the canonical app image reused by worker/scheduler/reverb |
| **mysql** | `mysql:8.0` | Primary datastore; private network only; named volume `mysql-data` |
| **redis** | `redis:7-alpine` | Cache, session store, queue backend, Reverb scaling/broadcast backplane, locks |
| **queue** | same image as `app`, cmd `php artisan queue:work` | Async jobs (notifications, PDF invoices, re-encryption, report generation). Scale via `--scale queue=N` |
| **scheduler** | same image, cmd loops `schedule:run` | Cron (`schedule:run` each minute) — retention, batch-expiry alerts, digest reports |
| **reverb** | same image, cmd `php artisan reverb:start` | WebSocket server (Phase 4); Nginx proxies `/app` WS traffic here; uses Redis for horizontal scaling |
| **vite** (dev only) | `node:20-alpine` | HMR dev server for Inertia/React/TS; excluded from prod compose |

- **Networks:** one bridge network; only `nginx` (and `vite` in dev) expose host ports. MySQL/Redis are internal-only.
- **Volumes:** `mysql-data`, `redis-data`; bind-mount source in dev, baked image (`COPY` + `composer install --no-dev` + `npm run build`) in prod.
- **Config split:** `docker-compose.yml` (base) + `docker-compose.override.yml` (dev: bind mounts, vite, xdebug) + `docker-compose.prod.yml` (prod: built image, restart policies, secrets, no source mounts). Health checks on mysql/redis with `depends_on: condition: service_healthy`.

### 2.2 Nginx tenant subdomain strategy

- DNS / local `/etc/hosts`: wildcard `*.medcore.local` → host. In prod, a wildcard DNS A record + **wildcard TLS cert** (`*.medcore.local`).
- Single `server` block with `server_name *.medcore.local medcore.local;` — Nginx passes the full `Host` header through FastCGI; **Laravel** (not Nginx) resolves the tenant from the subdomain (§1.1). Keeps Nginx tenant-agnostic and zero-touch when tenants are added.
- `root /var/www/html/public; try_files $uri /index.php?$query_string;` → `fastcgi_pass app:9000`.
- **WebSocket location** for Reverb: a `location /app { proxy_pass http://reverb:8080; }` block with `proxy_set_header Upgrade`/`Connection "upgrade"`, `proxy_http_version 1.1`, long read timeouts.
- TLS: prod terminates HTTPS at Nginx (Let's Encrypt wildcard via DNS-01, or provided cert), HSTS, modern cipher suite, HTTP→HTTPS redirect.

---

## 3. PHASE-WISE IMPLEMENTATION ROADMAP

### Phase 1 — Foundation, Infrastructure & Multi-Tenancy

**Objectives:** Bootable Dockerized Laravel + Inertia app; tenancy enforced and proven by tests; auth + RBAC scaffolding; Super Admin tenant provisioning.

- **Backend:**
  - Scaffold Laravel 11 + Breeze (Inertia-React-TS). Install `spatie/laravel-permission` (teams mode).
  - Migrations: `tenants`, `users` (+`tenant_id`, nullable for super admin), spatie tables (+`team_id`/`tenant_id`), `audit_logs`.
  - `BelongsToTenant` trait, `TenantScope`, `IdentifyTenant` middleware, `TenantManager`, `withoutTenant()` helper.
  - `Auditable` trait + audit subscriber. Super Admin `TenantProvisioningService` (create tenant, seed default roles/permissions, create Tenant Admin).
- **Frontend:**
  - Base Inertia layout (React), auth pages (login/2FA), Super Admin tenant CRUD pages, Tenant Admin dashboard shell, user/role management UI.
  - Shared Inertia props: current tenant, user, permission list. Reusable `<Can>` component + `useCan()` hook backed by a React context provider.
- **DevOps/Testing:**
  - Full `docker-compose` (base/override/prod), `Dockerfile`, Nginx conf, `.env` templates, Makefile.
  - **Tenancy isolation feature tests** (Tenant A cannot read/write Tenant B). Architecture test: all tenant models use the trait. CI pipeline (lint/Pint, PHPStan, Pest, Vitest).

### Phase 2 — Core EMR & Patient Lifecycle

**Objectives:** Patient registration → encounters → clinical documentation, with PHI encryption and blind-index search live.

- **Backend:** Migrations `patients` (encrypted PHI + blind-index cols), `encounters`/`visits`, `appointments`, `clinical_notes`, `vitals`, `diagnoses` (ICD-10 ref), `departments`, `doctor_schedules`. Encrypted casts + blind-index observer. `AppointmentService` (slot conflict checks), `PatientService`. Policies for Doctor/Receptionist record access. PHI-read access logging.
- **Frontend:** Patient search/registration, patient profile (tabbed: demographics, encounters, notes, vitals), appointment calendar/booking, doctor consultation screen. Reusable form/validation components, optimistic Inertia forms.
- **DevOps/Testing:** Policy tests (Receptionist blocked from clinical notes), encryption round-trip + blind-index search tests, factories/seeders generating realistic tenant-scoped data. Audit-log assertions on PHI reads.

### Phase 3 — Pharmacy, Perishable Inventory & Batch Tracking

**Objectives:** Dispensing tied to prescriptions; batch/lot + expiry tracking; FEFO (First-Expiry-First-Out) stock deduction.

- **Backend:** Migrations `medicines`, `medicine_batches` (batch_no, expiry_date, qty_on_hand, cost), `stock_movements` (immutable ledger), `prescriptions`, `dispense_records`, `suppliers`, `purchase_orders`. `InventoryService` with **atomic, locked** stock deduction (see §4), FEFO allocation, low-stock + near-expiry events (queued alerts). Scheduler job for expiry sweeps.
- **Frontend:** Pharmacy POS/dispense screen, batch intake (GRN), stock dashboard with expiry heatmap, prescription queue for pharmacists. Reusable quantity/batch-picker components.
- **DevOps/Testing:** Concurrency tests on stock deduction (simulate parallel dispenses → no oversell). FEFO ordering tests. Near-expiry notification tests.

### Phase 4 — Real-Time Resource Management (Beds/ORs) via Reverb + Redis

**Objectives:** Live bed/OR availability boards updating across clients in real time; race-free allocation.

- **Backend:** Migrations `wards`, `rooms`, `beds` (status enum), `bed_allocations`, `operating_rooms`, `or_schedules`. Reverb install + config. **Private/presence channels scoped by `tenant_id`** (channel authorization checks tenant + permission). Broadcast events: `BedStatusChanged`, `OrScheduleUpdated`. `BedAllocationService` using DB pessimistic locks / atomic status transition (see §4). Redis as Reverb backplane.
- **Frontend:** Live bed-board (grid of wards/beds, color-coded status), OR scheduling Gantt, admit/discharge/transfer flows. Laravel Echo + React hooks (`useBedBoard`) subscribing to tenant channels; optimistic UI reconciled by broadcast.
- **DevOps/Testing:** `reverb` container + Nginx WS proxy wired and load-checked. Channel-authorization tests (Tenant A cannot subscribe to B's channel). Concurrent-allocation tests (two staff grabbing the same bed → exactly one wins).

### Phase 5 — Financials, Billing & Advanced Analytics

**Objectives:** Charge capture across modules → invoices → payments; insurance/claims; tenant analytics dashboards.

- **Backend:** Migrations `charge_items`, `invoices`, `invoice_lines`, `payments`, `insurance_policies`, `claims`, `tax_configs`. `BillingService` (charge aggregation from encounters/pharmacy/beds), `InvoiceService` (queued PDF generation), payment recording, ledger. Materialized/pre-aggregated reporting tables refreshed by scheduler for dashboard performance. Optional payment-gateway adapter.
- **Frontend:** Billing desk, invoice viewer/PDF, payment capture, claims workflow, analytics dashboards (revenue, occupancy, dispensing trends) with chart components. Date-range/department filters (all tenant-scoped).
- **DevOps/Testing:** Billing-accuracy tests (charges reconcile to invoice totals), PDF generation job tests, dashboard query-performance benchmarks against seeded large datasets. Final hardening: security review, backup/restore drill, prod compose deploy rehearsal.

---

## 4. CRITICAL ARCHITECTURAL CONCERNS & MITIGATION

### 4.1 Race conditions — bed allocation & stock deduction

The shared failure mode: two requests read "available", both write "taken" → double-allocation / negative stock. Mitigations:

- **Pessimistic row locking inside a transaction (primary technique):**
  ```php
  DB::transaction(function () use ($bedId) {
      $bed = Bed::where('id', $bedId)->lockForUpdate()->first(); // SELECT ... FOR UPDATE
      abort_if($bed->status !== 'available', 409, 'Bed already taken');
      $bed->update(['status' => 'occupied']);
      // create allocation row
  });
  ```
  `lockForUpdate()` serializes concurrent transactions on that row; the loser sees the updated status and gets a clean 409.
- **Conditional atomic update as a guard** (cheap optimistic path): `UPDATE beds SET status='occupied' WHERE id=? AND status='available'` — proceed only if **1 row affected**. For stock: `UPDATE medicine_batches SET qty_on_hand = qty_on_hand - ? WHERE id=? AND qty_on_hand >= ?` (DB enforces non-negative; check affected rows).
- **CHECK constraint** `qty_on_hand >= 0` as a last-line DB guarantee.
- **Atomic Redis locks** (`Cache::lock("bed:$id")->block(3)`) to serialize at the app layer before hitting the DB on hot resources.
- **Queue/job tenant safety:** jobs serialize `tenant_id` and re-bind tenant context in `handle()` (and re-establish locks within the job's own transaction) so async stock/bed mutations stay isolated and race-free.

### 4.2 Optimizing heavy tenant-scoped MySQL queries

- **Composite indexes lead with `tenant_id`:** because every query is `WHERE tenant_id = ? AND ...`, indexes must be `(tenant_id, <next filter/sort col>)`. E.g. appointments: `(tenant_id, doctor_id, scheduled_at)`; stock ledger: `(tenant_id, medicine_id, created_at)`. A bare index on `tenant_id` alone is near-useless at scale.
- **Covering indexes** for hot list/dashboard queries so MySQL serves from the index without row lookups.
- **Blind-index columns indexed** for PHI lookups (`(tenant_id, national_id_index)`).
- **Avoid N+1 / unbounded reads:** eager-load (`with()`), always paginate (cursor pagination for large lists), `select()` only needed columns.
- **Pre-aggregation for analytics:** scheduler-refreshed summary tables (daily revenue, occupancy) instead of live `GROUP BY` over millions of rows; cache dashboard payloads in Redis with tenant-scoped keys.
- **Verify, don't guess:** `EXPLAIN` hot queries against seeded large datasets in Phase 5; add `slow_query_log`. Set `tenant_id` columns `NOT NULL` + FK so the optimizer trusts cardinality.

---

## Verification Strategy

1. **Boot & infra:** `docker compose up -d` brings up all containers healthy; app reachable at `http://tenant1.medcore.local`; Super Admin at `admin.medcore.local`.
2. **Tenancy isolation (gating test, Phase 1):** automated Pest tests proving Tenant A queries/policies cannot see Tenant B data; architecture test that every tenant model uses `BelongsToTenant`.
3. **Security (Phase 2):** encryption round-trip tests, blind-index search tests, audit-log assertions on PHI reads, Policy tests for role boundaries.
4. **Concurrency (Phases 3–4):** parallel-request tests for stock deduction and bed allocation prove no oversell / no double-allocation.
5. **Real-time (Phase 4):** Reverb WS connects through Nginx; channel-auth tests prevent cross-tenant subscription; manual multi-client bed-board demo.
6. **Financial accuracy & performance (Phase 5):** charge→invoice reconciliation tests; `EXPLAIN` + benchmark dashboard queries on large seeded data; backup/restore drill on prod compose.
7. **CI on every phase:** Pint, PHPStan (level ≥ 6), Pest, Vitest, `npm run build` must pass.
