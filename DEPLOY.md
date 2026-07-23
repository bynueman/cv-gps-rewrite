# Deploy — gpsfood.id (cPanel shared hosting)

Reference doc for deploying and updating this Laravel + Inertia + React app on
the CV Gama Putra Santosa production host. Written from how this app is
*actually* deployed there today, not a generic Laravel guide.

## Server facts

| | |
|---|---|
| Hosting | cPanel shared hosting (no root/SSH package manager, no persistent Node process) |
| PHP | 8.2.26, via cPanel's `php` alias |
| App path | `~/gsp-laravel` (cloned from `https://github.com/bynueman/cv-gps-rewrite.git`) |
| Web root | `~/public_html` is a **symlink** to `~/gsp-laravel/public` (this domain is the account's primary domain, so cPanel's Domains UI had no per-domain document-root field — see the `ln -s` step below if this ever needs recreating) |
| Database | MySQL, created via `uapi Mysql create_database` / `create_user` / `set_privileges_on_database` from the cPanel Terminal |
| Node | **Not installed on the server.** Frontend assets are built locally and uploaded — see below |

## Prerequisites

- PHP ≥ 8.2 with extensions: `pdo_mysql`, `mbstring`, `gd`, `dom`, `bcmath`,
  `fileinfo`, `zip`, `curl`, `openssl`, `tokenizer`, `ctype` (all confirmed
  present on the current host). `ext-intl` is *not* required — Symfony's
  polyfills cover IDN/grapheme handling in its absence, so don't block a
  deploy on it.
- Composer (already available via cPanel).
- A local machine with Node/npm to run `npm run build` — never attempt this
  on the server.

## First-time setup

1. **Clone outside the web root**, then symlink the public folder:
   ```bash
   cd ~
   git clone https://github.com/bynueman/cv-gps-rewrite.git gsp-laravel
   cd gsp-laravel
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```
   If `public_html` isn't already the app's `public/` folder (fresh account,
   or this ever needs to be redone):
   ```bash
   cd ~
   cp -r ~/public_html/.well-known ~/gsp-laravel/public/.well-known 2>/dev/null # keep SSL validation files
   mv ~/public_html ~/public_html_backup_$(date +%Y%m%d)
   ln -s ~/gsp-laravel/public ~/public_html
   ```

2. **Database** (via cPanel Terminal `uapi`, or the cPanel UI's MySQL Databases page):
   ```bash
   uapi Mysql create_database name="gsp"
   uapi Mysql create_user name="gsp" password="<strong-password>"
   uapi Mysql set_privileges_on_database user="gpsfoodi_gsp" database="gpsfoodi_gsp" privileges="ALL PRIVILEGES"
   ```
   cPanel prefixes both the database and username with the account username
   (e.g. `gpsfoodi_gsp`) — run `uapi Mysql list_databases` / `list_users` to
   get the exact final names and fill in `.env`'s `DB_*` values.

3. **`.env` — required values** (see `.env.example` for the full list):
   ```
   APP_NAME="CV Gama Putra Santosa"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://gpsfood.id
   DB_CONNECTION=mysql
   DB_DATABASE=<from step 2>
   DB_USERNAME=<from step 2>
   DB_PASSWORD=<from step 2>
   MAIL_MAILER=sendmail   # or smtp with the mailbox's own credentials — "log" (the default) silently no-ops
   ```
   `APP_NAME` matters beyond cosmetics here: it's `og:site_name`, the page
   title suffix, and the Organization JSON-LD name. Confirm it's *not* still
   `Laravel` after copying `.env.example` — that's a real regression this
   project shipped once already.

4. **Migrate, seed, link storage, create the first admin:**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force          # brands, products, and Pengaturan defaults
   php artisan storage:link
   php artisan admin:create admin@gpsfood.id 'a-real-password-not-a-placeholder'
   ```

5. **Build assets locally, then upload:**
   ```bash
   npm run build   # on your machine — never on the server
   ```
   Upload the resulting `public/build/` directory into `~/gsp-laravel/public/build/`
   (replace it wholesale) via File Manager or `scp`/`rsync` if SSH file
   transfer is available. There is no SSR bundle to upload — see "Why no
   Inertia SSR" below.

6. **Cache for production:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

7. **Cron** — add via cPanel's Cron Jobs page (not `crontab -e`, which isn't
   available on shared hosting):
   ```
   * * * * * cd /home/<cpanel-user>/gsp-laravel && php artisan schedule:run >> /dev/null 2>&1
   ```
   This drives `App\Console\Commands\AggregatePageViews`, scheduled daily at
   01:00 in `routes/console.php` — it folds raw `page_views` rows into
   `page_view_daily` (what the dashboard actually reads) and prunes raw rows
   older than 30 days. Without this cron entry the dashboard charts will
   just stay empty and `page_views` will grow unbounded.

## Routine deploys (code already on the server once)

```bash
# 1. On your machine
npm run build
# upload public/build/ (overwrite) to the server

# 2. On the server, in ~/gsp-laravel
git pull origin main
composer install --no-dev --optimize-autoloader   # skip if composer.json/lock unchanged
php artisan down                                   # optional, for larger changes
php artisan migrate --force                        # skip if no new migrations
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

`.env` is not tracked by git and is never touched by `git pull` — production
config survives deploys untouched. If a deploy adds a new key to
`config/company.php`/`.env.example`, add it to the live `.env` manually.

## Backups

Not automated by this app — set these up as cron jobs on the host once:

```bash
# Daily DB dump, keep 14 days
mysqldump -u <db_user> -p'<db_pass>' <db_name> | gzip > ~/backups/db-$(date +\%Y\%m\%d).sql.gz
find ~/backups -name 'db-*.sql.gz' -mtime +14 -delete

# Daily sync of admin-uploaded images (products/articles/partners/settings —
# NOT public/build, which is redeployed from your machine every release)
rsync -a ~/gsp-laravel/public/uploads/ ~/backups/uploads/
```

## Security notes specific to this app

- **Login rate limiting** is handled in `app/Http/Requests/Auth/LoginRequest.php`
  (5 attempts / 15 minutes / IP, via Laravel's `RateLimiter`) — not a route
  `throttle` middleware. If you ever add a second login entry point, route
  through `LoginRequest::ensureIsNotRateLimited()` rather than bypassing it.
- **Admin is `noindex`**: every `/admin/*` response (including `/admin/login`
  and the JSON upload endpoint) carries `X-Robots-Tag: noindex, nofollow`
  (`App\Http\Middleware\NoIndexAdmin`), and `robots.txt` also disallows
  `/admin` for crawlers that respect it.
- **Role gate is server-side**, not just a hidden sidebar item — `/admin/users`
  and `/admin/settings` are behind the `superadmin` route middleware
  (`App\Http\Middleware\EnsureUserIsSuperadmin`), so a direct URL hit from an
  editor account gets a real 403, not just a missing nav link.
- **Uploads are re-validated server-side**: `ImageUploadController` decodes
  every file with Intervention Image and checks the *decoded* media type
  before processing — a renamed `.php`/`.txt` can't ride through on a spoofed
  `Content-Type` or `.jpg` extension.
- **Secrets live only in `.env`** on the server; `.env.example` (tracked in
  git) has no real values. Never commit a filled-in `.env`.

## Why no Inertia SSR

Full Inertia SSR needs a persistent Node process (`php artisan inertia:start-ssr`
+ a supervisor to keep it alive) — this shared-hosting plan has no Node
runtime and no way to run a long-lived background process, so it's
disproportionate here. Instead, `resources/views/app.blade.php` renders a
`<noscript>` fallback (page H1 + description) inside the Inertia mount point,
so a non-JS crawler still gets each page's core topic. All per-page meta
(title/description/canonical/OG/Twitter/JSON-LD) is already rendered
server-side regardless — only the interactive body content requires JS.
