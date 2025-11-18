# Empulse (Workfitdx)

Overview
- Multi-tenant Laravel 9 app to onboard companies, manage employees by role (manager/chief/teamlead/employee), integrate Qualtrics survey data, and handle paid subscriptions via Stripe.

Key Features
- Company and department management, CSV/XLSX import/export
- Role-based dashboards and a Workfit Admin area
- Built-in survey engine (Qualtrics fully replaced)
- Stripe subscriptions powered by Laravel Cashier

Quick Start
- PHP >= 8.0, Composer, Node 16+
- cp .env.example .env and configure DB, Redis, Mail (Brevo), Socialite, Stripe
- composer install && php artisan key:generate && php artisan migrate --seed
- npm install && npm run dev

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
