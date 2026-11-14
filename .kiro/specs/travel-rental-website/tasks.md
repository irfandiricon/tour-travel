# Implementation Plan: Travel Rental Website

## Overview

This plan builds the Travel Rental Website as a CodeIgniter 4.7.x (PHP 8.5.x) application following the workspace three-tier controller pattern: public/admin controllers extend `BaseController`, `Api*Controller` proxies extend `Controller` and forward to `api/v1/*` via `Service::ApiRequestWithHeaderToken()`, and the `api/v1` tier owns DB access and validation.

The work proceeds bottom-up: first the pure helpers (`LanguageResolver`, `WhatsAppLinkBuilder`, `ImageUploadHandler`) and the shared validation/filter/pagination functions — because these carry most of the correctness properties and need no infrastructure. Next the database schema and models, then the `api/v1` tier (validation + persistence), then the proxy layer, then the admin and public surfaces, and finally wiring (routes, filters, views) and integration tests. Each step builds on the previous one and ends with everything wired into the running application.

Property-based tests use **Eris** (`giorgiosironi/eris`) with a **minimum of 100 iterations** per property, each tagged `// Feature: travel-rental-website, Property {n}: {property_text}`. Each of Properties 1–16 is implemented by exactly one property test.

## Tasks

- [x] 1. Bootstrap the CodeIgniter 4.7.x project and test harness
  - Install CodeIgniter 4.7.x into `d:\Application\tour-travel` (composer `codeigniter4/appstarter` or equivalent) targeting PHP 8.5.x
  - Add dev dependencies `phpunit/phpunit` and `giorgiosironi/eris`; configure `phpunit.xml.dist` with a `tests/unit`, `tests/session`, and `tests/database` suite layout mirroring `bimbel-nipasn`
  - Create the base directory structure: `app/Controllers`, `app/Controllers/Admin`, `app/Controllers/ApiV1`, `app/Models`, `app/Helpers`, `app/Libraries`, `app/Views`, `app/Database/Migrations`
  - Verify `vendor/bin/phpunit` runs with zero tests and `php spark` responds
  - _Requirements: 9.1, 9.2_

- [x] 2. Implement the LanguageResolver helper (pure language resolution + fallback)
  - [x] 2.1 Implement `LanguageResolver` library
    - Create `app/Libraries/LanguageResolver.php` with `SUPPORTED = ['en','id','ms']`, `DEFAULT = 'en'`, `normalize(?string): string`, and `resolve(array $values, string $lang): string`
    - `normalize` returns `en` for null/absent/unsupported input, otherwise the input unchanged
    - `resolve` returns the selected-language value when non-empty, else the `en` value when non-empty, else empty string; never raises
    - _Requirements: 1.2, 1.3, 2.2, 2.3, 2.6, 2.7_

  - [x] 2.2 Write property test for selected-language resolution
    - **Property 1: Selected-language resolution** — for any field map with a non-empty value for a selected supported language, `resolve` returns that value
    - **Validates: Requirements 1.2, 2.3**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 1`

  - [x] 2.3 Write property test for fallback chain and normalization
    - **Property 2: Resolver fallback chain and normalization** — `normalize` maps absent/null/unsupported to `en` and otherwise identity; `resolve` yields selected → en → empty string and never errors
    - **Validates: Requirements 1.3, 2.2, 2.6, 2.7**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 2`

- [x] 3. Implement the WhatsAppLinkBuilder helper (pure E.164 validation + link building)
  - [x] 3.1 Implement `WhatsAppLinkBuilder` library
    - Create `app/Libraries/WhatsAppLinkBuilder.php` with `isValidNumber(?string): bool` (regex `^\+\d{8,15}$`) and `build(?string $number, string $prefill = ''): ?string`
    - `build` returns `https://wa.me/<digits>` (leading `+` stripped) with `?text=` carrying the prefill truncated to ≤200 chars and rawurl-encoded; returns null when the number is invalid/empty
    - _Requirements: 7.1, 7.5, 7.6, 7.7_

  - [x] 3.2 Write property test for E.164 validation with retain-previous
    - **Property 15: WhatsApp number E.164 validation with retain-previous** — `isValidNumber` is true iff the string matches `^\+\d{8,15}$`; a failing value is rejected and leaves the stored number unchanged, a passing value persists unchanged (number-save persistence covered against an in-memory/transactional double in task 7)
    - **Validates: Requirements 7.1, 7.2, 7.3**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 15`

  - [x] 3.3 Write property test for link generation and booking visibility
    - **Property 16: WhatsApp link generation and booking visibility** — for valid numbers `build` yields `https://wa.me/<digits>` with decoded `text` equal to the prefill truncated to ≤200 chars; for invalid/empty it returns null (booking shown iff valid)
    - **Validates: Requirements 7.5, 7.6, 7.7**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 16`

- [x] 4. Implement shared validation, filtering, ordering, and pagination functions
  - [x] 4.1 Implement content validators (required-en, field length, section completeness)
    - Create `app/Libraries/ContentValidator.php` with pure functions returning a field-keyed error map: `validateRequiredEn()`, `validateFieldLengths()` (Information/FAQ answer ≤5000, News title ≤200, News body ≤20000, FAQ question ≤500), and `validateSectionLanguages()` (hero/about/gallery/contact require en+id+ms, naming each missing language)
    - Functions return an empty map when valid; no DB access
    - _Requirements: 2.8, 2.9, 4.1, 4.3, 4.5, 5.1, 5.3, 5.5, 6.4, 8.1, 8.6_

  - [x] 4.2 Implement `ImageUploadHandler` validator
    - Create `app/Libraries/ImageUploadHandler.php` validating type ∈ {JPEG, PNG, WebP} and size ≤ 5 MB, returning a violation identifying allowed types or the 5 MB maximum
    - _Requirements: 6.7, 6.8, 6.9_

  - [x] 4.3 Implement published-filter, fleet-image-source, and news-ordering functions
    - Create `app/Libraries/ContentFilter.php` with `publishedOnly($items)` (keeps only `status === 'published'`), `fleetImageSources($items, $placeholder)` (same length as input; item image when non-empty else placeholder), and `publishedNewsDescending($items, $now)` (published + `publish_date <= now`, ordered descending)
    - _Requirements: 1.4, 1.5, 1.6, 1.8, 4.8, 5.2, 5.8, 8.4_

  - [x] 4.4 Implement pagination slice/metadata function
    - Create `app/Libraries/Paginator.php` with `paginate($items, $page, $perPage)` clamping per_page to 1..100 (default 10), page minimum 1, returning the ordered slice plus `{total, page, per_page}`
    - _Requirements: 4.2, 5.2_

  - [x] 4.5 Write property test for required-field (mandatory-en) rejection
    - **Property 6: Required-field (mandatory-en) rejection** — any record missing/empty in the default language (en) is rejected 422 naming each missing field, previously stored record unchanged (persistence side checked in api/v1 tasks)
    - **Validates: Requirements 2.9, 4.5, 5.5, 8.6**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 6`

  - [x] 4.6 Write property test for field-length validation
    - **Property 7: Field-length validation** — accepted only when every required translatable field in en/id/ms is within range, else 422 naming each out-of-range field
    - **Validates: Requirements 2.8, 4.1, 4.3, 5.1, 5.3, 8.1**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 7`

  - [x] 4.7 Write property test for section-content language completeness rejection
    - **Property 13: Section-content language completeness rejection** — a section update with fewer than all three languages is rejected naming exactly the missing languages
    - **Validates: Requirements 6.4**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 13`

  - [x] 4.8 Write property test for image upload validation
    - **Property 14: Image upload validation** — upload accepted iff type ∈ {JPEG,PNG,WebP} and size ≤ 5 MB; rejection leaves record unchanged and names the violated constraint
    - **Validates: Requirements 6.7, 6.8, 6.9**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 14`

  - [x] 4.9 Write property test for published-items filter
    - **Property 4: Published-items filter excludes non-published** — for arbitrary status values the dataset contains exactly `published` items and excludes all others
    - **Validates: Requirements 1.4, 4.8, 8.4**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 4`

  - [x] 4.10 Write property test for fleet image-source selection and list totality
    - **Property 5: Fleet image-source selection and list totality** — output length equals input length; each entry is the item image when non-empty, else placeholder
    - **Validates: Requirements 1.5, 1.6, 1.8**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 5`

  - [x] 4.11 Write property test for news published filtering and descending order
    - **Property 9: News published filtering and descending order** — output contains only published items with `publish_date <= now`, ordered descending
    - **Validates: Requirements 5.2, 5.8**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 9`

  - [x] 4.12 Write property test for pagination metadata consistency
    - **Property 8: Pagination metadata consistency** — list length ≤ per_page, total equals dataset size, current page equals requested page (min 1), returned page is the correct ordered slice
    - **Validates: Requirements 4.2, 5.2**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 8`

- [x] 5. Checkpoint - Ensure all helper and property tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Create the database schema and models
  - [x] 6.1 Create migrations for all entities
    - Add migrations for `c_information`, `c_news`, `c_fleet`, `c_faqs`, `c_gallery`, `site_config`, `admin_users` with sibling `_en`/`_id`/`_ms` translation columns (`_en` NOT NULL where required), `status` ENUM('draft','published','deleted'), timestamps + `deleted_at`, plus `c_news.publish_date`, `c_gallery.sort_order`, `site_config.whatsapp_number` VARCHAR(16), and `admin_users` auth columns (`password_hash`, `status`, `failed_attempts`, `locked_until`, `last_login_at`)
    - _Requirements: 2.8, 4.1, 5.1, 5.8, 6.3, 7.1, 8.1_

  - [x] 6.2 Create models for all entities
    - Create `InformationModel`, `NewsModel`, `FleetModel`, `FaqModel`, `GalleryModel`, `SiteConfigModel`, `AdminUserModel` in `app/Models`, each declaring `$table`, `$primaryKey`, and `$allowedFields` covering every translation column, status, timestamps, and entity-specific columns; use `whereNotIn('status',['deleted'])` for soft-delete reads
    - _Requirements: 4.4, 5.4, 8.2_

- [x] 7. Implement the api/v1 content tier (validation + persistence)
  - [x] 7.1 Implement `ApiV1\ContentsInformationController`
    - Implement `index` (paginated), `getAllActive` (published only), `show/$1`, `create`, `update/$1`, `delete/$1`, `activate/$1`, `deactivate/$1` using `ContentValidator`, `ContentFilter`, `Paginator` and `InformationModel`
    - Return 200/201 + data on success, 422 + field errors on invalid (no persist), 404 on missing id
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.8_

  - [x] 7.2 Implement `ApiV1\ContentsNewsController`
    - Same action set; create sets `publish_date`; list ordered by publish date descending; landing `getAllActive` uses `publishedNewsDescending`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.8_

  - [x] 7.3 Implement `ApiV1\ContentsFleetController` with image handling
    - CRUD + active; `create`/`update` run `ImageUploadHandler` on multipart image, store via a storage path and set `image_path`; reject invalid uploads leaving the record unchanged
    - _Requirements: 6.5, 6.7, 6.8, 6.9, 4.8_

  - [x] 7.4 Implement `ApiV1\ContentsFaqsController`
    - `create` (question ≤500, answer ≤5000, en required), `delete`, `getAllActive`; 404 on missing id; 422 naming empty en question/answer
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.6_

  - [x] 7.5 Implement `ApiV1\ContentsGalleryController` with image upload + ordering
    - `create`/`update`/`delete` with required image (via `ImageUploadHandler`), optional captions, `sort_order`; `getAllActive` ordered by `sort_order`
    - _Requirements: 6.7, 6.8, 6.9_

  - [x] 7.6 Implement `ApiV1\SiteConfigController`
    - `getConfig` (single active row) and `save` running `validateSectionLanguages` (hero/about/gallery/contact all three languages) and `WhatsAppLinkBuilder::isValidNumber`; reject invalid leaving previous config unchanged; allow empty whatsapp number (booking disabled)
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 7.2, 7.3, 7.4_

  - [x] 7.7 Write property test for site-config invalid-field retain-previous
    - **Property 11: Site-config invalid-field rejection retains previous configuration** — invalid save is rejected naming each invalid field; stored config stays identical to the previous one (use an in-memory/transactional double)
    - **Validates: Requirements 6.2**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 11`

  - [x] 7.8 Write property test for section-content all-languages round trip
    - **Property 12: Section-content all-languages save round trip** — saving a section with all three languages then reading it back returns the same three values (transactional/in-memory double)
    - **Validates: Requirements 6.3**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 12`

  - [x] 7.9 Write example tests for api/v1 CRUD contracts
    - Create returns id + status (Req 4.1, 6.1, 6.5, 6.6, 8.1); delete returns 200 (Req 4.4, 5.4, 8.2); not-found returns 404 (Req 4.6, 5.6, 8.3); empty whatsapp number saves and disables booking (Req 7.4)
    - _Requirements: 4.1, 4.4, 4.6, 5.4, 5.6, 6.1, 6.5, 6.6, 7.4, 8.1, 8.2, 8.3_

- [x] 8. Implement the api/v1 auth tier
  - [x] 8.1 Implement `ApiV1\AuthController::login`
    - Verify username + `password_verify()` against an `active` account; increment `failed_attempts` and set `locked_until` on the 5th failure within 15 minutes; return a generic error that never reveals which field was wrong; reset counter + set `last_login_at` on success
    - _Requirements: 3.2, 3.3, 3.5_

  - [x] 8.2 Write unit tests for auth validation and lockout boundary
    - Generic failure message (Req 3.3); four failures do not lock, the fifth locks for 15 minutes (Req 3.5)
    - _Requirements: 3.3, 3.5_

- [x] 9. Checkpoint - Ensure all api/v1 and property tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Implement the proxy layer (`Api*Controller extends Controller`)
  - [x] 10.1 Implement the proxy response-mapping helper and `ApiInformationController`
    - Extract the null-safe mapping (`status/message/data/total` with `?? ` fallbacks over the `Service` result) into a reusable method; implement `ApiInformationController extends Controller` with `use ResponseTrait`, declared `protected $service`, `new Service()` in `__construct()`; AJAX methods (`apiList`, `apiCreate`, `apiUpdate`, `apiDelete`, `apiActivate`) return `setJSON`, PHP-consumed methods (`apiShow`, `apiListActive`) return arrays; forward to `api/v1/contents-information`
    - _Requirements: 9.1, 9.3, 9.4, 9.5, 9.6_

  - [x] 10.2 Implement `ApiNewsController`, `ApiFaqController`, `ApiSiteConfigController` proxies
    - Same pattern; `ApiSiteConfigController` exposes `apiGetConfig` (array) and `apiSave` (JSON)
    - _Requirements: 9.1, 9.3, 9.4, 9.5, 9.6_

  - [x] 10.3 Implement `ApiFleetController` and `ApiGalleryController` proxies (multipart forwarding)
    - Forward image-bearing create/update via `Service::ApiRequesUploadWithHeaderToken()` so `CURLFile` multipart reaches `api/v1`; `apiListActive` returns array for the landing page
    - _Requirements: 9.1, 9.3, 9.4, 9.5, 9.6_

  - [x] 10.4 Implement `ApiAuthController` proxy
    - Forward sign-in to `api/v1/auth/login` and return the mapped response
    - _Requirements: 9.1, 9.5_

  - [x] 10.5 Write property test for proxy null-safe failure mapping
    - **Property 10: Proxy null-safe failure mapping** — for any failure result from `Service` (null/empty/non-JSON/non-200) the proxy returns a non-200 status with an upstream-failed message, never a false success nor an unhandled error (stub the `Service`)
    - **Validates: Requirements 4.7, 5.7, 9.5**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 10`

  - [x] 10.6 Write example tests for proxy response-shape contracts
    - AJAX methods return JSON with `status/message/data/total` (Req 9.3); PHP-consumed methods return an array (Req 9.4)
    - _Requirements: 9.3, 9.4_

- [x] 11. Implement authentication filters, admin base, and admin controllers
  - [x] 11.1 Configure session and the `admin` auth filter
    - Configure `Config\Session` / `App.sessionExpiration` for 30-minute inactivity; implement an `admin` route filter that redirects unauthenticated/expired requests for admin pages (except sign-in) to sign-in without rendering protected content
    - _Requirements: 3.1, 3.2, 3.6_

  - [x] 11.2 Implement Admin `AuthController` and `DashboardController`
    - `AuthController extends BaseController`: render signin page, submit via `ApiAuthController` and establish/destroy the session (signin/signout); `DashboardController` renders the dashboard; declare every constructor-assigned property; do not re-wire authCheck/menu/service/user
    - _Requirements: 3.2, 3.3, 3.4, 9.2, 9.6, 9.7_

  - [x] 11.3 Implement Admin content controllers
    - `Admin\InformationController`, `NewsController`, `FleetController`, `FaqController`, `GalleryController`, `SiteConfigController` each extending `BaseController`, instantiating only their specific `Api*Controller`, rendering list/create/edit views; edit forms load data via the array-returning `apiShow`
    - _Requirements: 4.1-4.6, 5.1-5.6, 6.1-6.9, 7.1-7.4, 8.1-8.3, 9.2, 9.6, 9.7_

  - [x] 11.4 Create admin views and AJAX wiring
    - Build list/create/edit view templates with multi-language (`_en`/`_id`/`_ms`) inputs and image upload; wire fetch/AJAX to the proxy endpoints, rendering toasts and inline field errors from `422` responses
    - _Requirements: 2.8, 4.5, 5.5, 6.4, 7.3, 8.6_

- [x] 12. Implement the public landing surface
  - [x] 12.1 Implement `PublicBaseController`, `LandingController`, and `LanguageController`
    - `PublicBaseController extends Controller` wiring `$this->service` and the resolver without `requireLogin()`; `LandingController::index()` resolves the active language (query → session → en), calls each section's array-returning proxy method, runs fields through `LanguageResolver`, builds fleet image sources and WhatsApp links, and renders without aborting on a failed/empty section; `LanguageController::set($lang)` persists only valid languages to `session('lang')` and leaves the selection unchanged for invalid values
    - _Requirements: 1.1-1.8, 2.1-2.7, 5.8, 5.9, 7.5, 7.6, 7.7, 8.4, 8.5, 9.2_

  - [x] 12.2 Create landing views with sections, language selector, and booking actions
    - Hero/about/fleet/gallery/FAQ/news/contact sections with empty-state/unavailable indicators; language selector offering exactly en/id/ms; booking action rendered only when a valid WhatsApp link exists, with fleet name (≤200 chars) in the prefill
    - _Requirements: 1.1, 1.5, 1.6, 1.7, 1.8, 2.1, 5.9, 7.5, 7.6, 7.7, 8.4, 8.5_

  - [x] 12.3 Write property test for invalid-language preserve-persisted-selection
    - **Property 3: Invalid language preserves persisted selection** — an unsupported request language renders `en` and leaves the persisted selection unchanged; a valid language set-then-read round-trips
    - **Validates: Requirements 2.4, 2.5**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 3`

  - [x] 12.4 Write example tests for landing rendering and language selector
    - Language selector renders exactly en/id/ms (Req 2.1); sections with no published items render empty-state (Req 1.8, 5.9, 8.5)
    - _Requirements: 2.1, 1.8, 5.9, 8.5_

- [x] 13. Register routes and wire the application together
  - [x] 13.1 Register all routes
    - Public: `/` (LandingController), language set route; admin group behind the `admin` filter (signin/signout, dashboard, entity CRUD pages); `api/v1` group (`namespace App\Controllers\ApiV1`, jwt/internal filter) with RESTful routes for every entity plus `auth/login`
    - _Requirements: 3.1, 9.1, 9.2_

  - [x] 13.2 Seed an admin user and default site config
    - Add a seeder creating one `active` admin user (hashed password) and a default `site_config` row so the admin can sign in and the landing page renders
    - _Requirements: 3.2, 6.1_

- [x] 14. Write integration tests for end-to-end flows
  - [x] 14.1 Write landing-page and WhatsApp integration tests
    - Landing page renders all sections end-to-end within the 3-second budget (Req 1.1); a configured valid number produces a working `wa.me` link (Req 7.5)
    - _Requirements: 1.1, 7.5_

  - [x] 14.2 Write auth/session integration tests
    - Valid active credentials establish a 30-minute-inactivity session and redirect to dashboard (Req 3.2); sign-out terminates the session (Req 3.4); unauthenticated admin access redirects to sign-in (Req 3.1); inactivity ≥30 minutes de-authenticates (Req 3.6)
    - _Requirements: 3.1, 3.2, 3.4, 3.6_

  - [x] 14.3 Write site-config timing and architectural conformance tests
    - Site_Configuration save completes within 5 seconds (Req 6.1); reflection tests assert every `Api*Controller` extends `Controller` not `BaseController`, Page/Admin controllers extend `BaseController`, and each controller declares every constructor-assigned property
    - _Requirements: 6.1, 9.1, 9.2, 9.6, 9.7_

- [x] 15. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Extension Tasks: Bootstrap UI, SEO, and Visitor Analytics (Requirements 10–12)

The tasks below extend the completed plan. Tasks 1–15 are complete and MUST NOT be modified. Property tests continue the numbering at Property 17 (Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property {n}`).

**Dev-environment constraints (apply to every task below):**
- Windows with PowerShell. Run the app with the PHP built-in server (`php spark serve`). The proxy tier needs a second instance on port 8081 that `INTERNAL_API_BASE_URL` points to (for example `php spark serve --port 8081`); start servers manually in a terminal, do not run them from an agent task.
- MySQL/MariaDB is available at `127.0.0.1:3307`, database `tour_travel`, for migration verification.
- Existing tables were created from `database/schema.sql`, so the `migrations` table may be out of sync. Every NEW migration MUST be idempotent (`createTable(..., true)` and `fieldExists()` guards) and MUST be applied to the local DB with `php spark migrate`. Do NOT edit existing migrations. `database/schema.sql` MUST be updated with the same DDL (use `CREATE TABLE IF NOT EXISTS`).
- Run tests with `vendor/bin/phpunit` (Windows: `vendor\bin\phpunit`) and syntax-check changed PHP files with `php -l`.
- Preserve existing view-model keys, element ids, routes, `admin/api/<slug>/*` endpoints, CSRF handling, inline 422 errors, and EN/ID/MS tab behavior used by `admin.js` and existing tests.
- When two tasks write the same file, the task with the lower number runs first (the Task Dependency Graph already separates them into different waves).

- [x] 16. Add vendor assets and base layouts
  - [x] 16.1 Download and bundle vendor assets locally
    - Download pinned releases from jsDelivr (for example `https://cdn.jsdelivr.net/npm/bootstrap@5.3.x/dist/...`, `bootstrap-icons@1.11.x`, `chart.js@4.x/dist/chart.umd.min.js`) with PowerShell `Invoke-WebRequest` into `public/assets/vendor/bootstrap` (`css/bootstrap.min.css`, `js/bootstrap.bundle.min.js`), `public/assets/vendor/bootstrap-icons` (`font/bootstrap-icons.min.css`, `font/fonts/*.woff2|woff`), and `public/assets/vendor/chartjs/chart.umd.min.js`
    - Rewrite font URLs in the Bootstrap Icons CSS if needed so they resolve locally; add `public/assets/vendor/README.md` recording exact versions and source URLs; add a default `public/assets/img/og-default.jpg`
    - Verify that no downloaded CSS or JS requires a runtime CDN request
    - _Requirements: 10.1, 10.2, 12.26_

  - [x] 16.2 Create the public layout
    - Create `app/Views/layouts/public.php` with `<html lang>`, viewport meta, an `include` of `partials/seo_meta` that tolerates a missing `seo` variable, local Bootstrap/Icons CSS, a responsive `navbar-expand-lg` with toggler, a language dropdown with exactly the `lang/en`, `lang/id`, `lang/ms` links, `<main>`, `<footer>`, a floating WhatsApp button (only when a WhatsApp link exists), and local JS
    - Create `public/assets/css/landing.css` (focus styles, 4.5:1 contrast palette, floating button, carousel overlay) and an empty `public/assets/js/landing.js` placeholder
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.14, 10.15, 10.19, 10.20, 11.11_

  - [x] 16.3 Rewrite the admin layout
    - Rewrite `app/Views/admin/layout.php` with a Bootstrap sidebar (`offcanvas-lg offcanvas-start`, toggle button below 992 px), top bar, `.toast-container`, `<meta name="robots" content="noindex, nofollow">`, local assets, and `public/assets/css/admin.css`
    - Keep every existing `$this->renderSection` name, element id, CSRF meta/token handling, and script include order that `admin.js` relies on
    - _Requirements: 10.1, 10.2, 10.3, 10.5, 10.18, 10.19, 10.20, 10.21, 11.16_

  - [x] 16.4 Write smoke tests for vendor assets and layouts
    - Assert the vendor files exist and the rendered layouts reference only `/assets/vendor/...` (no `https://cdn.` or `jsdelivr` strings), include the viewport meta, `navbar-toggler`, and `offcanvas`
    - _Requirements: 10.1, 10.2, 10.4, 10.5_

- [x] 17. Redesign the landing page with Bootstrap and a carousel
  - [x] 17.1 Extend the `LandingController` view-model
    - Add `heroSlides` (first 5 published gallery items by sort order with resolved image URL and localized alt text) and `trackBookingUrl` (`base_url('track/booking')`) without changing or removing existing view-model keys
    - _Requirements: 10.6, 10.7, 10.21, 12.9_

  - [x] 17.2 Redesign `landing/index.php`
    - Extend `layouts/public`; build the hero carousel (`data-bs-ride="carousel"`, `data-bs-interval="5000"`, `data-bs-pause="hover"`, `data-bs-keyboard="true"`, indicators and prev/next controls only when slides > 1, static hero when zero slides, hero title as the only `h1`), fleet cards grid (`row-cols-1 row-cols-sm-2 row-cols-lg-3`, `loading="lazy"` and `alt` on images outside the first slide), about, information, news, gallery, FAQ accordion, and contact sections
    - Keep section ids, localized empty-state strings, the *Book via WhatsApp* button only when the link is non-null (with `esc(..., 'attr')`), and add `data-track-booking`, `data-fleet-id`, `data-fleet-name` attributes on booking buttons
    - _Requirements: 10.6, 10.7, 10.8, 10.9, 10.11, 10.12, 10.13, 10.14, 10.15, 10.19, 10.21, 11.11, 11.12, 11.13, 12.9_

  - [x] 17.3 Implement `landing.js`
    - Disable carousel autoplay and fade transition when `prefers-reduced-motion: reduce` matches; send booking clicks via `navigator.sendBeacon` (fallback `fetch` with `keepalive`) to `trackBookingUrl` without delaying the `wa.me` navigation; ignore failures silently
    - _Requirements: 10.10, 12.9_

  - [x] 17.4 Update landing tests
    - Update `tests/unit/LandingViewTest.php` and `tests/unit/LandingIntegrationTest.php` where the markup legitimately changes (language dropdown class names, carousel) while keeping their intent: exactly three links `lang/en`, `lang/id`, `lang/ms`, localized empty-state strings, booking button only when the link is non-null, `esc()` with `attr` for `wa.me` links
    - Add example tests for the carousel with 0, 1, 3, and 7 gallery items (static hero, no controls for 1 slide, cap at 5), one `h1`, accordion, and floating button visibility
    - _Requirements: 10.6, 10.7, 10.8, 10.12, 10.13, 10.14, 10.15, 11.11, 11.12, 11.13_

- [x] 18. Redesign the CMS Admin and sign-in
  - [x] 18.1 Redesign the sign-in page
    - Rewrite `app/Views/admin/signin.php` as a centered, responsive Bootstrap card with labelled username/password inputs, an accessible generic error alert, visible focus, and `noindex, nofollow`; keep form field names, action, and CSRF field
    - _Requirements: 10.1, 10.3, 10.19, 10.20, 11.16_

  - [x] 18.2 Redesign admin list and form views
    - Update `admin/*/index.php`, `admin/*/form.php`, and `admin/partials/*` to Bootstrap: `.table-responsive` wrappers, button groups with `aria-label`ed icon buttons, form grid, `.invalid-feedback` containers beside each field, and Bootstrap nav tabs for EN/ID/MS; keep ids and `data-*` hooks used by `admin.js`
    - _Requirements: 10.16, 10.17, 10.19, 10.21_

  - [x] 18.3 Redesign the site configuration page with an SEO tab
    - Update `admin/site-config/index.php` with section tabs and a new **SEO** tab containing `site_name_{en,id,ms}`, `meta_description_{en,id,ms}`, `meta_keywords_{en,id,ms}`, and `og_image` (file upload plus current image preview); keep the existing WhatsApp and section field names
    - _Requirements: 11.17, 10.17, 10.21_

  - [x] 18.4 Write admin view tests
    - Assert `noindex` meta in the admin layout and sign-in, `table-responsive` on list pages, labelled sign-in inputs, and presence of the SEO tab fields
    - _Requirements: 10.16, 10.19, 11.16, 11.17_

  - [x] 18.5 Update `admin.js` for Bootstrap toasts and validation feedback
    - Render operation results in Bootstrap toasts and map 422 `errors` to `.is-invalid` and `.invalid-feedback` text; keep the existing endpoints, CSRF header handling, tab switching, and request payload shapes unchanged
    - _Requirements: 10.17, 10.18, 10.21_

- [x] 19. Implement SEO
  - [x] 19.1 Implement `SeoMetaBuilder`
    - Create `app/Libraries/SeoMetaBuilder.php` (pure) with `build`, `truncate` (multibyte-safe, ≤160), `alternates` (en, id, ms, x-default), `jsonLd` (`AutoRental` plus `FAQPage` when FAQ items exist), and `encodeJsonLd` (`JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP`); use `LanguageResolver` for localized fields; og:image falls back gallery/hero image → site_config `og_image` → bundled default, always absolute
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8, 11.9, 11.10, 12.28_

  - [x] 19.2 Create `partials/seo_meta.php` and wire the landing controller
    - Render title, description, optional keywords, robots, canonical, hreflang alternates, Open Graph, Twitter Card, and a `<script type="application/ld+json">` block with correct `esc()` contexts; build `seo` in `LandingController::index()` from the view-model and site config
    - _Requirements: 11.1, 11.3, 11.4, 11.6, 11.8, 11.9, 11.10_

  - [x] 19.3 Implement robots.txt and sitemap.xml routes
    - Create `App\Controllers\SeoController extends PublicBaseController` with `robots()` (`text/plain`, allow `/`, disallow `/admin`, `/signin`, `/api`, `/dashboard`, `Sitemap:` line) and `sitemap()` (`application/xml` with `xhtml:link` alternates for en, id, ms, x-default); register `GET robots.txt` and `GET sitemap.xml` outside the admin filter in `app/Config/Routes.php`
    - _Requirements: 11.14, 11.15_

  - [x] 19.4 Create the SEO columns migration and update `database/schema.sql`
    - Add a NEW migration (for example `2025-02-01-000001_AddSeoColumnsToSiteConfig.php`) adding `site_name_{en,id,ms}` VARCHAR(150) NULL, `meta_description_{en,id,ms}` VARCHAR(320) NULL, `meta_keywords_{en,id,ms}` VARCHAR(255) NULL, and `og_image` VARCHAR(500) NULL, each guarded by `fieldExists()`; `down()` removes only those columns
    - Update `database/schema.sql` with the same columns, then apply with `php spark migrate` against `127.0.0.1:3307` / `tour_travel` and run it a second time to confirm idempotence
    - _Requirements: 11.17, 11.21_

  - [x] 19.5 Add SEO settings persistence and validation
    - Extend `SiteConfigModel::$allowedFields` with the SEO columns; add pure `ContentValidator::validateSeoSettings()` (en required when any SEO value is provided, descriptions ≤320, keywords ≤255, site name ≤150); merge it into `ApiV1\SiteConfigController::prepareSave`, run `ImageUploadHandler` on the OG image upload and store it, and return 422 naming each invalid field while retaining the previous row; update `ApiSiteConfigController::apiSave` to forward the OG image as multipart
    - _Requirements: 11.17, 11.18, 11.19, 11.20_

  - [x] 19.6 Write SEO head view tests
    - Render the landing view and assert title, description, canonical, three hreflang plus `x-default`, `og:*`, `twitter:*`, a JSON-LD block that decodes as JSON, FAQPage only with FAQ items, and keywords tag only when configured
    - _Requirements: 11.1, 11.3, 11.4, 11.6, 11.8, 11.9, 11.10_

- [x] 20. Implement analytics backend
  - [x] 20.1 Create the `visitor_logs` and `site_events` migrations and update `database/schema.sql`
    - Add two NEW idempotent migrations (`createTable(..., true)`): `visitor_logs` (id, visit_date DATE, visitor_hash CHAR(64), page VARCHAR(255), lang CHAR(2), device_type ENUM('desktop','mobile','tablet','bot'), referrer_host VARCHAR(255) NULL, is_bot TINYINT(1) DEFAULT 0, created_at DATETIME; indexes on `visit_date` and `visitor_hash`) and `site_events` (id, event_type, ref_id NULL, ref_label NULL, lang, visitor_hash, created_at; indexes on `(event_type, created_at)` and `ref_id`)
    - Update `database/schema.sql` with `CREATE TABLE IF NOT EXISTS` for both tables, apply with `php spark migrate` on `127.0.0.1:3307` / `tour_travel`, and run it again to confirm idempotence
    - _Requirements: 12.1, 12.10, 12.27_

  - [x] 20.2 Create `VisitorLogModel` and `SiteEventModel`
    - Declare `$table`, `$primaryKey`, `$allowedFields`, `$useTimestamps = false`, `$returnType = 'array'`
    - _Requirements: 12.1, 12.10_

  - [x] 20.3 Implement `DeviceDetector`
    - Create `app/Libraries/DeviceDetector.php` (pure) with `classify()` returning exactly one of `desktop|mobile|tablet|bot` for any string or null (empty UA → bot) and `isBot()`
    - _Requirements: 12.5, 12.6, 12.28_

  - [x] 20.4 Implement `VisitorHasher`
    - Create `app/Libraries/VisitorHasher.php` (pure) with `hash($ip, $userAgent, $date, $salt)` returning the SHA-256 hex of the delimited inputs; the raw IP never appears in the output
    - _Requirements: 12.2, 12.3, 12.4, 12.28_

  - [x] 20.5 Implement `DashboardStats`
    - Create `app/Libraries/DashboardStats.php` (pure) with `fillDateRange`, `distribution` (largest-remainder rounding, sums to 100.0 or all 0.0), `excludeBots`, `visitorTotals` (distinct `(visit_date, visitor_hash)` pairs for 7/30 days), and `topN`
    - _Requirements: 12.16, 12.17, 12.19, 12.20, 12.21, 12.28_

  - [x] 20.6 Implement `VisitorTracker` and integrate it into `LandingController`
    - Create `app/Libraries/VisitorTracker.php` with `shouldTrack($path)` (excludes `/admin`, `/api`, `/signin`, `/dashboard`, `/track`, `/robots.txt`, `/sitemap.xml`, asset paths), referrer host parsing (null when absent or same host), and `record()` that stores `is_bot` per `DeviceDetector` and wraps everything in `try/catch (\Throwable)`; derive the salt from `encryption.key`; log failures without the IP; call it from `LandingController::index()` after the view data is built
    - _Requirements: 12.1, 12.2, 12.3, 12.6, 12.7, 12.8_

  - [x] 20.7 Implement the booking click endpoint
    - Add pure `ContentValidator::validateBookingClick()` (positive numeric `fleet_id`, `fleet_name` 1–200 chars, `lang` ∈ en/id/ms); create `App\Controllers\TrackController extends PublicBaseController` with `booking()` that returns 204 for bots, 403 for cross-origin requests, 422 for invalid payloads, 429 (`Retry-After: 60`) after 10 requests per `visitor_hash` in 60 seconds using the CI cache, and otherwise inserts a `booking_click` row into `site_events`; register `POST track/booking` outside the admin and CSRF filters
    - _Requirements: 12.9, 12.10, 12.11, 12.12, 12.13_

  - [x] 20.8 Implement the `api/v1` dashboard summary
    - Create `ApiV1\DashboardController::summary` using `VisitorLogModel`, `SiteEventModel`, the content models, `AdminUserModel` (latest `last_login_at`), and `SiteConfigModel` (WhatsApp configured flag) with non-bot SQL aggregates and `DashboardStats`; return the `{status,message,data,total}` envelope with the documented `data` shape; handle missing tables by returning a zero-shaped summary and a message naming the migration; register `GET api/v1/dashboard/summary` with the `internalapi` filter
    - _Requirements: 12.14, 12.16, 12.17, 12.18, 12.20, 12.21, 12.22, 12.23, 12.24, 12.25_

  - [x] 20.9 Implement the dashboard proxy and admin controller wiring
    - Create `ApiDashboardController extends Controller` (declared `protected $service`) with `apiSummary(): array` and `apiSummaryJson()` using null-safe fallbacks to a zero-shaped summary and a non-200 status; update `Admin\DashboardController` to instantiate only `ApiDashboardController` and pass the summary to the view; register `GET admin/api/dashboard/summary` inside the existing admin API group
    - _Requirements: 12.14, 12.15, 12.25_

- [x] 21. Build the dashboard UI
  - [x] 21.1 Rewrite `admin/dashboard.php`
    - Render Bootstrap cards (total visitors, unique today/7d/30d, page views, booking clicks total/7d/30d, content published/draft counts), a latest-5-news list group, last admin login, top referrers and top-5 fleet tables, canvases for the daily, language, and device charts, a JSON data block for the charts, an empty-state message when there is no data, and a warning card linking to Site Configuration when WhatsApp is not configured; load only local Chart.js
    - _Requirements: 12.16, 12.17, 12.18, 12.20, 12.21, 12.22, 12.23, 12.24, 12.25, 12.26_

  - [x] 21.2 Implement `dashboard.js`
    - Create `public/assets/js/dashboard.js` that reads the JSON data block and creates Chart.js line/bar (daily page views and unique visitors for 30 days), doughnut (language), and doughnut/bar (device) charts with percentages in tooltips; show an empty-state message instead of a chart when all values are 0
    - _Requirements: 12.17, 12.18, 12.25, 12.26_

  - [x] 21.3 Write dashboard view tests
    - Render `admin/dashboard` with an all-zero summary and a populated summary; assert cards, canvases, empty-state messages, the WhatsApp warning card visibility, and that only `/assets/vendor/chartjs/` is referenced
    - _Requirements: 12.16, 12.24, 12.25, 12.26_

- [x] 22. Write property and example tests for the new logic
  - [x] 22.1 Write property test for date-range zero-fill
    - **Property 17: Date-range zero-fill completeness and ordering**
    - **Validates: Requirements 12.17, 12.25**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 17`; file `tests/unit/DashboardStatsPropertyTest.php`

  - [x] 22.2 Write property test for distribution percentages
    - **Property 18: Distribution percentages sum to 100 or 0**
    - **Validates: Requirements 12.18, 12.19**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 18`; same file as 22.1

  - [x] 22.3 Write property test for the visitor hash
    - **Property 19: Visitor hash privacy and stability**
    - **Validates: Requirements 12.2, 12.3, 12.4**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 19`; file `tests/unit/VisitorHasherPropertyTest.php`; IP generator yields ≥7 characters

  - [x] 22.4 Write property test for device classification
    - **Property 20: Device classification totality**
    - **Validates: Requirements 12.5**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 20`; file `tests/unit/DeviceDetectorPropertyTest.php`

  - [x] 22.5 Write property test for bot exclusion
    - **Property 21: Bots never count as visitors**
    - **Validates: Requirements 12.6**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 21`; same file as 22.1

  - [x] 22.6 Write property test for SEO builder output
    - **Property 22: SEO builder required output and description limit**
    - **Validates: Requirements 11.1, 11.2, 11.6, 11.7**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 22`; file `tests/unit/SeoMetaBuilderPropertyTest.php`

  - [x] 22.7 Write property test for canonical and hreflang URLs
    - **Property 23: Canonical and hreflang URLs are well-formed and complete**
    - **Validates: Requirements 11.4, 11.5**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 23`; same file as 22.6

  - [x] 22.8 Write property test for JSON-LD
    - **Property 24: JSON-LD validity and FAQ completeness**
    - **Validates: Requirements 11.8, 11.9, 11.10**
    - Eris, ≥100 iterations, tagged `// Feature: travel-rental-website, Property 24`; same file as 22.6

  - [x] 22.9 Write example tests for the analytics libraries and validators
    - `DeviceDetector` samples (Chrome desktop, iPhone, iPad, Android phone, Googlebot, curl, empty UA); `VisitorTracker::shouldTrack` path table, referrer host parsing, and a throwing model stub proving the failure is swallowed; `validateBookingClick` and `validateSeoSettings` boundaries; `DashboardStats::topN` ordering and ties and `visitorTotals` for a fixed row set
    - _Requirements: 11.18, 12.5, 12.7, 12.8, 12.11, 12.16, 12.20, 12.21_

  - [x] 22.10 Write feature and database tests
    - Apply the three new migrations twice against `tour_travel` and assert the tables, columns, and indexes; test `GET /robots.txt`, `GET /sitemap.xml` (well-formed XML with alternates), `POST /track/booking` (valid insert, invalid 422, 11th request 429, bot no insert), and `GET api/v1/dashboard/summary` (401 without session or signature, 200 with the internal proxy signature)
    - _Requirements: 11.14, 11.15, 11.21, 12.10, 12.11, 12.12, 12.13, 12.14, 12.27_

  - [x] 22.11 Extend architectural conformance tests
    - Assert `ApiDashboardController extends Controller`, `Admin\DashboardController extends BaseController`, constructor-assigned properties are declared, and `DeviceDetector`, `VisitorHasher`, `DashboardStats`, and `SeoMetaBuilder` contain no references to `db_connect`, `service(`, `Services::`, or `curl_`
    - _Requirements: 12.15, 12.28_

- [x] 23. Final checkpoint - Ensure all tests pass
  - Run the full `vendor\bin\phpunit` suite, `php -l` on every new and changed PHP file, and `php spark routes` to confirm `robots.txt`, `sitemap.xml`, `track/booking`, `admin/api/dashboard/summary`, and `api/v1/dashboard/summary` are registered; confirm `php spark migrate` is idempotent on `127.0.0.1:3307` / `tour_travel`
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional (tests) and can be skipped for a faster MVP, but every one of Properties 1–16 has a dedicated property-test sub-task here.
- Each task references specific requirements (granular clauses) for traceability.
- Checkpoints (tasks 5, 9, 15) ensure incremental validation at natural boundaries.
- Property tests use Eris at ≥100 iterations and carry the `// Feature: travel-rental-website, Property {n}` tag; property-test placement sits next to the code it validates so failures surface early.
- Property-to-task map: P1→2.2, P2→2.3, P3→12.3, P4→4.9, P5→4.10, P6→4.5, P7→4.6, P8→4.12, P9→4.11, P10→10.5, P11→7.7, P12→7.8, P13→4.7, P14→4.8, P15→3.2, P16→3.3.
- Extension tasks 16–23 cover Requirements 10–12. Property-to-task map: P17→22.1, P18→22.2, P19→22.3, P20→22.4, P21→22.5, P22→22.6, P23→22.7, P24→22.8. Task 23 is the final checkpoint for the extension; run the suite after each top-level group (16–21) before moving on.
- UI responsiveness, accessibility, and color contrast (Req 10.3–10.5, 10.19, 10.20) need manual verification at 320, 768, and 1280 px and with a keyboard; automated tests only check markup attributes.
- Non-PBT acceptance criteria (auth/session timing, performance budgets, CRUD contracts, empty-state rendering, architectural structure) are covered by the example and integration tasks (7.9, 8.2, 10.6, 12.4, 14.1–14.3).

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1", "3.1", "4.1", "4.2", "4.3", "4.4"] },
    { "id": 2, "tasks": ["2.2", "2.3", "3.2", "3.3", "4.5", "4.6", "4.7", "4.8", "4.9", "4.10", "4.11", "4.12", "6.1"] },
    { "id": 3, "tasks": ["6.2"] },
    { "id": 4, "tasks": ["7.1", "7.2", "7.3", "7.4", "7.5", "7.6", "8.1"] },
    { "id": 5, "tasks": ["7.7", "7.8", "7.9", "8.2"] },
    { "id": 6, "tasks": ["10.1", "10.2", "10.3", "10.4"] },
    { "id": 7, "tasks": ["10.5", "10.6", "11.1"] },
    { "id": 8, "tasks": ["11.2", "11.3", "12.1"] },
    { "id": 9, "tasks": ["11.4", "12.2", "12.3", "12.4"] },
    { "id": 10, "tasks": ["13.1"] },
    { "id": 11, "tasks": ["13.2"] },
    { "id": 12, "tasks": ["14.1", "14.2", "14.3"] },
    { "id": 13, "tasks": ["16.1"] },
    { "id": 14, "tasks": ["16.2", "16.3", "19.1", "19.4", "20.3", "20.4", "20.5"] },
    { "id": 15, "tasks": ["16.4", "17.1", "18.1", "18.2", "18.5", "19.3", "19.5", "20.1", "22.1", "22.3", "22.4", "22.6"] },
    { "id": 16, "tasks": ["17.2", "18.3", "19.2", "20.2", "22.2", "22.7"] },
    { "id": 17, "tasks": ["17.3", "17.4", "18.4", "19.6", "20.6", "20.7", "22.5", "22.8"] },
    { "id": 18, "tasks": ["20.8", "21.2", "22.9"] },
    { "id": 19, "tasks": ["20.9", "21.1"] },
    { "id": 20, "tasks": ["21.3", "22.10", "22.11"] }
  ]
}
```
