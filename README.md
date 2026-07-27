# Empulse

Empulse is WorkFit's company-level workplace diagnostic and continuous-listening platform. It helps leaders understand the gap between what employees need from work and what they experience now, see the culture surrounding those gaps, choose where to intervene, and measure change across repeated survey waves.

The product is not intended to be a generic form builder. Its center is a versioned WorkFit instrument, reliable company and cohort analytics, and a recurring loop from evidence to leadership action.

## Read first

1. [`docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md`](docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md) — product promise, customers, value loop, monetization direction, principles, and open owner decisions.
2. [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — current system, data flow, actors, code map, formulas, runtime, and engineering invariants.
3. [`AGENTS.md`](AGENTS.md) — current phase context and chronological implementation record.
4. [`docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md`](docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md) — production configuration, processes, checks, and rollback.

## The product loop

1. A manager creates a company and adds or imports the roster.
2. WorkFit admin keeps the shared survey instrument versioned and publishes the live version.
3. The manager launches a full baseline wave.
4. Employees receive secure assignments, autosave progress, and submit responses.
5. Empulse calculates work-content gaps, indicator satisfaction, team culture, impact, and a composite temperature.
6. Leaders filter and compare results across departments, teams, and waves.
7. The company acts, then uses recurring Pulse waves to measure movement.

The first completed response is the workflow activation milestone: it proves the end-to-end path works. It is not enough for a reliable company diagnosis. A minimum-sample and cohort-suppression policy still needs to be defined and implemented. The retained value is trustworthy diagnosis and wave-over-wave learning that changes management decisions.

## Current capabilities

- multi-tenant companies and organization rosters;
- Manager, Chief, Team Lead, Employee, and WorkFit Admin experiences;
- CSV/XLSX roster import and export;
- versioned internal survey engine with pages, sections, item types, scale presets, options, and display logic;
- token-scoped survey assignments, autosave, validation, and normalized responses;
- full and recurring survey waves with role-based audiences;
- scheduled dispatch, invitations, cadence enforcement, logs, and recovery;
- company, department, team, and wave analytics;
- trend and comparison reports;
- WorkFit-admin survey builder, customer activation report, and onboarding action queue;
- Stripe subscriptions through Laravel Cashier.

Qualtrics has been replaced for the active survey and analytics path.

## Architecture at a glance

- Backend: Laravel 11 on PHP 8.2+
- UI: Blade, Bootstrap 5, and conditionally mounted Vue 3 components
- Assets: Vite
- Billing: Stripe and Laravel Cashier
- Background work: Laravel queue and scheduler
- Charts: Chart.js, vue-chartjs, and dashboard Vue components

See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) before changing survey, analytics, billing, role, or tenant behavior.

## Local setup

Requirements:

- PHP 8.2+
- Composer
- Node 20+
- a configured database

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan app:install --seed-if-empty
npm install
npm run dev
```

Configure database, cache, queue, mail, Socialite, and Stripe values in the environment before exercising those integrations.

Build production assets with:

```bash
npm run build
```

`public/build` is generated output and should be produced by the release process rather than edited by hand.

## Import the WorkFit instrument

The canonical source file in this repository is [`survey_instrument.json`](survey_instrument.json).

```bash
php artisan survey:import survey_instrument.json --activate
```

The import is transactional. Activation deactivates the prior version and makes the new normalized version live globally.

Do not rename question IDs or change analytics mappings without reviewing [`config/survey.php`](config/survey.php), the product methodology, and analytics tests.

## Demo data

Create a realistic company with departments, team leads, 100+ employees, several survey waves, and analytics-ready answers:

```bash
php artisan demo:seed --import-instrument --employees=120 --months=6 --force
```

Demo password: `password`

- `admin@workfit.com` — WorkFit Admin
- `manager@acme.com` — Manager
- `chief@acme.com` — Chief
- `lead@acme.com` — Team Lead
- `employee1@acme.com` — Employee
- `employee2@acme.com` — Employee
- `employee3@acme.com` — Employee

Never seed demo data into a production customer database.

## Survey automation

Managers operate waves at `/survey-waves`.

The scheduler runs:

```bash
php artisan survey:waves:schedule
```

The command enforces wave state, open/due windows, billing status, audience roles, and per-assignment cadence. It dispatches `ProcessSurveyWave`, which creates assignments and queues invitations.

A complete runtime needs both:

```bash
php artisan queue:work --tries=1
php artisan schedule:run
```

The production scheduler should invoke `php artisan schedule:run` every minute. Without a worker and scheduler, recurring measurement stalls.

Full waves use manual cadence. Weekly, monthly, and quarterly drip cadences require a tariff listed in `config('survey.automation.drip_tariffs')`; the current Pulse entitlement is tariff `1`.

## Billing

Plans live in `plans` and point to Stripe Price IDs. Managers browse `/plans` and manage the active subscription at `/account/billing`.

Cashier and Stripe webhook events are billing truth. Legacy payment return routes must not grant entitlements.

The current code has a $100/month seeded Business Plan and a Starter/Pulse tariff distinction, but the final packaging, trial policy, prices, respondent limits, and service inclusions are open product-owner decisions. Do not hard-code new commercial behavior without reconciling [`docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md`](docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md), plan data, Stripe configuration, customer copy, and tests.

## Quality gates

```bash
php artisan test
npm run lint
npm run build
npm run test:e2e
```

CI runs the backend suite, frontend lint/build, and Playwright role smoke tests against a seeded SQLite application.

Analytics query changes also require the EXPLAIN workflow in [`docs/ANALYTICS_EXPLAIN_CHECKLIST.md`](docs/ANALYTICS_EXPLAIN_CHECKLIST.md).

## Documentation map

- [`docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md`](docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md) — authoritative working north star
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — authoritative current-source map
- [`docs/AUDIT.md`](docs/AUDIT.md) — historical code audit and hardening roadmap
- [`docs/ONBOARDING_FLUENCY_AUDIT_2026-03-06.md`](docs/ONBOARDING_FLUENCY_AUDIT_2026-03-06.md) — activation-path reasoning
- [`docs/ANALYTICS_EXPLAIN_CHECKLIST.md`](docs/ANALYTICS_EXPLAIN_CHECKLIST.md) — production query-plan review
- [`docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md`](docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md) — deployment and operations
