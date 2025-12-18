# Empulse (Workfitdx)

Overview
- Multi-tenant Laravel (v11) app to onboard companies, manage employees by role (manager/chief/teamlead/employee), run the in-app pulse survey, and handle paid subscriptions via Stripe.

Key Features
- Company and department management, CSV/XLSX import/export
- Role-based dashboards and a Workfit Admin area
- Built-in survey engine (Qualtrics fully replaced)
- Stripe subscriptions powered by Laravel Cashier

Quick Start
- PHP >= 8.2, Composer, Node 20+
- cp .env.example .env and configure DB, Redis, Mail (Brevo), Socialite, Stripe
- composer install && php artisan key:generate
- php artisan app:install --seed-if-empty
- npm install
  - dev: npm run dev (runs Vite HMR)
  - prod: npm run build (writes to public/build)
- Keep a queue worker (`php artisan queue:work --tries=1`) and the scheduler (`* * * * * php artisan schedule:run >> storage/logs/schedule.log 2>&1`) running so survey automations and drip cadences execute without manual CLI intervention.

Seeded Logins (when using `--seed` or `--seed-if-empty`)
- Company manager: `manager@acme.com` / `password`
- Workfit admin: `admin@workfit.com` / `password`

Survey Waves & Automation
- Admins can create waves at `/survey-waves` (full send or drip). Status, cadence, logs, and per-assignment progress are surfaced directly in the UI with pause/resume + manual run buttons.
- The scheduler command `php artisan survey:waves:schedule` (scheduled in `routes/console.php`) enforces drip cadences per assignment, respects billing status, and logs every action to `survey_wave_logs` for auditability.
- Drip cadences are gated to the Pulse plan (tariff `1`). The mapping lives in `config/survey.php` under the `automation` key; downgrade or past-due billing automatically pauses waves until the subscription is active again.
- Feature tests (`tests/Feature/SurveyWaveTest.php`, `tests/Feature/DashboardAnalyticsTest.php`) cover wave creation, cadence gating, billing pauses, and analytics filters so future changes remain safe.

Internal Surveys
- Run `php artisan migrate` to create the placeholder survey schema/assignments.
- Employees receive secure links (via email or `/surveys/manage`) served from `/survey/{token}`.
- Dashboard data now reads from the internal survey responses; no Qualtrics configuration is required.
- Import the master instrument via `php artisan survey:import storage/app/instruments/org_culture_work_content_v1.json --activate`.

Stripe Subscriptions
- Manage plans in DB table `plans` (name, slug, stripe_plan, price)
- Routes: `/plans` to browse; purchase via secure Cashier flow

Documentation
- See `docs/AUDIT.md` for the current audit, priorities, and roadmap.
