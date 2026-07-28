# Empulse

Empulse is WorkFit's company-level workplace diagnostic and continuous-listening platform. It helps leaders understand the gap between what employees need from work and what they experience now, see the culture surrounding those gaps, choose where to intervene, and measure change across repeated survey waves.

The product is not intended to be a generic form builder. Its center is a versioned WorkFit instrument, reliable company and cohort analytics, and a recurring loop from evidence to leadership action.

## Read first

1. [`docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md`](docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md) — product promise, customers, value loop, monetization direction, principles, and open owner decisions.
2. [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — current system, data flow, actors, code map, formulas, runtime, and engineering invariants.
3. [`AGENTS.md`](AGENTS.md) — current phase context and chronological implementation record.
4. [`docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md`](docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md) — production configuration, processes, checks, and rollback.

## The product loop

1. A manager creates a company and adds the initial roster.
2. WorkFit admin keeps the shared survey instrument versioned, records a change summary, reviews and approves the exact content hash, then publishes the live version.
3. The manager launches a full baseline wave.
4. Employees receive secure assignments, autosave progress, and submit responses.
5. Empulse calculates work-content gaps, indicator satisfaction, team culture, impact, and a composite temperature.
6. Leaders filter and compare results across departments, teams, and waves.
7. The company acts, then uses recurring Pulse waves to measure movement.

The first completed response is the workflow activation milestone: it proves the end-to-end path works. It is not enough for a reliable company diagnosis. Company results require at least 5 valid respondents; subgroup results require at least 7 and complementary suppression. The retained value is trustworthy diagnosis, a recorded leadership response, and governed wave-over-wave learning.

## Current capabilities

- multi-tenant companies and organization rosters;
- Manager, Chief, Team Lead, Employee, and WorkFit Admin experiences;
- manual roster management plus governed CSV import with encrypted staging, stable external IDs, row-level reconciliation, cross-tenant conflict detection, explicit confirmation, atomic commit, and expiring account invitations;
- versioned internal survey engine with pages, sections, item types, scale presets, options, and display logic;
- token-scoped survey assignments, autosave, validation, and normalized responses;
- full and recurring survey waves with role-based audiences;
- scheduled dispatch, invitations, cadence enforcement, logs, and recovery;
- company, department, team, and wave analytics;
- trend and comparison reports;
- WorkFit-admin survey builder with draft/review/approval/publication states, customer activation report, sanitized audit explorer, grant-scoped advisor queue, and privacy-safe action-loop value reporting;
- company-owned Stripe subscriptions, explicit billing administrators, immutable catalog/entitlement history, entitlement/usage enforcement, and replay-safe webhook reconciliation;
- versioned respondent privacy acknowledgment and audited data-subject/retention workflows;
- immutable findings, evidence-labeled intervention options, leadership actions, communications, measurement plans, governed Pulse variants, and non-causal outcomes;
- customer-approved WorkFit advisory with scoped queues and append-only customer-shared versus WorkFit-internal notes.

Qualtrics has been replaced for the active survey and analytics path.

## Architecture at a glance

- Backend: Laravel 12 on PHP 8.2+
- UI: Blade, Bootstrap 5, and conditionally mounted Vue 3 components
- Assets: Vite
- Billing: Stripe and Laravel Cashier
- Background work: Laravel queue and scheduler
- Charts: Chart.js, vue-chartjs, and dashboard Vue components
- Production baseline: PostgreSQL 16 with separate web, worker, and scheduler processes

See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) before changing survey, analytics, billing, role, or tenant behavior.

## Local setup

Requirements:

- PHP 8.2+
- Composer
- Node 22+
- a configured database

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan app:install --seed-if-empty
npm install
npm run dev
```

The local example uses PostgreSQL plus database-backed queue, cache, and sessions. Configure mail, Socialite, and Stripe values before exercising those integrations.

Build production assets with:

```bash
npm run build
```

`public/build` is generated output and should be produced by the release process rather than edited by hand.

## Import the WorkFit instrument

The canonical source file in this repository is [`survey_instrument.json`](survey_instrument.json).

```bash
php artisan survey:import survey_instrument.json \
  --activate \
  --approved-by=<workfit-admin-user-id> \
  --change-summary="Describe what changed and why this exact version is being published."
```

The import is transactional and fails closed unless the canonical content passes publication lint and metric-compatibility checks. `--activate` additionally requires a named active WorkFit survey administrator and a change summary; it records review, approval, content hash, publisher, and platform audit evidence before retiring the prior live version. For ordinary content changes, use `/admin/builder` and its draft → review → approved → published workflow.

Do not rename question IDs or change analytics mappings without reviewing [`config/survey.php`](config/survey.php), the product methodology, and analytics tests.

## Governed roster import

Company managers can open Team Management and choose **Import CSV**. Required headers are:

```text
external_id,name,email,role
```

Optional headers are `department`, `supervisor_external_id`, and `status`. Departments must exist before upload; status is explicitly `active` or `inactive`. Omitting a person from a file never deactivates them.

The workflow is deliberately two-phase:

1. upload into encrypted staging and generate a row-level create/update/reactivate/deactivate/unchanged preview;
2. review all rows, acknowledge the preview, and commit once with a short-lived confirmation token.

Invalid headers, duplicate identities, cross-company email conflicts, unresolved supervisors, unknown departments, self-deactivation, and manager deactivation fail closed before any roster mutation. A changed roster invalidates the preview. Successful commits update the compatibility roster and effective-dated organization history in one transaction, audit the import, and queue account-only invitations. Large files are parsed by the worker; the maximum is 1 MB and 1,000 rows.

Detailed preview/result rows expire after 30 days through the hash-confirmed retention workflow; the import summary and audit evidence remain. Failed or interrupted account-invitation deliveries are found by the scheduled `account:invitations:recover --execute` command and requeued with the original idempotency key.

## Demo data

`php artisan migrate:fresh --seed` is the deterministic browser/CI fixture: it imports the canonical 62-item WorkFit baseline, creates two hash-pinned cycles, and gives the role accounts a complete respondent journey. Use it only in an isolated disposable database. `DatabaseSeeder` refuses to run when `APP_ENV=production`.

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

The command enforces wave state, open/due windows, billing status, audience roles, and per-assignment cadence. It dispatches `ProcessSurveyWave`, which creates assignments and queues invitations. Dispatch moves a full or one-time manual Pulse wave to `active`; it does not mark collection complete. Completion occurs only after all frozen assignments complete or the due date passes.

A complete runtime needs both:

```bash
php artisan queue:work --tries=3 --backoff=10 --timeout=120
php artisan schedule:run
```

The production scheduler should invoke `php artisan schedule:run` every minute. It runs wave scheduling plus account- and survey-invitation recovery. Both recovery commands are report-only unless the scheduler supplies `--execute`; they requeue only stale eligible records and rely on stable delivery idempotency. Without a worker and scheduler, recurring measurement and queued account setup or survey delivery can stall.

Full waves use manual cadence. Recurring and action-linked follow-up waves require the company-level `recurring_waves` entitlement. Governed Pulse variants limit questions to the predeclared metric, freeze their audience, cap reminders, and enforce respondent rest/rolling-frequency rules.

Each frozen wave assignment is queued once even if `ProcessSurveyWave` is replayed. Invitation/reminder retries reuse one encrypted survey URL and one provider idempotency UUID. `SendSurveyAssignmentInvitation` is unique per assignment during the 15-minute interruption window, and `survey:invitations:recover --execute` safely requeues stale queued/sending/failed delivery work. Automatic resend stops before the provider deduplication window expires and requires manual provider review afterward.

## Billing

The company is Cashier's billable customer. Only users with an active `organization_billing_admins` appointment can browse and operate `/plans` and `/account/billing`; a manager or chief role alone does not grant billing access. The active owner can appoint or revoke additional billing administrators, while owner transfer is acceptance-gated and preserves continuity. Stripe webhooks reconcile the canonical organization entitlement, and legacy return routes cannot grant access. The plan/feature/limit contract lives in [`config/billing.php`](config/billing.php). Public prices and a trial remain disabled/unconfirmed until the product owner approves them.

After approved Stripe price IDs and integer prices are configured, materialize the checkout projection with `php artisan billing:sync-catalog`. The command fails closed on incomplete plans. Active-respondent limits are reserved atomically with dispatch.

WorkFit advisors do not have global customer action access. A company administrator grants and revokes named, purpose-bound access from the leadership action workspace. The advisor queue shows only organizations with a currently active grant. Customer-shared workspace notes are visible to authorized customer users and the approved advisor; WorkFit-internal notes are visible only to an approved advisor. Both note types are append-only, and their creation is audited without copying note bodies into the audit log.

## Quality gates

```bash
composer test
php artisan readiness:checklist
vendor/bin/pint --test
composer analyse
composer audit --no-interaction
npm run lint
npm run test:unit
npm run build
npm audit --audit-level=high
npm run test:e2e
```

CI runs migrations and the backend suite against PostgreSQL 16, checklist-structure validation, formatting, scoped critical-path static analysis, dependency audits, a full-history secret-policy check, frontend lint/unit/build gates, readiness, and Playwright role/failure/respondent/accessibility journeys with real web and worker processes. The history policy requires the unignored baseline to equal the three exact owner-approved revoked-credential fingerprints, then proves that the fingerprint-scoped scan passes and an unrecognized synthetic finding fails. Current-source and proposed-change scans remain strict. Immediately before an accountable release sign-off, run `php artisan readiness:checklist --require-signoff`; it fails unless every checklist item is either verified or explicitly accepted with an owner and rationale.

Analytics query changes also require the EXPLAIN workflow in [`docs/ANALYTICS_EXPLAIN_CHECKLIST.md`](docs/ANALYTICS_EXPLAIN_CHECKLIST.md).
For a clean-checkout, PostgreSQL-backed 500-respondent analytics and integrity rehearsal, use `php artisan readiness:capacity-rehearsal {company_id} --wave=wave:{wave_id}` and follow [`docs/CAPACITY_AND_PERFORMANCE_TEST_PLAN.md`](docs/CAPACITY_AND_PERFORMANCE_TEST_PLAN.md). That bounded command is not provider staging or production sign-off.

## Production runtime contract

No production environment is currently selected or deployed. Repository-level production work targets the provider-neutral contract in [`docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md`](docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md):

- PostgreSQL is the only production database;
- `APP_DEBUG=false`, HTTPS, secure session cookies, strict survey validation, durable queue/cache/session drivers, mail, and Stripe secrets are mandatory;
- migrations run as a one-time release action, never in the image build or web startup;
- web, worker, and scheduler are distinct processes;
- `/api/healthz` is liveness and `/api/readyz` checks database/runtime-table readiness;
- `AVATAR_DISK` points to persistent/shared storage (or avatar upload is disabled); an ephemeral release filesystem is not durable customer storage;
- `php artisan app:production-check` fails closed on unsafe configuration.

The checked-in Docker and Procfile definitions express the same process contract. Replit configuration is development-only and intentionally has no production deployment block.

## Documentation map

- [`docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md`](docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md) — authoritative working north star
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — authoritative current-source map
- [`docs/AUDIT_BACKLOG_TRACEABILITY_2026-07-27.md`](docs/AUDIT_BACKLOG_TRACEABILITY_2026-07-27.md) — disposition and evidence for every owner-supplied audit ticket
- [`docs/EMPULSE_PRODUCTION_READINESS_CHECKLIST.md`](docs/EMPULSE_PRODUCTION_READINESS_CHECKLIST.md) — current release gates, evidence, and accountable residual risks
- [`docs/ONBOARDING_FLUENCY_AUDIT_2026-03-06.md`](docs/ONBOARDING_FLUENCY_AUDIT_2026-03-06.md) — activation-path reasoning
- [`docs/ANALYTICS_EXPLAIN_CHECKLIST.md`](docs/ANALYTICS_EXPLAIN_CHECKLIST.md) — production query-plan review
- [`docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md`](docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md) — deployment and operations
- [`docs/OBSERVABILITY_AND_SERVICE_LEVELS.md`](docs/OBSERVABILITY_AND_SERVICE_LEVELS.md) — launch SLOs, signals, alerts, and incident minimum
- [`docs/BACKUP_RESTORE_AND_DISASTER_RECOVERY.md`](docs/BACKUP_RESTORE_AND_DISASTER_RECOVERY.md) — RPO/RTO and restore drill
- [`docs/RELEASE_AND_ROLLBACK_POLICY.md`](docs/RELEASE_AND_ROLLBACK_POLICY.md) — evidence packet, canary rollout, and rollback policy
- [`docs/GIT_HISTORY_SECRET_REMEDIATION_RUNBOOK.md`](docs/GIT_HISTORY_SECRET_REMEDIATION_RUNBOOK.md) — owner-approved preserved-history accepted risk and future rewrite contingency
- [`docs/CAPACITY_AND_PERFORMANCE_TEST_PLAN.md`](docs/CAPACITY_AND_PERFORMANCE_TEST_PLAN.md) — staging data profiles, workloads, and budgets
- [`docs/METHODOLOGY_AND_CLAIMS_DOSSIER.md`](docs/METHODOLOGY_AND_CLAIMS_DOSSIER.md) — metric intent, interpretation, and claim limits
- [`docs/RESPONDENT_DATA_PROMISE.md`](docs/RESPONDENT_DATA_PROMISE.md) — working privacy promise and owner decisions
- [`docs/RELEASE_CANDIDATE_EVIDENCE_2026-07-27.md`](docs/RELEASE_CANDIDATE_EVIDENCE_2026-07-27.md) — current proven checks and external launch gates
- [`docs/archive/README.md`](docs/archive/README.md) — explicitly non-authoritative historical audits and handoffs
