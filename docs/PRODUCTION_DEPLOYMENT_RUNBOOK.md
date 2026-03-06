# Production Deployment Runbook

## Purpose
- Deploy Empulse safely to production with the current billing, survey-wave, and analytics stack.
- Ensure the runtime matches the app's requirements: migrated DB, built frontend assets, queue worker, and scheduler.

## Prerequisites
- Access to the production environment, database, and deployment platform.
- PHP 8.2+, Composer, Node 20+, and either:
  - a Herokuish/buildpack platform that provides `heroku-php-apache2` for the checked-in [Procfile](/Users/davedixon/Downloads/empulse%20code/Procfile), or
  - a container/native web runtime that matches the checked-in [Dockerfile](/Users/davedixon/Downloads/empulse%20code/Dockerfile).
- Stripe production keys and webhook secret.
- Brevo API key for invitation delivery.
- Queue, session, and cache backends configured for production.

## Critical Release Notes
- `public/build` is generated during deployment and is no longer intended to be committed.
- The app now depends on both a queue worker and the Laravel scheduler for survey wave dispatch and invitation delivery.
- Cashier compatibility now requires the subscription schema alignment migration:
  - [2026_03_05_010000_align_subscriptions_table_with_cashier.php](/Users/davedixon/Downloads/empulse%20code/database/migrations/2026_03_05_010000_align_subscriptions_table_with_cashier.php)

## Required Environment Configuration
- App:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=<production-url>`
- Database:
  - `DB_CONNECTION`
  - `DB_HOST`
  - `DB_PORT`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
- Queue / cache / session:
  - `QUEUE_CONNECTION`
  - `CACHE_DRIVER`
  - `SESSION_DRIVER`
  - Redis settings if using Redis
- Billing:
  - `STRIPE_KEY`
  - `STRIPE_SECRET`
  - `STRIPE_WEBHOOK_SECRET`
- Mail:
  - `BREVO_KEY`
  - `MAIL_MAILER`
  - From-address config as required

## Deployment Order
1. Put the release on the target revision.
2. Install backend dependencies:
   - `composer install --no-dev --prefer-dist --optimize-autoloader`
3. Install frontend dependencies and build assets:
   - `npm ci`
   - `npm run build`
4. Ensure writable directories exist:
   - `storage/`
   - `bootstrap/cache/`
5. Run database migrations:
   - `php artisan migrate --force`
6. Cache application state:
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
7. Restart runtime processes:
   - web
   - queue worker
   - scheduler

## Process Requirements
- Web:
  - If deploying with the checked-in `Procfile`, run the Apache/PHP buildpack runtime: `heroku-php-apache2 public/`
  - If your platform does not provide that command, deploy with the checked-in Docker image or the platform's native Apache/nginx + PHP runtime.
- Queue worker:
  - `php artisan queue:work --tries=1 --sleep=1 --timeout=120`
- Scheduler:
  - `php artisan schedule:work`
  - If your platform does not support a long-running scheduler process, run cron with:
    - `* * * * * php artisan schedule:run`

## Stripe Webhook Setup
- Point Stripe to:
  - `/stripe/webhook`
- Verify the signing secret matches `STRIPE_WEBHOOK_SECRET`.
- Confirm delivery for at least:
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`

## Post-Deploy Verification
- Run the basic health checks:
  - app loads at `/`
  - login page renders
  - manager can reach `/home`, `/survey-waves`, and `/account/billing`
  - workfit admin can reach `/admin` and `/admin/builder`
  - employee can reach `/employee`
- Verify billing:
  - plans page renders
  - billing center renders
  - webhook updates subscription state without errors
- Verify survey operations:
  - create a wave
  - dispatch a wave
  - confirm assignments are created
  - confirm invitation jobs leave the queue and `invite_status` updates
- Verify reports/dashboard:
  - no-data tenant shows onboarding states instead of blank UI
  - seeded/demo tenant shows populated analytics and reports

## Demo Environment Prep
- Seed a demo tenant if needed:
  - `php artisan demo:seed --import-instrument --employees=120 --months=6 --force`
- Confirm demo credentials work for:
  - `admin@workfit.com`
  - `manager@acme.com`
  - `chief@acme.com`
  - `lead@acme.com`
  - `employee1@acme.com`

## Rollback Guidance
- If the release fails before migrations:
  - roll back application code only
- If the release fails after migrations:
  - prefer forward-fix unless a tested DB rollback exists
  - restore the last known-good application release
  - restart worker and scheduler after rollback
- If survey dispatch or email delivery stalls:
  - verify queue worker is running
  - verify scheduler is running
  - inspect failed jobs and app logs

## Operational Commands
- Backend tests:
  - `php artisan test`
- Frontend lint:
  - `npm run lint`
- Frontend build:
  - `npm run build`
- Role smoke tests:
  - `npm run test:e2e`

## Ownership Checklist
- Product owner confirms demo accounts and data are ready.
- Engineering confirms migrations, env vars, worker, and scheduler are live.
- Billing owner confirms Stripe webhook delivery is healthy.
- Ops confirms logs, queue depth, and scheduler execution after release.
