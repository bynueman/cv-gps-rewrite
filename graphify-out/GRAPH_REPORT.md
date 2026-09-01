# Graph Report - .  (2026-07-31)

## Corpus Check
- 280 files · ~177,693 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 960 nodes · 1899 edges · 91 communities (67 shown, 24 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 133 edges (avg confidence: 0.8)
- Token cost: 88,765 input · 0 output

## Community Hubs (Navigation)
- Public Site Frontend
- Page-View Analytics & Access Control
- Composer Package Config
- Settings, SEO & Seeders
- Product & Brand Catalog
- Public Controllers & Auth
- Admin CMS Forms
- User Model & Auth Tests
- Composer Scripts & Setup Commands
- Auth & Seeder Test Fixtures
- TypeScript Config
- Article Validation Requests
- Image Upload Pipeline
- Project Documentation & Architecture Notes
- Admin List Pages
- Core Eloquent Models & Dashboard
- Frontend Build Tooling
- Auth Password & Registration Tests
- Admin Layout & Navigation UI
- Frontend Package Dependencies
- Partner Model & Tests
- Settings/User/Contact Validation
- Admin Dialogs & Row Components
- Public Route Controllers
- Admin User CRUD
- Activity Log & Model Relations
- Admin Dashboard UI
- User Management Tests
- Teko Packaging & Product Requests
- Article Controller & Category
- Contact Mail
- SEO Tests
- Home & News Controllers
- JS Path Config
- App Service Provider
- Image Upload Tests
- Login Rate Limiting
- Article HTML Sanitizer
- NPM Package Scripts
- Settings Management Tests
- Message Management Tests
- Authentication Tests
- Partner Store Request
- Sitemap & Robots
- Profile Update Request
- Deploy Process Docs
- Unit Test Example
- Guest Layout & Login Page
- Axios Dependency
- App Bootstrap & Middleware Config
- Concurrently Dependency
- Inertia React Dependency
- React Dependency
- Types/Node Dependency
- TypeScript Dependency
- Google Search Console Verification
- Vite Env Types

## God Nodes (most connected - your core abstractions)
1. `User` - 64 edges
2. `useLang()` - 49 edges
3. `TestCase` - 42 edges
4. `useReveal()` - 35 edges
5. `Product` - 30 edges
6. `Controller` - 28 edges
7. `Article` - 27 edges
8. `Partner` - 27 edges
9. `ImageProcessor` - 25 edges
10. `Brand` - 23 edges

## Surprising Connections (you probably didn't know these)
- `src/lib/content.ts (content source referenced by image README)` --semantically_similar_to--> `Product`  [AMBIGUOUS] [semantically similar]
  public/images/README.md → app/Models/Product.php
- `Bilingual Content: Two Different Strategies (UI dictionary vs DB parallel columns)` --references--> `Product`  [EXTRACTED]
  CLAUDE.md → app/Models/Product.php
- `AggregatePageViews` --references--> `cPanel Cron Job Setup for Scheduler`  [EXTRACTED]
  app/Console/Commands/AggregatePageViews.php → DEPLOY.md
- `Admin Auth & Access Control` --references--> `LoginRequest`  [EXTRACTED]
  CLAUDE.md → app/Http/Requests/Auth/LoginRequest.php
- `Deploy-Specific Security Notes` --references--> `LoginRequest`  [EXTRACTED]
  DEPLOY.md → app/Http/Requests/Auth/LoginRequest.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Admin Auth & Access Control components (superadmin gate, noindex, rate-limited login, redirect wiring)** — app_http_middleware_ensureuserissuperadmin_ensureuserissuperadmin, app_http_middleware_noindexadmin_noindexadmin, app_http_requests_auth_loginrequest_loginrequest, bootstrap_app_app, routes_web_web [EXTRACTED 1.00]
- **Cookie-free page view analytics pipeline (track, filter bots, aggregate on schedule)** — app_http_middleware_trackpageview_trackpageview, app_support_useragentparser_useragentparser, app_console_commands_aggregatepageviews_aggregatepageviews, routes_console_console [EXTRACTED 1.00]
- **SEO / head rendering flow without Inertia SSR (Seo value object, server-rendered blade template, Setting fallback for OG image)** — app_support_seo_seo, resources_views_app_blade_appblade, app_models_setting_setting [EXTRACTED 1.00]

## Communities (91 total, 24 thin omitted)

### Community 0 - "Public Site Frontend"
Cohesion: 0.06
Nodes (69): ArticleCard(), formatDate(), ContactForm(), ExportCTA(), Footer(), BottleSVG(), PouchSVG(), SachetSVG() (+61 more)

### Community 1 - "Page-View Analytics & Access Control"
Cohesion: 0.06
Nodes (23): AdminCreateCommand, AggregatePageViews, DashboardController, EnsureUserIsSuperadmin, NoIndexAdmin, TrackPageView, BrandResource, PageView (+15 more)

### Community 2 - "Composer Package Config"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 3 - "Settings, SEO & Seeders"
Cohesion: 0.07
Nodes (19): HandleInertiaRequests, Setting, Seo, Config-Driven Static Data vs DB-Driven Admin Data, SEO / Head Rendering Without Inertia SSR, Settings: DB Override Over Config Default Pattern, config/brand_assets.php, config/company.php (+11 more)

### Community 4 - "Product & Brand Catalog"
Cohesion: 0.14
Nodes (9): TekoPackaging Enum, ProductController, ProductController, ProductResource, Brand, Product, Two Brands, One Product Model (architecture pattern), Illuminate\Database\Eloquent\Relations\HasMany (+1 more)

### Community 5 - "Public Controllers & Auth"
Cohesion: 0.12
Nodes (10): MessageController, PartnerController, ProfileController, AuthenticatedSessionController, ContactController, Controller, PartnerController, ContactMessage (+2 more)

### Community 6 - "Admin CMS Forms"
Cohesion: 0.13
Nodes (19): ImageUploadField(), UploadedImage, RichTextEditor(), useUnsavedChangesGuard(), AdminLayout(), slugify(), ArticleFormValues, Category (+11 more)

### Community 7 - "User Model & Auth Tests"
Cohesion: 0.10
Nodes (6): User, Illuminate\Foundation\Auth\User, AdminNavigationTest, DashboardTest, EmailVerificationTest, ProfileTest

### Community 8 - "Composer Scripts & Setup Commands"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 9 - "Auth & Seeder Test Fixtures"
Cohesion: 0.12
Nodes (6): BrandSeeder, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Notifications\Notifiable, PasswordResetTest, PartnersLogoWallTest

### Community 10 - "TypeScript Config"
Cohesion: 0.09
Nodes (22): DOM, DOM.Iterable, ES2020, resources/js, compilerOptions, allowImportingTsExtensions, isolatedModules, jsx (+14 more)

### Community 11 - "Article Validation Requests"
Cohesion: 0.13
Nodes (5): ensureBodyNotEmpty(), ensureFeaturedCap(), StoreArticleRequest, UpdateArticleRequest, Illuminate\Contracts\Validation\Validator

### Community 12 - "Image Upload Pipeline"
Cohesion: 0.15
Nodes (8): ImageUploadController, SettingController, ImageUploadController, ImageProcessor, Admin Image Upload Pipeline (re-decode + WebP re-encode), Illuminate\Http\JsonResponse, Illuminate\Http\UploadedFile, WebP Static Asset Export Pipeline (product photos/logos)

### Community 13 - "Project Documentation & Architecture Notes"
Cohesion: 0.13
Nodes (22): Bilingual Content: Two Different Strategies (UI dictionary vs DB parallel columns), Frontend Structure (Inertia Pages/Layouts/Components), CV Gama Putra Santosa (GPS Group), CLAUDE.md — Repository Guidance for Claude Code, Inertia.js, Kuicip (cassava chips brand), Putri Teko (traditional herbal drinks brand), Backup Strategy (DB dump + uploads rsync) (+14 more)

### Community 14 - "Admin List Pages"
Cohesion: 0.14
Nodes (13): EmptyState(), EyeIcon(), PencilIcon(), ArticleListItem, formatDate(), Index(), formatDate(), Index() (+5 more)

### Community 15 - "Core Eloquent Models & Dashboard"
Cohesion: 0.18
Nodes (3): Article, Illuminate\Database\Eloquent\Builder, Illuminate\Database\Eloquent\Model

### Community 16 - "Frontend Build Tooling"
Cohesion: 0.11
Nodes (19): autoprefixer, @headlessui/react, laravel-vite-plugin, devDependencies, autoprefixer, @headlessui/react, laravel-vite-plugin, postcss (+11 more)

### Community 17 - "Auth Password & Registration Tests"
Cohesion: 0.13
Nodes (6): Illuminate\Foundation\Testing\TestCase, PasswordConfirmationTest, PasswordUpdateTest, RegistrationTest, ExampleTest, TestCase

### Community 18 - "Admin Layout & Navigation UI"
Cohesion: 0.15
Nodes (13): NAV_ITEMS, Sidebar(), useSidebarCollapsed(), ToastContext, ToastContextValue, ToastItem, ToastKind, ToastProvider() (+5 more)

### Community 19 - "Frontend Package Dependencies"
Cohesion: 0.12
Nodes (17): gsap, lucide-react, dependencies, gsap, lucide-react, recharts, @tiptap/extension-image, @tiptap/extension-link (+9 more)

### Community 20 - "Partner Model & Tests"
Cohesion: 0.18
Nodes (3): Partner, PartnerCrudTest, PartnersDirectoryTest

### Community 21 - "Settings/User/Contact Validation"
Cohesion: 0.19
Nodes (4): UpdateSettingsRequest, UpdateUserRequest, StoreContactMessageRequest, Illuminate\Foundation\Http\FormRequest

### Community 22 - "Admin Dialogs & Row Components"
Cohesion: 0.22
Nodes (7): ConfirmDialog(), DeleteArticleButton(), DeleteButton(), formatDate(), MessageDetail, Show(), UserRow

### Community 25 - "Activity Log & Model Relations"
Cohesion: 0.18
Nodes (3): UpdatePartnerRequest, ActivityLog, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 26 - "Admin Dashboard UI"
Cohesion: 0.20
Nodes (9): ACTION_LABELS, ActivityItem, Dashboard(), DONUT_COLORS, formatDateTime(), MODULE_LABELS, Stat, TopPage (+1 more)

### Community 29 - "Article Controller & Category"
Cohesion: 0.20
Nodes (3): ArticleController, baseRules(), ArticleCategory

### Community 30 - "Contact Mail"
Cohesion: 0.31
Nodes (6): ContactMessageMail, Illuminate\Bus\Queueable, Illuminate\Mail\Mailable, Illuminate\Mail\Mailables\Content, Illuminate\Mail\Mailables\Envelope, Illuminate\Queue\SerializesModels

### Community 32 - "Home & News Controllers"
Cohesion: 0.31
Nodes (3): HomeController, NewsController, ArticleResource

### Community 33 - "JS Path Config"
Cohesion: 0.22
Nodes (8): compilerOptions, baseUrl, paths, exclude, ziggy-js, node_modules, public, ./vendor/tightenco/ziggy

### Community 34 - "App Service Provider"
Cohesion: 0.29
Nodes (4): AppServiceProvider, Illuminate\Support\ServiceProvider, vite, vite

### Community 37 - "Article HTML Sanitizer"
Cohesion: 0.33
Nodes (3): ArticleHtmlSanitizer, Article HTML Sanitization, config/purify.php

### Community 38 - "NPM Package Scripts"
Cohesion: 0.29
Nodes (6): private, $schema, scripts, build, dev, type

### Community 45 - "Deploy Process Docs"
Cohesion: 0.50
Nodes (4): First-Time Deploy Setup Process, cv-gps-rewrite GitHub Repository, Routine Deploy Process, gpsfood.id Production Domain

## Ambiguous Edges - Review These
- `Product` → `src/lib/content.ts (content source referenced by image README)`  [AMBIGUOUS]
  public/images/README.md · relation: semantically_similar_to
- `public/images/README.md — Image Asset Inventory & Guidelines` → `Possible Stale/Legacy Documentation (references src/lib/content.ts, not the current resources/js DB-driven architecture)`  [AMBIGUOUS]
  public/images/README.md · relation: rationale_for
- `Frontend Structure (Inertia Pages/Layouts/Components)` → `Possible Stale/Legacy Documentation (references src/lib/content.ts, not the current resources/js DB-driven architecture)`  [AMBIGUOUS]
  public/images/README.md · relation: conceptually_related_to
- `src/lib/content.ts (content source referenced by image README)` → `Possible Stale/Legacy Documentation (references src/lib/content.ts, not the current resources/js DB-driven architecture)`  [AMBIGUOUS]
  public/images/README.md · relation: rationale_for

## Knowledge Gaps
- **170 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+165 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **24 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Product` and `src/lib/content.ts (content source referenced by image README)`?**
  _Edge tagged AMBIGUOUS (relation: semantically_similar_to) - confidence is low._
- **What is the exact relationship between `public/images/README.md — Image Asset Inventory & Guidelines` and `Possible Stale/Legacy Documentation (references src/lib/content.ts, not the current resources/js DB-driven architecture)`?**
  _Edge tagged AMBIGUOUS (relation: rationale_for) - confidence is low._
- **What is the exact relationship between `Frontend Structure (Inertia Pages/Layouts/Components)` and `Possible Stale/Legacy Documentation (references src/lib/content.ts, not the current resources/js DB-driven architecture)`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `src/lib/content.ts (content source referenced by image README)` and `Possible Stale/Legacy Documentation (references src/lib/content.ts, not the current resources/js DB-driven architecture)`?**
  _Edge tagged AMBIGUOUS (relation: rationale_for) - confidence is low._
- **Why does `WebP Static Asset Export Pipeline (product photos/logos)` connect `Image Upload Pipeline` to `Public Site Frontend`, `Project Documentation & Architecture Notes`?**
  _High betweenness centrality (0.146) - this node is a cross-community bridge._
- **Why does `ProductPackshot()` connect `Public Site Frontend` to `Image Upload Pipeline`?**
  _High betweenness centrality (0.144) - this node is a cross-community bridge._
- **Why does `ImageProcessor` connect `Image Upload Pipeline` to `Product & Brand Catalog`, `Public Controllers & Auth`, `Article Validation Requests`, `Core Eloquent Models & Dashboard`, `Public Route Controllers`, `Article Controller & Category`?**
  _High betweenness centrality (0.114) - this node is a cross-community bridge._