# Production Deployment Runbook

## Purpose
- Define the provider-neutral contract for a future Empulse production deployment.
- Keep repository readiness separate from provider selection and live deployment evidence.

Empulse has not been deployed or sold. This runbook is a release contract, not evidence that any production environment exists.

## Prerequisites
- A selected production provider, release owner, database, and domain.
- Stable PHP at least 8.2.35 / 8.3.36 / 8.4.27 / 8.5.12 on the corresponding maintained branch, Composer, Node 22.23.4 (or patched 24.22.1+), and either:
  - a Herokuish/buildpack platform that provides `heroku-php-apache2` for the checked-in `Procfile`, or
  - a container runtime that builds the checked-in `Dockerfile`.
- Stripe production keys and webhook secret.
- Brevo API key for invitation delivery.
- Queue, session, and cache backends configured for production.
- Persistent/shared avatar storage configured through a Laravel filesystem disk, or avatar upload intentionally disabled until that storage exists.

## Critical Release Notes
- Read the [October 22 security refresh](SECURITY_AUDIT_2026-10-22.md) and [subsequent CI/runtime qualification](SECURITY_CI_FOLLOWUP_2026-10-22.md) first. A digest pin is identity evidence, not a clean vulnerability assessment. The owner-authorized Alpine/nginx/FPM replacement passes actual-image scans and journeys at code `952c5be`, run `39900324906`; this is CI qualification, not a published/deployed artifact. Rebuild, rescan and verify the exact provider artifact before promotion.
- Builds use SHA-512-verified npm 11.21.1 in an empty build-specific prefix (`node scripts/install-build-npm.mjs <absolute-empty-prefix>`), not the base image's older bundled npm or a global workstation upgrade. Node security floors run before asset/test scripts. Build and scan both frontend toolchain and final runtime images; the application lockfile audit does not cover global build-tool dependencies.
- Use `PLAYWRIGHT_CHANNEL=chrome npm run test:e2e` with the approved current stable Chrome for the browser gate, recording the actual browser version. Updating Playwright alone does not refresh every browser security patch.
- Run `php scripts/check-php-runtime.php` before application bootstrap. Inspect the actual Apache/FPM executable/version as well; a patched CLI does not prove the serving SAPI or old workers were replaced.
- Rebuild with `docker build --pull --no-cache` in trusted CI, scan the final image with a fresh distribution-aware database, and record the final digest and native-package dispositions. Never promote an old vulnerable image as rollback.
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
  - `APP_RELEASE_SHA=<exact lowercase 40-character Git SHA in the built artifact>`
  - `DEPLOYMENT_ENVIRONMENT_ID=<stable non-secret environment identifier>`
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
  - `REQUIRE_PROCESS_HEARTBEATS=true`
  - `HEARTBEAT_MAX_AGE_SECONDS=180` (allowed production range: 60–600)
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
php scripts/check-php-runtime.php
php artisan app:production-check
```

## Deployment Order
1. Put the release on the target revision.
2. Install backend dependencies:
   - `composer install --no-dev --prefer-dist --optimize-autoloader`
3. Install frontend dependencies and build assets:
   - `npm ci --ignore-scripts`
   - `npm run build`
4. Run `php scripts/check-php-runtime.php` and `php artisan app:production-check`.
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
- The replacement Compose web port maps loopback `${APP_PORT:-8080}` to container port 8080, not 80. The image's default `web` command starts non-root nginx and PHP-FPM under Tini after fail-closed production validation. Put an approved HTTPS reverse proxy on the same host, or explicitly configure the selected provider's private ingress; do not expose the HTTP origin/database directly. Trust only that proxy's exact addresses. FPM port 9000 is loopback-only inside the web container and must not be published.
- Build the project PostgreSQL image through Compose (or `docker build -f docker/postgres/Dockerfile -t empulse-postgres:local .`). It retains PostgreSQL 16.15 but starts as `postgres` and removes unused gosu. For an existing volume, confirm compatible UID/ownership and backup/restore first; this entrypoint intentionally cannot chown arbitrary data as root. Do not point PostgreSQL 16 at PostgreSQL 14 data.
- Alpine and Debian may assign different numeric IDs to `www-data`. Before reusing uploads or application-cache volumes, run `docker run --rm --entrypoint id <qualified-image> -u` and `-g`, compare existing ownership, back up the data and approve a narrowly scoped migration. Do not assume the old Debian volume is writable, use world-writable permissions, or auto-chown shared data. Fresh volumes initialize from the image's owned directories; this work did not alter any existing volume.
- nginx/FPM logs omit URI/query/header/address values; startup/process errors, redacted application errors, and HTTP status/timing remain available. Apply the same bearer-URL policy to the selected edge/proxy/CDN; repository logging alone cannot protect provider logs.
- The Compose `uploads` volume now backs `storage/app/public`, matching Laravel's public disk. Before using an existing volume, inspect its layout and plan a non-destructive data migration; do not blindly mount a legacy `public/upload` volume and assume avatars moved. Run `php artisan storage:link` for this disk. New installs have no existing customer data to migrate.
- Roster staging persists the CSV encrypted in the database (`roster_imports.source_csv`), then clears it after parsing; workers need the same database and `APP_KEY`, not a shared plaintext upload volume. Verify synthetic cross-process parsing and retention. Keep temporary uploads/private application storage out of public mounts.
- Web:
  - If deploying with the checked-in `Procfile`, run the Apache/PHP buildpack runtime: `heroku-php-apache2 public/`
  - If your platform does not provide that command, use the checked-in rootless nginx/PHP-FPM Docker image (`web`, internal port 8080), or separately qualify its native server equivalent. The legacy buildpack Procfile is not proof that a provider supplies patched PHP/native libraries.
- Queue worker:
  - In Compose, this process runs as `www-data`; verify shared storage permissions rather than granting root.
  - `php artisan queue:work --tries=3 --backoff=10 --sleep=1 --timeout=120 --max-time=3600`
- Scheduler:
  - In Compose, this process also runs as `www-data`.
  - `php artisan schedule:work`
  - If your platform does not support a long-running scheduler process, run cron with:
    - `* * * * * php artisan schedule:run`

## Stripe Webhook Setup
- Point Stripe to:
  - `/stripe/webhook`
- Verify the signing secret matches `STRIPE_WEBHOOK_SECRET`.
- Prove absent configuration returns 503, an unsigned/incorrectly signed request returns 403, and a real Stripe test-mode delivery succeeds exactly once. The controller now fails closed even when Cashier's signature middleware was not installed.
- Confirm delivery for at least:
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`

## Post-Deploy Verification
- From outside the deployed runtime, prove the selected release SHA, environment identity, HTTPS security headers, and health surfaces:

```bash
php artisan app:verify-deployment \
  https://<target-host> \
  <exact-40-character-release-sha> \
  <deployment-environment-id> \
  --json
```

  - `/api/healthz` returns `status=live`, the exact release SHA and environment ID in JSON, and the same SHA in `X-Empulse-Release`
  - `/api/readyz` returns `status=ready`, the same identities, connected database, available runtime tables, and fresh scheduler and worker heartbeats
  - `/login` returns the same release header plus HSTS, CSP, content-type, frame, and referrer protections
  - in production this also requires fresh scheduler and worker heartbeats; allow the first scheduled minute after a new environment starts
  - a redirect, stale process, absent header, wrong SHA/environment, non-HTTPS origin, or unavailable surface fails the verifier
  - the verifier always reports `production_signoff=false`; it covers served identity and a narrow runtime surface only
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
  - run `php artisan roster:imports:recover` and confirm it is report-only
  - simulate a stale encrypted `parsing` import, run `php artisan roster:imports:recover --execute`, and confirm one unique parse job is queued
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
