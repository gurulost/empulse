Empulse (Workfitdx) — Code Audit and Upgrade Plan

Summary
- Purpose: Multi-tenant platform to onboard companies, manage staff (manager/chief/teamlead/employee), import/export users, run the in-app pulse survey, and handle paid subscriptions via Stripe. Includes a super-admin (Workfit Admin) area to view companies and subscriptions.
- Stack: Laravel 9 (PHP 8.0), Sanctum, Socialite, Cashier, Mail (Brevo API), Maatwebsite Excel, Intervention Image, Bootstrap/Vite.

Critical Issues to Fix (Short-Term)
- Authorization: Fix WorkfitAdmin middleware (incorrect boolean), ensure only is_admin=1 can access Workfit Admin. DONE
- Payment gating: Fix type-strict check in Payment middleware so only unpaid company owners see payment page. DONE
- Social auth: Facebook controller catch block typo prevents error handling. DONE
- Typos: AdminController addDepartment uses misspelled property (comoany_title). DONE
- API imports: ApiController referenced a non-existent namespace (APP\Models). Removed. DONE
- Wire subscriptions: Add routes for Stripe plan listing and subscription purchase using Cashier. DONE

High-Risk / Security Improvements (Next)
- Stripe/Cashier: Remove legacy PaymentController::stripe cURL flow; rely on Cashier + webhooks for source of truth. Persist subscription status per-company based on Stripe events.
- Password reset: Replace custom reset endpoints and ad-hoc token creation with Laravel’s built-in password broker and token storage.
- Email sending: Centralize Brevo calls using Notifications/Mailables; add retry/backoff and error handling. Do not expose error details to users.
- File uploads: Store avatars in storage/app/public using Laravel’s Storage facade; avoid direct unlink and path concatenation. Validate mime/size server-side (already partially present).
- Policies/Gates: Add per-company authorization policies. Ensure admin endpoints filter by authenticated user’s company and block cross-tenant actions.
- Middleware consistency: Add explicit role/permission middleware for manager/chief/teamlead; avoid “admin != employee” as the only guard.
- Env handling: Ensure sensitive keys are only referenced via config files; avoid leaking into views. Add config caching in production.

Data Model & Integrity
- Foreign keys: Add FK constraints between users.company_id, company_worker.company_id -> companies.id; cascade on delete where appropriate.
- Unique constraints: Validate email updates for uniqueness, avoid conflicts across tenants; define indexes for frequent lookups (email, company_id, role).
- Remove AUTO_INCREMENT resets: Remove DB::statement("ALTER TABLE ... AUTO_INCREMENT"); not safe across engines and unnecessary.
- Normalize roles: Replace magic integers with enum (backed enum in PHP 8.1+) or constants; introduce a roles table if needed.

Code Quality & Structure
- Extract services: Move business logic from models/controllers (emails, user creation, Qualtrics filtering) into service classes. Add Jobs for mail and heavy processing.
- Validation: Use Form Request classes for update/create endpoints; centralize rules.
- Naming consistency: Rename ContuctUs -> ContactUs across files; standardize controller actions and Blade file naming.
- Remove globals: Refactor User model methods using global variables into pure methods returning data.
- Eloquent relations: Define relations (User hasOne Company; Company hasMany Users/Workers), replace manual DB::table calls where possible.
- Logging: Replace var_dump and ad-hoc echoes with structured logs; ensure no debug in production.

Internal Survey Engine
- DONE: Qualtrics dependency removed; surveys, assignments, and analytics now live inside Laravel.
- Next: expand builder UI, support branching/anonymous modes, and expose export/report APIs for ops teams.

Subscription & Billing
- Plans: Seed Plan records and expose plan pages securely. Drive entitlement from Stripe subscription state via webhooks.
- Entitlements: Store company-level subscription state in a dedicated table (e.g., subscriptions) or map Stripe customer to company.
- UI: Link the “Payment” CTA to plans index; hide plan purchase for non-owners.

Testing
- Feature tests: Replace hard-coded user IDs with factories. Add tests for middleware (WorkfitAdmin, Payment), subscription flow, and user management (CRUD within tenant boundaries).
- Unit tests: Cover services (mail sending, imports, Qualtrics filtering, user creation/update flows).

Frontend & Assets
- Vite: Remove public/js_old and public/css_old; consolidate under resources with Vite.
- Vite build artifacts: `public/build` is tracked; run `npm run build` after JS/CSS changes so the manifest + hashed assets stay in sync (prevents blank pages when loading production assets).
- Accessibility: Audit Blade templates for a11y and responsiveness; remove inline styles.

Roadmap (Phased)
1) Security & correctness: Policies, middleware fixes, password reset, Stripe via Cashier + webhooks
2) Data integrity: FKs, indexes, role enum/consts, remove AUTO_INCREMENT resets
3) Code refactor: Services/Jobs/Notifications, Form Requests, Eloquent relations
4) UX & cleanup: Vite asset unification, consistent naming, admin UX improvements
5) Tests: Factories, feature tests for critical flows; CI setup

Notes
- Keep migrations backward-compatible; add data backfills where needed.
- Gate risky behavior behind feature flags during rollout (e.g., new subscription source-of-truth).
