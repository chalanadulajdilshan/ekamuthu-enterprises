# Ekamuthu POS (Laravel 12, MySQL/MariaDB)

Hardware-store point of sale and back office: POS billing, products, categories, multi-branch
inventory with a stock ledger, purchases (GRN), customers with credit accounts, suppliers, reports,
and settings/users. It replaces the old Node/React POS that lived in this folder.

**Data model:** the POS keeps its own tables, all prefixed `pos_`, inside the same database as the
main Ekamuthu system. It never writes to the main system's tables. Branches, items, customers,
suppliers and store details are copied in once with `php artisan pos:import-ekamuthu`.

Requires PHP 8.2+ with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`.

## Install (local XAMPP or live cPanel)

```bash
cd pos-system
composer install --no-dev --optimize-autoloader
cp .env.example .env              # then set DB_DATABASE / DB_USERNAME / DB_PASSWORD / APP_URL
php artisan key:generate
php artisan migrate --force       # creates the pos_* tables only
php artisan db:seed --force       # first admin login (printed), branch, category tabs
php artisan pos:import-ekamuthu   # branches, items (+images, opening stock), customers, suppliers
php artisan optimize              # production: caches config, routes, views
```

Open `https://<site>/pos-system/` (local: `http://localhost/ekamuthu-enterprises/pos-system/`).
The main dashboard's POS tile already links here. `pos-system/.htaccess` sends every request into
`public/`, so `.env`, code and `vendor/` are never served.

If the host has no Composer, run `composer install --no-dev` locally and upload `vendor/` too.

### Importer options
```bash
php artisan pos:import-ekamuthu --only=customers,suppliers   # just some parts
php artisan pos:import-ekamuthu --stock-source=stock_master  # opening stock from the main system
php artisan pos:import-ekamuthu --stock-source=none          # no opening stock
php artisan pos:import-ekamuthu --category=Building          # tab for imported items (default: Others)
```
It's safe to re-run. Rows are matched on `legacy_id`, and opening stock is only written for
products created in that run, so re-running never resets stock the POS has already sold from.
The main system's categories are tyre types, so they are not imported. Re-categorise imported
items in **Products**.

### ⚠ Shared database: never wipe
`migrate:fresh` and `db:wipe` would drop **every** table in the database, including the main
Ekamuthu system's, so both are blocked in code (`AppServiceProvider`). To remove or rebuild only
the POS tables:
```bash
php artisan migrate:reset --force    # drops pos_* tables only (runs each migration's down())
php artisan migrate --force && php artisan db:seed --force && php artisan pos:import-ekamuthu
```

### Demo data (testing only, never on live)
```bash
php artisan db:seed --class=DemoSeeder
php artisan pos:bulk-seed --products=100000 --customers=5000 --sales=150000   # load test
```

## POS keyboard shortcuts

| Key | Action |
|---|---|
| F2 | Focus search / scanner |
| Enter (in search) | Exact barcode/code lookup, then add to cart |
| F4 | Clear sale |
| F6 | Discount |
| F7 | Hold the bill (on an empty cart it opens the held bills to recall one) |
| F8 | Pay |
| F12 | Print the last bill |

USB barcode scanners work out of the box. They type the code and press Enter, and stray keystrokes go
to the search box automatically.

## Performance design ("fast no matter how much data")

**Database**
- Every list or search query is backed by an index: grid `(is_active, category_id, name)`, barcode/code
  `UNIQUE`, `FULLTEXT(name, code, barcode)` for word search, `product_stocks PK(branch_id, product_id)`,
  sales reports `(branch_id, status, sale_date)`, stock ledger `(product_id, branch_id, id)`.
- Search order: an exact barcode hit uses a unique-index lookup. Words of 3+ characters use a FULLTEXT
  prefix search. Short tokens like `5m` are a LIKE filter on top of that.
- Checkout runs in one transaction. It locks stock rows in id order with `SELECT … FOR UPDATE` (so
  there's no overselling and no deadlocks), then does one `UPDATE … CASE` for all lines and bulk-inserts
  the items and ledger rows.
- Bill numbers come from `LAST_INSERT_ID(expr)` on a sequence row. They're race-safe and gap-free, and
  cost one round trip.
- Reports aggregate in SQL over index range scans. Nothing loads full tables into PHP.
- Settings, categories, and branches are cached and flushed on change, so they cost zero queries per request.

**App**
- Grid and product list never join to count. `COUNT(*)` runs index-only on `products`, and pages
  use a deferred join: page through ids on the covering index, then join category and stock
  for just the rows shown. Deep pages (`OFFSET 20000`) skip index entries, not joined rows.
- The browsing count (no search) is cached per category and invalidated when products are
  added, deleted or (de)activated.
- The POS grid loads one page at a time (default 10) as JSON from the query builder, with no Eloquent
  hydration.
- Search is debounced. In-flight requests are aborted, and recent pages are cached in the browser for 30 s.
- The cart is kept in `localStorage`, so a refresh or power cut doesn't lose the sale. The server
  re-prices everything at checkout.
- `Model::preventLazyLoading()` is on in development to catch N+1 queries.

**Load test**
```bash
php artisan pos:bulk-seed --products=100000 --customers=5000 --sales=150000
```

### Production checklist
```bash
# .env: APP_ENV=production, APP_DEBUG=false
composer install --no-dev --optimize-autoloader
php artisan optimize          # caches config, routes, events, and views
```
Enable OPcache in `php.ini` (`opcache.enable=1`).

Recommended `my.ini` changes under `[mysqld]`. XAMPP's defaults are tiny. Restart MySQL afterwards:
```ini
innodb_buffer_pool_size=512M      ; XAMPP default 16M – the single biggest win
innodb_log_file_size=128M
innodb_flush_log_at_trx_commit=2  ; much faster commits; at most ~1s of data at risk on OS crash
max_allowed_packet=64M
```

## Structure
- `app/Services/SaleService.php`: checkout and void (all money math lives server-side)
- `app/Services/StockService.php`: the only place stock changes, and it writes `stock_movements`
- `app/Services/PurchaseService.php`: GRN, weighted-average cost, supplier balance
- `app/Services/ProductSearch.php`: index-friendly search
- `app/Services/DocNumber.php`: bill and GRN numbering
- `public/js/pos.js`: POS screen; `public/css/app.css`: UI
- `app/Console/Commands/ImportEkamuthu.php`: one-way import from the main Ekamuthu tables
- Logo: drop a `public/images/logo.png` and it replaces the text logo in the sidebar.
