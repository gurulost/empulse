# Empulse (Workfitdx)

Overview
- Multi-tenant Laravel 9 app to onboard companies, manage employees by role (manager/chief/teamlead/employee), integrate Qualtrics survey data, and handle paid subscriptions via Stripe.

Key Features
- Company and department management, CSV/XLSX import/export
- Role-based dashboards and a Workfit Admin area
- Qualtrics responses fetch (via API)
- Stripe subscriptions powered by Laravel Cashier

Quick Start
- PHP >= 8.0, Composer, Node 16+
- cp .env.example .env and configure DB, Redis, Mail (Brevo), Socialite, Stripe
- composer install && php artisan key:generate && php artisan migrate --seed
- npm install && npm run dev

Stripe Subscriptions
- Manage plans in DB table `plans` (name, slug, stripe_plan, price)
- Routes: `/plans` to browse; purchase via secure Cashier flow

Documentation
- See `docs/AUDIT.md` for the current audit, priorities, and roadmap.
