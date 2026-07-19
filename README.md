# Lara Dev

A small Laravel 13 app: a session-gated dashboard with full CRUD for a `Customer` entity, a bulk CSV importer built for millions of rows, and paginated listing. SQLite database, plain Blade views, no JS framework.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Log in at `/login` with `admin@example.com` / `password123` (see [Commit 2 — login](#commit-2--login) for why these are hardcoded).

## Development log

This section walks through the git history commit by commit: what each commit does, its **scope** (what it deliberately does and doesn't cover), its **lifecycle** (how a request/data actually flows through it), what alternative approaches existed, and which one was picked and why.

Commits 1–2 predate this working session and are documented here for completeness, based on reading the code rather than on original author intent. Commits 3–5 were designed and implemented in this session.

| # | Commit | Summary |
|---|--------|---------|
| 1 | `e8ab040` add initial commit | Stock `laravel new` skeleton |
| 2 | `2c08491` login | Hardcoded-credential session login |
| 3 | `29cff44` customer | Customer CRUD (model, migration, controller, middleware, views) |
| 4 | `c85b875` add import command | Streaming/bulk CSV importer |
| 5 | `31efa64` add pagination | 10-per-page listing with Previous/Next |

---

### Commit 1 — initial commit

**What:** Unmodified `laravel new` output — framework, default config, `.env.example`, the stock `users`/`cache`/`jobs` migrations.

**Scope:** Scaffolding only. No app-specific code.

**Lifecycle:** N/A — this is the starting point every later request/data flow builds on.

---

### Commit 2 — login

**What:** `AuthController` with `showLoginForm` / `login` / `dashboard` / `logout`; login checks the submitted email/password against a hardcoded pair (`admin@example.com` / `password123`) and, on success, writes a plain array into `session('user')`. `dashboard()` originally re-checked that key inline. Two Blade views (`auth/login`, `auth/dashboard`) with hand-written inline CSS. One feature test (`LoginTest`).

**Scope:** Gates a single route (`/dashboard`) behind "is there a `user` key in the session." No `users` table involved, no password hashing, no registration/password-reset flow.

**Lifecycle:** `GET /login` renders the form → `POST /login` validates shape (`required|email`, `required|string`) → compares against the hardcoded pair → on match, stores `{name, email}` in the session and redirects to `intended()` (falls back to `/dashboard`) → `GET /dashboard` reads `session('user')` to render → `GET /logout` clears that session key.

**Best approaches available:**
1. Laravel Breeze / Fortify / Jetstream — full scaffolding: real `users` table, hashed passwords, registration, password resets.
2. Hand-rolled session flag against a hardcoded credential pair.

**What was followed and why:** Option 2 — a bare session flag, no real user record. This is a reasonable choice *for a prototype/demo gate* (zero dependencies, nothing to migrate, one file to read to understand the whole auth story) but it is not production auth: credentials live in source, there's no password hashing, and anyone who can read the code can log in. Fine as a placeholder while building out the features that actually mattered (customer CRUD, import, pagination); worth swapping for Breeze/Fortify before this goes near real users.

---

### Commit 3 — customer

**What:** `customers` migration (`name`, unique `email`, `phone`, `address`), `Customer` model, `CustomerController` (`index`, `create`/`store`, `edit`/`update`, `destroy` — no `show`, since edit already displays everything), a new `EnsureUserIsLoggedIn` middleware aliased as `auth.session`, `Route::resource('customers', ...)` wrapped in that middleware alongside `/dashboard`, and Blade views (`layouts/app` shared chrome + `customers/index|create|edit`). Also removed the now-redundant inline session check from `AuthController::dashboard()` since the middleware covers it.

**Scope:** Full CRUD for one entity, gated behind "logged in." No authorization beyond that (no roles/ownership), no soft deletes, no API endpoints, no search/filter — just create, list, edit, delete.

**Lifecycle:** Standard Laravel resource-controller request flow: `routes/web.php` maps HTTP verbs to controller actions → `auth.session` middleware runs first and redirects to `/login` if `session('user')` is empty → controller talks to `Customer` (Eloquent) → view renders through the shared `layouts/app` template → mutating forms carry `@csrf` and method-spoofing (`@method('PUT')`/`@method('DELETE')`) → each mutation redirects back to the index with a one-shot `session('status')` flash message.

**Best approaches considered:**
1. **Livewire or Inertia + a JS framework** — richer, no full-page reloads on save/delete. Rejected: nothing in the project pulls in Livewire/Vue/React yet (only Tailwind+Vite for CSS); adding one for a single CRUD entity would be new infrastructure disproportionate to the ask.
2. **Filament** (or a similar admin-panel package) — CRUD scaffolding for free. Rejected: heavyweight dependency with its own opinionated UI, awkward to reconcile with the existing hand-styled login/dashboard look, overkill for one entity in a demo-auth app.
3. **Plain resource controller + Blade forms**, matching the existing `AuthController` pattern.

**What was followed and why:** Option 3. It's consistent with what was already in the repo (no new dependencies, same request/response shape as the login flow), and simplest to verify end-to-end with plain `curl` given the session-based auth. The one deliberate abstraction introduced — `EnsureUserIsLoggedIn` middleware — replaced what would otherwise have been the same `if (! session('user'))` check copy-pasted into six new controller methods; that's a DRY fix earned by actual duplication, not speculative design.

---

### Commit 4 — add import command

**What:** `php artisan customers:import {path} {--chunk=2000}` — streams a CSV with `fgetcsv` (constant memory regardless of file size), validates each row (required `name`/valid `email`), batches valid rows and `upsert()`s them keyed on `email` (so re-importing or overlapping data updates existing rows instead of throwing a unique-constraint error), and writes every rejected/failed row to a sibling `_errors.csv` with a reason instead of aborting the run. If a whole batch throws unexpectedly, it retries that batch row-by-row to isolate just the bad row. Temporarily tunes SQLite pragmas (`synchronous=OFF`, `journal_mode=WAL`) for the duration of the run.

**Scope:** One-directional CSV → `customers` table, run manually from the CLI. Not scheduled, not queued, no upload-from-browser UI.

**Lifecycle (data, not HTTP):** CSV file on disk → read one row at a time → validate/normalize → buffer into a chunk → chunk `upsert()`s in one query → on chunk failure, fall back to per-row upserts and log the actual offending row(s) → repeat until EOF → print a summary (imported/updated count, skipped count, path to the errors file if any).

**Best approaches considered:**
1. **`Customer::create()` in a loop** — simplest to write, but instantiates an Eloquent model and fires model events per row; at millions of rows this is materially slower, and a single duplicate email throws and kills the whole run unless every iteration is individually wrapped.
2. **Load the whole file into memory** (`Storage::get()`/`file()` then parse), then chunk and bulk-insert — fails exactly where it matters: the entire file has to fit in memory *before* any row is processed, which is the specific failure mode "millions of rows" is likely to hit first.
3. **Queue a job per row/chunk** — good for not blocking a web request and for automatic retries, but requires queue infrastructure (a worker process, a queue driver) that doesn't exist in this project, and is disproportionate for an admin-run one-off import.
4. **Streaming read + chunked query-builder `upsert()`.**

**What was followed and why:** Option 4, because it directly answers the stated requirement — "millions of data without any error." Memory had to stay flat regardless of file size (verified empirically: ~83 MB peak RSS importing a 272 MB / 3,000,000-row file, confirmed by re-running the actual file-generation + import + a `/usr/bin/time -l` memory measurement), duplicate keys needed to resolve via upsert rather than crash the run, and malformed rows needed to be logged and skipped rather than aborting the batch they're in. Query builder (not Eloquent) specifically avoids model-instantiation and event overhead at this volume.

---

### Commit 5 — add pagination

**What:** `CustomerController::index()` changed from `Customer::latest()->get()` to `Customer::latest()->paginate(10)->withQueryString()`; the index view gained a Previous/Next control pair plus a "Page X of Y" indicator, driven off the paginator's own `onFirstPage()`/`hasMorePages()`/`previousPageUrl()`/`nextPageUrl()` state, with matching styles in the shared layout.

**Scope:** Only the customers index/listing page. Create/edit/delete are untouched. Page size is fixed at 10, not user-configurable.

**Lifecycle:** Same resource-index request flow as commit 3, except the underlying query is now bounded (`LIMIT 10 OFFSET ...` under the hood) instead of fetching every row, and page position round-trips through the `?page=` query string on every click.

**Best approaches considered:**
1. **Laravel's default pagination Blade views** (`{{ $customers->links() }}`) — fastest to wire up, but ships Tailwind-based markup (would need publishing/overriding to match the existing inline-CSS look) and renders numbered page links, which wasn't what was asked for.
2. **Client-side pagination** (fetch everything, slice in JS) — defeats the purpose entirely once the table has thousands of rows; still loads the full result set up front.
3. **Manual Previous/Next buttons off the paginator's own URL/state helpers.**

**What was followed and why:** Option 3. The ask was specifically "load only 10 records" with "next and previous" — not a numbered pager — so this was the smallest change that fit the existing hand-styled views, required no view-publishing step, and lets the database do the actual work via `LIMIT`/`OFFSET` so only 10 rows are ever fetched per request. Verified against the real dataset (1,001 customers → 101 pages): page 1 and page 2 return disjoint rows, Previous is inert on page 1, Next is inert on page 101.

## Known limitations / next steps

- Auth is a hardcoded-credential session flag, not real user accounts — swap for Breeze/Fortify before any real users touch this.
- No authorization layer beyond "logged in" (no per-user ownership of customers).
- CSV import is CLI-only; there's no browser upload flow yet.
- Pagination page size (10) is hardcoded, not a query param.
