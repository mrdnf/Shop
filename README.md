# Matjar — SaaS online shop (PHP + MySQL)

A multi-language (Arabic / English) online shop that can run as **one shop** or as a
**multi-shop SaaS** where every shop has **its own MySQL database**. It connects to any
external system for **live stock balances**, ships orders with **real-time delivery
tracking** (driver app + live map), and every company gets its **own branding & theme**.

Built with plain PHP 8.1+ (no framework, no Composer), MySQL/MariaDB,
Tailwind CSS 4 + **daisyUI 5**, Alpine.js, Lucide icons, Chart.js and Leaflet maps.
Compiled assets are committed, so the server needs no Node.js.

---

## Installation

**Production server (Ubuntu/Debian + nginx):** see [`deploy/DEPLOY.md`](deploy/DEPLOY.md). One command
(`deploy/deploy.sh`) installs everything, including SSL and the cron job.
Unattended install without the web wizard: `php bin/install.php --help` (see the file header).

**Manual / any host:**

1. Point your web server's document root to `public/` (Apache `.htaccess` included,
   nginx sample in `docker/nginx.conf.example`). Make `config/`, `storage/` and
   `public/uploads/` writable.
2. Open `http://your-domain/install`. The wizard:
   1. checks server requirements,
   2. asks for MySQL credentials (an account that can `CREATE DATABASE`),
   3. **asks how the system will be used:**
      - **One shop** → roles: *Admin + Users* (plus staff & drivers)
      - **Multi shop (SaaS)** → roles: *Platform admin + Shop admins + Users*
   4. creates the company / owner account, brand colour, language, optional demo data.
3. Add the scheduler (inventory sync + courier tracking):
   ```
   * * * * * php /path/to/matjar/bin/cron.php >> /path/to/matjar/storage/logs/cron.log 2>&1
   ```

Quick try with Docker: `docker compose up -d` → `http://localhost:8080/install`
(DB host `db`, user `root`, password `matjar`).

Local dev: `php -S 127.0.0.1:8000 -t public public/index.php`

## Architecture

```
master DB (default name: master)     tenants, plans, platform admins, platform branding
one DB per shop (named after it)     users, products, orders, shipments, settings, API keys…
```

- **Single mode**: one tenant row (`main`) with its own DB serves every URL.
- **Multi mode**: a shop is resolved by **custom domain** → **`{slug}.{base_domain}`** →
  **`/s/{slug}`** path; otherwise the request goes to the SaaS website / platform admin.
- Optional **dedicated MySQL user per shop** (granted only on its own database).
- Schema upgrades: add `database/tenant/00X_*.sql`, then `php bin/migrate.php` migrates
  the platform DB and *every* shop DB.

| Area | URL | Who |
|---|---|---|
| Storefront | `/` | everyone |
| Users (customer) area | `/account` | customers |
| Shop admin | `/admin` | admin, staff (settings/integrations: admin only) |
| Driver app | `/driver` | drivers (mobile-first, live GPS) |
| Public tracking | `/track/{number}` | everyone |
| SaaS website & sign-up | `/`, `/start` | multi mode |
| Platform admin | `/platform` | platform owner (multi mode) |

## Inventory integration (any system)

Admin → **Inventory sync**. Every product has a SKU that must match the item code in
your system. Products can follow the integration or be managed manually.

| Connector | How it works |
|---|---|
| **REST / JSON API** | Pull from any endpoint (Odoo, ERPNext, Zoho, SAP B1 Service Layer, custom…). Configurable auth (Bearer / Basic / header), dot-path field mapping (`result.items`, `qty.available`), paging with `{page}`, optional single-SKU URL for real-time checks. |
| **CSV feed** | Published CSV (ERP export, Google Sheets). |
| **External database** | Read-only `SELECT` against MySQL / PostgreSQL / SQL Server. |
| **Push API** | Your system `POST`s balances to `/api/v1/inventory`. |

Modes: **scheduled sync** (cron, per-shop interval), **real-time check** on product page /
cart / checkout (with cache TTL) and atomic stock reservation on order.
**Outbound order webhook**: every new / cancelled order is POSTed to your system, signed
with HMAC-SHA256 (`X-Matjar-Signature`), so the ERP can deduct stock.

New connector? Implement `App\Services\Inventory\InventoryProvider` and register it in
`InventoryService::PROVIDERS` — its config form is generated automatically.

## Delivery & tracking

- **Shipping zones** (city lists in both languages, rate, free-shipping threshold, ETA).
- **Couriers**: *In-house fleet* (driver app), *third-party manual* (tracking link
  template, e.g. Aramex/DHL/SMSA), *Courier API* (create shipment from a JSON template +
  poll tracking with field/status mapping). Couriers can also push updates to
  `/api/v1/shipments/{tracking}/events`.
- Status flow: pending → ready → picked up → in transit → out for delivery → delivered
  (+ failed / returned / cancelled), synced to the order (COD marked paid on delivery).
- Customers see a stepper, timeline and **live map** (driver GPS pings every 15 s,
  page refreshes automatically). Admin sees a **live fleet map**.

## REST API (per shop)

Create keys in Admin → API & webhooks. Send `Authorization: Bearer mk_…`.

```
GET  /api/v1/products?updated_since=2025-01-01
POST /api/v1/inventory                 {"items":[{"sku":"EL-1001","quantity":12}]}
GET  /api/v1/orders?status=pending&since=2025-01-01
GET  /api/v1/orders/{number}
POST /api/v1/shipments/{tracking}/events {"status":"in_transit","note":"Hub","lat":24.7,"lng":46.6}
```
(In path mode prefix with `/s/{slug}`.)

## Theming (per company)

Admin → **Branding & theme** (platform owner has the same screen for the SaaS site):
logo (+ dark-mode logo), favicon, company name EN/AR, tagline, primary / secondary /
accent colours with presets, English & Arabic Google fonts, default language (RTL
automatic), default light / dark / system mode, **top menu or sidebar menu** (separately
for storefront and admin), corner style, homepage hero — with a live preview.
Visitors can switch language and light/dark at any time.

## Security notes

CSRF on all forms, prepared statements everywhere, bcrypt passwords, login throttling,
scoped sessions per shop, encrypted secrets (libsodium, `app_key`), upload type
validation (no scripts in `uploads/`), SSRF guard for integrations in SaaS mode,
read-only SQL enforcement for the DB connector, plan limits (products / team).

## Front-end build (only if you change views/CSS)

```
npm install
npm run build   # copies vendor JS + compiles public/assets/css/app.css
```
