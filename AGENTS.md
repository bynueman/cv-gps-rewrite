# AGENTS.md

This file provides architectural context, commands, coding standards, and operational guidelines for AI agents working in this repository.

---

## 1. Project Overview

- **Entity**: CV Gama Putra Santosa (GPS Group) — a food & beverage company based in Yogyakarta, Indonesia.
- **Brands**:
  - **Kuicip**: Modern cassava chips (*keripik singkong*) available in various flavors.
  - **Putri Teko**: Traditional Indonesian herbal drinks (*jamu & minuman herbal*).
- **Application Type**: Monolithic marketing & CMS website with a public bilingual showcase and an authenticated admin management dashboard.
- **Tech Stack**:
  - **Backend**: Laravel 12 (PHP 8.2+)
  - **Frontend**: React 18 with TypeScript via Inertia.js 2.0
  - **Styling**: Tailwind CSS 3.x
  - **Bundler**: Vite 7.x
  - **Database**: MySQL 8.x / MariaDB (SQLite `:memory:` for automated testing)
  - **Media Processing**: Intervention Image v3 (WebP conversion & resizing)
  - **Hosting Environment**: cPanel Shared Hosting (**No Node.js runtime on production**; no Inertia SSR)

---

## 2. Essential Commands

### Local Development
```bash
# Start full local development stack (PHP server + queue + logs + Vite concurrently)
composer dev

# Run frontend Vite dev server only
npm run dev

# Build frontend production bundle (Vite -> public/build)
npm run build

# Run PHP local built-in server only
php artisan serve
```

### Testing & Code Quality
```bash
# Run full PHPUnit test suite (uses in-memory SQLite)
composer test
php artisan test

# Run a specific test class or file
php artisan test --filter=PartnerCrudTest
php artisan test tests/Feature/Admin/ProductCrudTest.php

# Code style formatting (Laravel Pint)
vendor/bin/pint

# Check code formatting without applying changes
vendor/bin/pint --test
```

### Setup & Administration
```bash
# One-time project setup (install, .env, key:generate, migrate, build)
composer setup

# Create or reset an administrator account
php artisan admin:create <email> <password>
```

> **Note on TypeScript**: There is no separate standalone JS linter configured. TypeScript type-checking executes automatically during `npm run build` / `vite build`.

---

## 3. Core Architecture & Conventions

### A. Two Brands, Single Product Model
- Both **Kuicip** and **Putri Teko** share the `Product` model (`app/Models/Product.php`), differentiated by `brand_id` linked to the `Brand` model (`kuicip` vs `putri-teko`).
- **Packaging Attribute**:
  - Kuicip has no packaging dimension.
  - Putri Teko uses the `packaging` column backed by `App\Enums\TekoPackaging` (`botol`, `kotak`, `toples`, `kemasan`, `besek`). This is stored as a string column for schema adaptability.
- **Public Routing**:
  - Product routes are brand-scoped: `/products/kuicip/{slug}` and `/products/putri-teko/{slug}` (handled by distinct controller actions in `app/Http/Controllers/ProductController.php`).

### B. Bilingual Content Strategy
The application supports Indonesian (`id` - primary/canonical) and English (`en`):
1. **Static UI Copy**:
   - Resides in `resources/js/lib/i18n.tsx`.
   - Managed as a typed dictionary where `en` is strictly type-checked against `id`.
   - Consumed on the client side via the `useLang()` hook / `<LanguageProvider>`.
2. **Dynamic Database Content**:
   - Models (Products, Articles, Brands, etc.) store parallel bilingual columns (e.g., `name_id` & `name_en`, `description_id` & `description_en`).
   - The active `lang` state selects the appropriate field directly in React components.

### C. Server-Rendered Head & SEO (Without Inertia SSR)
Because shared hosting lacks a persistent Node.js runtime for Inertia SSR:
- Every public controller builds an `App\Support\Seo` value object and passes it to Inertia via the `seo` prop (`seo: $seo->toArray()`).
- `resources/views/app.blade.php` inspects `$page['props']['seo']` **server-side** during the initial HTTP request to render `<title>`, `<meta>` tags, canonical URLs, OpenGraph/Twitter cards, and JSON-LD structured schemas.
- **Rule**: When creating or modifying public pages, always construct and return the `Seo` object in the controller. Do not rely on client-side React `<Head>` for bot/crawler-critical metadata.

### D. Settings & Company Profile
- `Setting::get($key, $default)` (`app/Models/Setting.php`) reads from the database with forever-caching (`Cache::rememberForever`), invalidated automatically on save or delete.
- Static fallback defaults reside in `config/company.php`.
- `HandleInertiaRequests::resolveCompany()` merges database overrides over `config('company.*')` and shares it across all Inertia requests as `site.company`.

### E. Image Processing Pipeline
- All admin image uploads pass through `POST /admin/upload` (`App\Http\Controllers\Admin\ImageUploadController`).
- Images are processed by `App\Services\ImageProcessor` using Intervention Image:
  - **Standard Content** (`processStandard`): Generates 3 WebP variants (`content` ≤1280w, `thumb` ≤640w, and `og` 1200×630).
  - **Logos** (`processLogo`): Generates a single WebP variant (≤480×480) preserving transparency without forced cropping.
- Files are stored in `public/uploads/`.
- Deletion via `ImageProcessor::delete()` cleans up all sibling variants matching the UUID prefix.

### F. Security & Access Control
- Admin routes are prefixed with `/admin` and wrapped in `auth` and `noindex` (`NoIndexAdmin` setting `X-Robots-Tag: noindex, nofollow`).
- Superadmin authorization (`EnsureUserIsSuperadmin` middleware) protects `/admin/users` and `/admin/settings`.
- Authentication rate limiting is enforced in `App\Http\Requests\Auth\LoginRequest` (5 attempts per 3 minutes per IP).
- User-submitted HTML in articles is sanitized server-side via `App\Support\ArticleHtmlSanitizer::clean()` (`Purify::config('article')` plus forced `rel="noopener noreferrer" target="_blank"` on links).

### G. Privacy-Safe Analytics
- `TrackPageView` middleware logs visits for public routes using a hashed identifier: `sha256(ip + user-agent + date)`. Raw IPs are never persisted.
- Daily aggregation is executed by the scheduled command `App\Console\Commands\AggregatePageViews` (`routes/console.php`).

---

## 4. Frontend Organization (`resources/js/`)

- `Pages/`: Inertia page components corresponding directly to routes defined in `routes/web.php`.
  - `Pages/Admin/`: Management CMS views (Dashboard, Products, Articles, Partners, Certifications, Settings, Users).
- `Layouts/`:
  - `SiteLayout.tsx`: Public user-facing layout (header, navigation, footer).
  - `AdminLayout.tsx`: Authenticated admin sidebar and shell.
  - `GuestLayout.tsx`: Admin login layout.
- `Components/`:
  - `Components/sections/`: Homepage sections.
  - `Components/admin/`: Admin UI components (`ImageUploadField`, `RichTextEditor`, `ConfirmDialog`, etc.).
  - `Components/graphics/`: Vector graphics and SVG packshots.
- Path alias `@/*` maps directly to `resources/js/*`.

---

## 5. Deployment Constraints (cPanel Shared Hosting)

1. **Never run `npm` on the production server**: The production environment has no Node runtime.
2. **Build assets locally**: Always run `npm run build` locally before committing or uploading `public/build/`.
3. **Deployment Routine**:
   - Pull latest code (`git pull origin main`).
   - Run migrations if schema changed (`php artisan migrate --force`).
   - Clear and refresh production caches:
     ```bash
     php artisan optimize:clear
     php artisan config:cache
     php artisan route:cache
     php artisan view:cache
     ```
   - Verify cron configuration in cPanel for Laravel scheduler (`php artisan schedule:run`).
