# Production Deployment Runbook

## Purpose
- Define the provider-neutral contract for a future Empulse production deployment.
- Keep repository readiness separate from provider selection and live deployment evidence.

Empulse has not been deployed or sold. This runbook is a release contract, not evidence that any production environment exists.

## Prerequisites
- A selected production provider, release owner, database, and domain.
- PHP 8.2+, Composer, Node 22+, and either:
  - a Herokuish/buildpack platform that provides `heroku-php-apache2` for the checked-in `Procfile`, or
  - a container runtime that builds the checked-in `Dockerfile`.
- Stripe production keys and webhook secret.
- Brevo API key for invitation delivery.
- Queue, session, and cache backends configured for production.
- Persistent/shared avatar storage configured through a Laravel filesystem disk, or avatar upload intentionally disabled until that storage exists.

## Critical Release Notes
- `public/build` is generated during deployment and is no longer intended to be committed.
- The app now depends on both a queue worker and the Laravel scheduler for survey wave dispatch and invitation delivery.
- Image builds and web startup must never run migrations or seed data.
- Replit settings are development-only; there is no Replit production deployment declaration.
- PostgreSQL 16 is the production database baseline.
- Deterministic and demo seeders are test/demo tooling only; `DatabaseSeeder` refuses to run in production.

## Required Environment Configuration
- App:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://<production-host>`
  - `APP_KEY=<unique production key>`
  - `TRUSTED_PROXIES=<explicit comma-separated proxy IPs/CIDRs, or empty when direct>`
  - `AUDIT_HASH_KEY=<separate 32+ character audit-chain secret>`
  - `BREVO_WEBHOOK_TOKEN=<32+ character bearer token configured on the Brevo webhook>`
- Database:
  - `DB_CONNECTION=pgsql`
  - `DB_HOST`
  - `DB_PORT`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
- Queue / cache / session:
  - `QUEUE_CONNECTION`
  - `CACHE_STORE=database` or another durable shared store
  - `SESSION_DRIVER=database` or another durable shared store
  - `SESSION_SECURE_COOKIE=true`
- Storage:
  - `AVATAR_DISK=<persistent/shared Laravel filesystem disk>`
  - if using the local `public` disk in a non-ephemeral environment, run `php artisan storage:link`; do not use an ephemeral release filesystem for customer avatars
- Billing:
  - `STRIPE_KEY`
  - `STRIPE_SECRET`
  - `STRIPE_WEBHOOK_SECRET`
  - `STRIPE_PRICE_STARTER`
  - `STRIPE_PRICE_PULSE`
  - `BILLING_PRICE_STARTER_CENTS`
  - `BILLING_PRICE_PULSE_CENTS`
- Mail:
  - `BREVO_KEY`
  - `MAIL_MAILER`
  - `MAIL_FROM_ADDRESS`

Use `.env.production.example` as the variable inventory. Before migration or process start, run:

```bash
php artisan app:production-check
```

## Deployment Order
1. Put the release on the target revision.
2. Install backend dependencies:
   - `composer install --no-dev --prefer-dist --optimize-autoloader`
3. Install frontend dependencies and build assets:
   - `npm ci`
   - `npm run build`
4. Run `php artisan app:production-check`.
5. Ensure writable directories exist:
   - `storage/`
   - `bootstrap/cache/`
6. Verify `AVATAR_DISK` can write, read, serve, and delete a disposable normalized image from every web instance.
7. Run database migrations as a one-time release action:
   - `php artisan migrate --force`
8. Reconcile the checkout projection with the approved environment catalog:
   - `php artisan billing:sync-catalog`
9. Cache application state:
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
10. Start or restart independently supervised runtime processes:
   - web
   - queue worker
   - scheduler

## Process Requirements
- Web:
  - If deploying with the checked-in `Procfile`, run the Apache/PHP buildpack runtime: `heroku-php-apache2 public/`
  - If your platform does not provide that command, deploy with the checked-in Docker image or the platform's native Apache/nginx + PHP runtime.
- Queue worker:
  - `php artisan queue:work --tries=3 --backoff=10 --sleep=1 --timeout=120 --max-time=3600`
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
- Prove the selected release SHA and run health checks:
  - `/api/healthz` returns `{"status":"live"}`
- `/api/readyz` returns `{"status":"ready",...}`
  - in production this also requires fresh scheduler and worker heartbeats; allow the first scheduled minute after a new environment starts
  - app loads at `/`
  - login page renders
  - manager can reach `/home`, `/survey-waves`, and `/account/billing`
  - workfit admin can reach `/admin` and `/admin/builder`
  - employee can reach `/employee`
- Verify billing:
  - plans page renders
  - billing center renders
  - a role-only manager without a billing-admin appointment is denied
  - the active owner can appoint and revoke an additional billing administrator and both changes appear in the audit stream
  - webhook updates subscription state without errors
- Verify survey operations:
  - create a wave
  - dispatch a wave
  - confirm assignments are created
  - confirm queue completion leaves a one-time/full collection `active`, not `completed`, until every assignment completes or the due date passes
  - confirm invitation jobs leave the queue and `invite_status` updates
  - force one transient provider retry and confirm the same encrypted survey URL and Brevo idempotency UUID are reused within the automatic retry window
  - confirm automatic resend stops for manual provider review after 25 minutes
  - replay the completed `ProcessSurveyWave` job and confirm assignment dispatch counts and queued invitation totals do not increase
  - run `php artisan survey:invitations:recover` and confirm it is report-only
  - simulate stale queued, sending, and failed survey delivery states; run `php artisan survey:invitations:recover --execute` and confirm one unique recovery job per eligible assignment
- Verify roster account invitations:
  - commit a staged roster preview and confirm account-invitation jobs leave the queue
  - run `php artisan account:invitations:recover` and confirm it is report-only
  - simulate an eligible failed or interrupted delivery, run `php artisan account:invitations:recover --execute`, and confirm one idempotent recovery job is queued
- Verify roster-import retention in a disposable staging fixture:
  - run the retention command in dry-run mode and review the target list and hash
  - execute only with the exact reviewed hash
  - confirm expired detailed rows and confirmation tokens are removed while summary and audit evidence remain
- Verify customer-scoped advisory:
  - a customer administrator grants a named advisor access with a purpose and expiry
  - that advisor can open only the granted customer workspace
  - revocation immediately removes access and produces an audit event
- Verify reports/dashboard:
  - no-data tenant shows onboarding states instead of blank UI
  - seeded/demo tenant shows populated analytics and reports
  - a complete finding → action → measurement → outcome chain appears in the WorkFit-admin value-loop report without answer content or employee identity

## Non-production demo environment

Demo data is forbidden in the production customer database. In an isolated non-production environment only:

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
  - `composer test`
- Frontend lint:
  - `npm run lint`
- Frontend build:
  - `npm run build`
- Role smoke tests:
  - `npm run test:e2e`

## Ownership Checklist
- Product owner confirms the approved launch account, offer, prices, respondent promise, and claims.
- Engineering confirms migrations, env vars, worker, and scheduler are live.
- Billing owner confirms Stripe webhook delivery is healthy.
- Ops confirms logs, queue depth, and scheduler execution after release.
- Release owner attaches the evidence required by [`RELEASE_AND_ROLLBACK_POLICY.md`](RELEASE_AND_ROLLBACK_POLICY.md).
- Ops completes the isolated restore drill in [`BACKUP_RESTORE_AND_DISASTER_RECOVERY.md`](BACKUP_RESTORE_AND_DISASTER_RECOVERY.md).
- Monitoring is configured against [`OBSERVABILITY_AND_SERVICE_LEVELS.md`](OBSERVABILITY_AND_SERVICE_LEVELS.md).
