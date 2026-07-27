# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Laravel 12 + Inertia (React 18 + TypeScript) marketing/CMS site for **CV Gama Putra Santosa (GPS Group)**, a Yogyakarta food & beverage company with two product brands: **Kuicip** (cassava chips) and **Putri Teko** (traditional herbal drinks). Public site (bilingual ID/EN, ID-primary) + a small admin CMS for managing products, articles, partners, contact messages, users, and settings.

Deployed to cPanel shared hosting with no Node runtime on the server — see `DEPLOY.md` for the full deploy process, security notes, and why there's no Inertia SSR. Read it before touching deploy-adjacent code (asset builds, `.env`, cron).

## Commands

```bash
# Local dev — runs PHP server + queue listener + log tailer + Vite concurrently
composer dev

# Frontend only
npm run dev
npm run build

# Tests (PHPUnit, sqlite :memory:, see phpunit.xml)
composer test
php artisan test                                    # same as composer test, skips config:clear
php artisan test --filter=PartnerCrudTest            # single test class
php artisan test tests/Feature/Admin/ProductCrudTest.php

# Lint/format (Laravel Pint)
vendor/bin/pint
vendor/bin/pint --test        # check only, no changes

# One-time setup (fresh clone)
composer setup                # install, .env, key:generate, migrate, npm install+build

# Admin user creation
php artisan admin:create <email> <password>
```

There is no JS test runner or JS linter configured — TypeScript checking happens implicitly via `tsc`/Vite at build time, not as a separate script.

## Architecture

### Two brands, one Product model

`Product` (app/Models/Product.php) belongs to `Brand` (`kuicip` or `putri-teko` by `key`). Kuicip has no packaging dimension; Putri Teko's `packaging` column is one of the `App\Enums\TekoPackaging` cases (`botol`, `kotak`, `toples`, `kemasan`, `besek` — deliberately a string column, not a MySQL ENUM, for schema flexibility). `Product::scopeRelatedTo()` prefers same-packaging siblings for Teko and is a plain same-brand match for Kuicip. Public product routes are brand-scoped and duplicated per brand (`/products/kuicip/{slug}`, `/products/putri-teko/{slug}`) rather than a single generic `/products/{brand}/{slug}` — see `routes/web.php` and the matching controller methods in `app/Http/Controllers/ProductController.php`.

### Bilingual content: two different strategies

- **UI copy** (nav labels, section headings, static page copy) lives in `resources/js/lib/i18n.tsx` as a single TypeScript dictionary object (`id` canonical, `en` type-checked against it — a missing translation is a compile error). `useLang()` / `<LanguageProvider>` provide `{ lang, setLang, t }`; language is client-side state, not a route or session value.
- **Data-driven content** (products, articles, brands) stores parallel `_id`/`_en` columns (e.g. `name_id`/`name_en`, `description_id`/`description_en`) and is switched by the same `lang` from `i18n.tsx` in the consuming component, not fetched separately per language.

### SEO / head rendering without Inertia SSR

Every public controller action builds an `App\Support\Seo` value object and passes it as the `seo` Inertia prop. `resources/views/app.blade.php` reads `$page['props']['seo']` **server-side** on first load to render `<title>`, meta description/keywords, canonical, OG/Twitter tags, and JSON-LD `<script>` blocks — this is what lets crawlers and social-preview bots see correct metadata without executing JS, since there's no SSR bundle. When adding a new public page, always construct a `Seo(...)` and pass `seo: $seo->toArray()`; don't rely on client-side `<Head>` for anything crawler-visible. `Seo`'s `ogImage` falls back to `Setting::get('og_image_default', ...)` when a page doesn't supply one.

### Settings: DB override over config default

`Setting::get($key, $default)` (app/Models/Setting.php) reads from the `settings` table with a `Cache::rememberForever` wrapper, invalidated on save/delete via model events. `config/company.php` holds the static, non-admin-editable defaults (name, address, WhatsApp, hours, map embed). `HandleInertiaRequests::resolveCompany()` layers `Setting::get()` over `config('company.*')` and shares the merged result as the `site.company` Inertia prop on every request — so a fresh install works with zero admin configuration, and an admin can override specific fields (address, emails, WhatsApp, hours) via `/admin/settings` without a deploy.

### Image pipeline

All admin image uploads (products, articles, partners, settings OG image) go through the single JSON endpoint `POST /admin/upload` (`ImageUploadController`), which delegates to `App\Services\ImageProcessor`. Every accepted upload is **re-decoded** with Intervention Image (not trusted by client-declared MIME type) and re-encoded to WebP, which also strips EXIF. Two pipelines:
- `processStandard()` — three variants (content ≤1280w, thumb ≤640w, OG 1200×630 cover) for products/articles/settings.
- `processLogo()` — single variant ≤480×480, no forced crop, for partner logos (keeps transparency, avoids distorting non-square marks).

`ImageProcessor::delete()` removes all sibling variants sharing a UUID prefix and refuses to delete anything outside `public/uploads/`. When adding a new image field, reuse `ImageProcessor` rather than writing a new upload path — the `context` param (`ImageProcessor::CONTEXTS`) only picks the destination folder.

### Admin auth & access control

- Admin routes live under `Route::middleware(['auth', 'noindex'])->prefix('admin')` in `routes/web.php`. Guest/authenticated redirect targets are wired in `bootstrap/app.php` (`redirectGuestsTo` → `admin.login`, `redirectUsersTo` → `admin.dashboard`) since this app only has an admin login, not the Breeze-default `login` route.
- `superadmin` route middleware (`EnsureUserIsSuperadmin`) gates `/admin/users` and `/admin/settings` — a real 403 for non-superadmin roles, not just a hidden nav item.
- `noindex` middleware (`NoIndexAdmin`) sets `X-Robots-Tag: noindex, nofollow` on every admin response, including `admin.login` and the JSON upload endpoint.
- Login rate limiting is in `app/Http/Requests/Auth/LoginRequest.php` (`ensureIsNotRateLimited()`, 5 attempts/3min/IP) — route any new login entry point through it rather than adding a `throttle` middleware.

### Page view analytics (cookie-free, no external service)

`TrackPageView` middleware (registered globally in `bootstrap/app.php`) logs successful GET requests to public (non-admin, non-sitemap/robots/health) routes into `page_views`, filtering bots via `App\Support\UserAgentParser`. It never stores raw IP — `visitor_hash` is `sha256(ip+user-agent+date)`, one-way and only good for same-day dedup. The scheduled command `App\Console\Commands\AggregatePageViews` (`routes/console.php`, daily at 01:00, **requires the server cron entry documented in DEPLOY.md**) folds raw rows into `page_view_daily` — the admin dashboard charts read only the aggregated table, and raw `page_views` rows older than 30 days are pruned. Without the cron entry, dashboard charts silently stay empty.

### Article HTML sanitization

Admin-authored article HTML (from the TipTap-based `resources/js/Components/admin/RichTextEditor.tsx`) is cleaned server-side by `App\Support\ArticleHtmlSanitizer::clean()` before storage: `Purify::config('article')` (allowlist in `config/purify.php`) plus a DOMDocument post-pass that forces `rel="noopener noreferrer" target="_blank"` on every `<a>` (HTMLPurifier's URI directives don't cover that). The sanitized HTML is later rendered via `dangerouslySetInnerHTML` on public article pages, so don't bypass this sanitizer for any new HTML-accepting field.

### Frontend structure

- `resources/js/Pages/` — Inertia page components, one per route (mirrors `routes/web.php` naming); `Pages/Admin/*` for the CMS.
- `resources/js/Layouts/` — `SiteLayout` (public), `AdminLayout` (CMS), `GuestLayout` (admin login).
- `resources/js/Components/sections/` — homepage sections; `Components/admin/` — CMS-only widgets (ImageUploadField, RichTextEditor, ConfirmDialog, etc.); `Components/graphics/` — inline SVG packshot art.
- Path alias `@/*` → `resources/js/*` (see `tsconfig.json`).
- `site.company`, `site.brandAssets`, `site.brands` are shared Inertia props on every request via `HandleInertiaRequests` — available in any page/component without an explicit prop.

### Config-driven static data vs DB-driven admin data

`config/company.php` and `config/brand_assets.php` hold content that is a literal port of the original app's hardcoded `content.ts` and is **not** admin-editable (company identity, fixed logo/group-shot paths for the 2 brands + corporate mark). Contrast with `Brand`, `Product`, `Partner`, `Article`, `Setting` — all DB-backed and CRUD'd through `/admin/*`. When asked to make something "admin-editable," check which category it currently falls into before assuming a DB migration is needed.
