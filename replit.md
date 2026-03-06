# Empulse (Workfitdx) - Replit Setup

## Overview
Empulse (Workfitdx) is a multi-tenant Laravel 11 application with a Vue.js frontend designed for comprehensive employee lifecycle management. Its core purpose is to streamline company onboarding, employee management, and facilitate employee feedback through an integrated survey engine. The platform also includes a robust subscription billing system powered by Stripe. The project aims to provide a modern, scalable solution for businesses to manage their workforce and gather insights effectively.

## Production Login Fix (March 2026)

### Root Cause
PHP's built-in web server (`php artisan serve`) spawns worker processes for each request. These worker processes do **not** inherit environment variables from the parent process via `getenv()` or `$_ENV`, even though the parent has them in `/proc/PID/environ`. This meant `DB_HOST`, `DB_PASSWORD`, `CACHE_STORE`, `SESSION_DRIVER`, etc. all resolved to their defaults (localhost, file, etc.) on every web request, causing POST /login to 500.

### Fixes Applied
1. **`public/index.php` parent-process env shim**: Reads the parent process's `/proc/<ppid>/environ` at the very start of each request if `DB_HOST` is missing. This copies all missing env vars into the current worker process via `putenv()` before Laravel bootstraps, making Dotenv and config() work correctly in production without any change to `.replit` or the build/run commands.

2. **`config/cache.php` env key fix**: The file read `env('CACHE_DRIVER', 'file')` but `.replit` sets `CACHE_STORE=database`. Changed to `env('CACHE_STORE', env('CACHE_DRIVER', 'database'))` so the database cache is used.

3. **`LoginController.php` throttle hardening**: Used trait aliasing (`hasTooManyLoginAttempts as traitHasTooManyLoginAttempts`) to wrap all rate-limiter calls in try-catch. Also added a full `login()` override that captures any unexpected exception to the `cache` table for production diagnostics.

4. **`.env` file** (gitignored, dev only): Written with `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `APP_KEY`, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` as belt-and-suspenders for the dev workflow.

5. **`tests/Feature/LoginDatabaseCacheTest.php`**: Added test suite covering login with database cache, including the case where cache tables are missing.

### Verified Working
- `GET /login` → 200 ✓
- `POST /login` wrong password → 302 back to /login ✓
- `POST /login` correct credentials → 302 to /home ✓
- All tested both with and without DB secrets in `.env` (parent-process shim handles the no-.env case)

## Recent Changes (December 2024)

### Critical Fixes Applied
1. **PlanController Multi-Tenancy Fix**: Fixed hardcoded `company === 1` check that blocked billing for all companies except ID 1. Now uses `company_id !== null` for proper multi-tenant support.
2. **Password Reset Flow**: Fixed broken password reset - corrected endpoints to use Laravel's default `/password/email` and `/password/reset`, fixed success detection logic.
3. **SurveyBuilder.vue API Endpoints**: Fixed all `/builder/*` endpoints to correctly use `/admin/builder/*` prefix.
4. **OAuth Security Hardening**: Fixed Google/Facebook login to properly link social IDs to existing accounts, added null email validation.
5. **PaymentController**: Added missing Auth facade, moved Stripe API calls from view to controller, added failure logging.
6. **WorkfitAdminController**: Added missing methods (getCompanyList, getUsersList, getCompany) referenced by routes.
7. **TeamController Authorization**: Restored authorization check for CompanyWorker updates.
8. **Legacy JS Fixes**: Fixed undefined `apiDomain` variable in home.js, created symlink for /js directory.
9. **Contact Form**: Fixed ContuctUs.blade.php to handle both authenticated and guest users.
10. **Legacy Routes Restoration (Dec 2024)**: Added missing routes for /users/delete, /users/list, /users/import, /departments POST/list/delete that legacy JS files depend on. Routes use auth-only middleware with policy-based authorization in controller.
11. **User Model Fixes**: Fixed undefined `$this->companyWorkerTable` property, eliminated global variable declarations (`global $users`, `global $departments`), added null safety to `user_role()` method.
12. **BillingController Safety**: Added try/catch and null checks for Stripe configuration to prevent errors when Stripe is not configured.
13. **SurveyBuilderController Access**: Changed middleware from 'admin' to 'workfit_admin' for proper access control.
14. **JavaScript Fixes**: Fixed implicit global `parent` variable in usersPagination.js, removed infinite `requestAnimationFrame` loop, fixed null checks in profile.js using proper jQuery `.length` checks.

### Code Quality Refactoring (December 2024)
15. **"Contuct" Typo Fix**: Renamed all misspelled files to proper "Contact" naming:
    - `ContuctUsController.php` → `ContactUsController.php`
    - `ContuctUs.blade.php` → `contact-us.blade.php`
    - `ContuctUs.php` (Mail) → `ContactUs.php`
    - Routes changed from `/contuctUs` to `/contact` (with redirects for backwards compatibility)
    - CSS class references updated from `.contuctUs-window` to `.contact-us-window`
16. **EmailService Consolidation**: Enhanced `app/Services/EmailService.php` with centralized email methods:
    - `sendContactForm()` - Contact form submissions
    - `sendPasswordReset()` - Password reset emails
    - `sendSurveyInvitation()` - Survey invites
    - `sendToAdmin()` - Admin notifications
    - Updated `UserController.sendLetter()` to use EmailService
17. **SocialAuthService**: Created `app/Services/SocialAuthService.php` to eliminate duplicate social login logic:
    - `handleGoogleLogin()` and `handleFacebookLogin()` methods
    - Updated `SocialController` and `FacebookController` to use the service
18. **Theme.js Performance Fix**: Replaced infinite `requestAnimationFrame` loop with proper event listeners and MutationObserver (auto-disconnects after 5 seconds)
19. **Auth Facade Imports (Dec 2024)**: Added missing Auth facade imports to all middleware files (Admin, Chief, Manager, Teamlead, WorkfitAdmin) - critical fix that would cause 500 errors on all authenticated routes.
20. **Middleware Role Modernization**: Updated Chief, Manager, Teamlead middleware to use integer role field instead of deprecated boolean properties (chief, manager, teamlead).
21. **Legacy Companies.js Deprecation**: Marked js/roles/companies.js as deprecated with warnings - orphaned routes (/companies/delete, /companies/delete/manager) replaced by Vue AdminDashboard.
22. **UserController Import Fix**: Added missing ModelNotFoundException import for proper error handling.
23. **User Model SurveyService Fix**: Added missing SurveyService import for surveyLink() method.
24. **Vue Mounting Architecture Fix (Dec 2024)**: Completely rewrote `resources/js/app.js` to fix blank page issue. The old approach mounted a single empty Vue app to `#app` which wiped all Blade-rendered HTML. New approach uses `mountByTagName()` and `mountById()` helper functions to mount Vue components individually to their custom elements (`<app-sidebar>`, `<analytics-dashboard>`, etc.) without clearing the surrounding Blade content.
25. **Session Configuration for Replit (Dec 2024)**: Configured database sessions with `same_site=lax` and `secure=false` for compatibility with Replit's iframe preview. Sessions now persist correctly after login.

### Known Issues / Technical Debt
- **CRITICAL SECURITY**: Brevo API key should be rotated quarterly (rotation pending)
- **Architecture**: Mixed frontend (legacy jQuery + Vue.js) - migration to Vue recommended
- **Queue Workers**: Survey wave automation requires queue workers (`php artisan queue:work`)

## User Preferences
- Not yet configured

## System Architecture

### Core Technologies
- **Backend**: Laravel 11 (PHP 8.2)
- **Frontend**: Vue.js 3 with Vite
- **Database**: PostgreSQL (production), SQLite (development)
- **Styling**: Bootstrap 5, SASS
- **Charting**: Chart.js, vue-chartjs
- **Payments**: Stripe via Laravel Cashier

### UI/UX Decisions
- **Design System**: Modern Bootstrap 5 for consistent styling.
- **Authentication**: Redesigned authentication pages (login, register, password reset, email verification) with a consistent split-panel layout and purple gradient design. Includes Google OAuth integration.
- **Iconography**: Bootstrap Icons CDN for professional icon support.

### Technical Implementations & Design Choices
- **Multi-tenancy**: Designed as a multi-tenant application.
- **Dynamic URL Handling**: `AppServiceProvider` dynamically sets the application URL based on the request, ensuring correct asset paths and URL generation across different environments (local, Replit proxy, custom domain).
- **Asset Management**: Vite is used for frontend asset compilation. Production builds disable HMR and generate optimized, fingerprinted assets in `public/build/`.
- **Proxy Configuration**: `TrustProxies` middleware is configured to trust all proxies (`$proxies = '*'`) for compatibility with Replit's infrastructure.
- **Authorization**: Role-based access control (Admin, Manager, Chief, Team Lead, Employee) is implemented, with a `UserRole` enum for type-safe role management (though full integration is a known debt).
- **Survey System**: Features a built-in survey builder with various question types, wave-based deployment, automated drip cadences, and real-time analytics. Requires queue workers for automation.
- **User Management**: Includes CSV/XLSX import/export for bulk operations and a team member invitation system.
- **Billing**: Integration with Stripe via Laravel Cashier for subscription management.

### Deployment & Development Workflow
- **Development**: Laravel server runs on port 5000. Frontend assets require `npm run build` after changes. Database operations use `php artisan migrate` and `php artisan db:seed`.
- **Deployment**: Configured for autoscale deployment on Replit with a build step (`npm run build`) and a run command (`php artisan serve --host=0.0.0.0 --port=5000`).

## External Dependencies

- **Database**: Neon PostgreSQL (external, accessed via `DB_HOST` secret — Replit's local `helium` PostgreSQL is present in the environment but NOT used)
- **Payment Gateway**: Stripe (via Laravel Cashier)
- **Email Service**: Brevo (for transactional emails, e.g., password resets, survey invitations)
- **Social Authentication**: Google OAuth, Facebook OAuth
- **Frontend Libraries**: Vue.js 3, Chart.js, vue-chartjs, Bootstrap 5, Bootstrap Icons