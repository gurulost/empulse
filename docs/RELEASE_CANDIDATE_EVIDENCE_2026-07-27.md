# Empulse Release-Candidate Evidence — 2026-07-27

## Purpose

This handoff records what the production-readiness branch actually proves. It does not claim that Empulse is deployed, sold, or approved for customer use. No deployment provider has been selected and no live environment exists.

Current product, architecture, and release truth are separated from historical implementation records. Obsolete audits, completed checklists, and the February deploy handoff live under `docs/archive/` with an explicit warning that they are not deployment instructions or current sign-off evidence.

## Scope

Branch: `codex/production-readiness`

Base commit: `e166a6b9b26ae95584bb34b8fd7fa9410718f320`

The branch is a direct pre-deployment reset across:

- authorization, organization identity, invitations, historical cohort custody, and tamper-evident audit;
- survey publication, version pinning, expiring assignment access, mandatory validation, autosave concurrency, and atomic submission;
- delivery state, wave lifecycle, recurring Pulse governance, frozen audiences, reminders, and fatigue exclusions;
- respondent promise, privacy operations, retention/legal hold, sample and suppression policy, metric registry, reliability, and comparability;
- reliable findings, governed intervention guidance, leadership actions, communication, follow-up measurement, and non-causal outcome evaluation;
- company-owned billing, entitlements, Stripe reconciliation, usage, and billing-owner continuity;
- PostgreSQL/process topology, health checks, worker/scheduler heartbeats, CI, static analysis, dependency security, browser tests, accessibility automation, and operational runbooks.

The unsafe legacy roster importer remains removed. Its replacement uses encrypted CSV staging, stable company-scoped external identities, strict header/row/unit/supervisor validation, cross-tenant conflict detection, row-level reconciliation, an expiring confirmation token, stale-preview detection, atomic commit, tamper-evident audit, queued account invitations, scheduled delivery recovery with stable idempotency, and downloadable sanitized results. Missing rows never imply deactivation. Detailed rows expire after 30 days through the hash-confirmed, legal-hold-aware retention workflow while summary and audit evidence remain.

## Validation Completed

### Application and database

- `composer test` on SQLite: 184 tests, 928 assertions passed.
- Clean PostgreSQL 14 migration and canonical 62-item demo seed: passed.
- `composer test` on PostgreSQL 14: 184 tests, 928 assertions passed.
- Browser gate: all 14 role, roster-import authorization, route-failure, privacy-acknowledgment, required-answer, submission, completed-state, and automated accessibility journeys passed together against a fresh seeded application. The respondent journey answered all 62 canonical items and persisted exactly 62 answers. The earlier 13-journey set also passed with real web and worker processes on PostgreSQL locally and in GitHub CI.
- CI is configured to repeat the database and process journey on PostgreSQL 16 with real web and worker processes.

### Code and dependencies

- `vendor/bin/pint --test`: passed.
- `composer analyse`: passed for the release-critical authorization, audit, billing, survey-access, validation, and sample-policy paths with no baseline or ignored errors.
- `composer audit --no-interaction`: no security advisories.
- `npm audit --audit-level=high`: zero vulnerabilities.
- Dependency-license inventory: all PHP packages are permissive or usable under an available permissive license; the unused direct PHPMailer dependency was removed. Direct JavaScript dependencies are MIT/Apache-2.0, except the development-only axe Playwright adapter under MPL-2.0. No direct dependency has an unknown license.
- `npm run lint`: passed.
- `npm run test:unit`: 2 component tests passed.
- `npm run build`: passed with 179 modules transformed.
- Laravel configuration, route, and Blade view caches build and clear successfully.
- Gitleaks current-source scan: no findings after removal of the legacy attachment directory.
- Gitleaks full-history scan: three pre-existing findings remain—two generic-key matches in the removed attachment history and one old Sendinblue/Brevo token in a historical controller commit. The mail key must be revoked/rotated; shared-history rewriting requires explicit owner approval and is not represented as complete.
- The first committed-SHA GitHub run exposed an npm lock inconsistency before tests began. The lock was regenerated from a clean dependency graph and then passed `npm ci` with the exact npm 10.9.8 version used by that runner.
- The initial GitHub secret job fetched full history but the action’s push-event command scanned only `-1` commit. The workflow now retains the proposed-change scan and adds an explicit fail-closed `gitleaks git --log-opts="--all"` history scan; a one-commit green result is not accepted as full-history evidence.
- GitHub Actions run `30303624681` on committed candidate `d9725de151f49792088319871349ef59085ca516` passed the complete product job: PostgreSQL 16 migration/seed, 170 tests/782 assertions, Composer/npm audits, cache construction, Pint, static analysis, frontend lint, two component tests, the 177-module production build, real web/worker readiness, and all 13 Playwright role/failure/respondent/accessibility journeys. The overall workflow is red only because the explicit full-history job correctly detects the three historical findings; its proposed-change scan is green.
- A disposable mirror rehearsal removed the two obsolete affected paths across all 366 commits and four affected refs. After rewriting, the old tainted commits/blobs were unreachable, `git fsck` passed, and Gitleaks scanned the rewritten history with no findings. The release-candidate tree remained exactly `cf4a3a028e652770c81bf4c5ec1050f2af84906c`, with a matching recursive tree-listing SHA-256 of `a493d9cff743dff816962ce60c54ea46872428c34788ba1785396f7f3f8a5387`. No remote ref was changed. The owner-safe procedure is documented in `docs/GIT_HISTORY_SECRET_REMEDIATION_RUNBOOK.md`.

### Fresh-review blocker closure

The first independent fresh-context review found eleven release blockers. The branch now closes them with focused regression coverage:

- canonical-host generation, explicit proxy trust, and response security headers;
- metric-valid sample suppression and exact-cycle trend/action provenance;
- manual and governed-import roster updates that never rotate or disclose survey access;
- companyless social-login rejection;
- fail-closed billing catalog materialization and a unique plan slug;
- atomic active-respondent reservation with assignment dispatch;
- customer-approved, expiring/revocable WorkFit advisor grants;
- one encrypted survey URL and stable Brevo UUID idempotency key per delivery attempt, with automatic retries stopped before provider TTL expiry;
- canonical 62-item demo/browser execution instead of the three-item toy path;
- truthful pre-launch marketing and registration copy;
- removal of 32 unreferenced legacy attached artifacts containing stale deployment/source material.

A second independent fresh-context review found five additional analytics and governance issues. Its verification pass caught one narrower subgroup-threshold defect, which was fixed and re-reviewed. The final source closes the complete set with a dedicated five-test regression suite:

- default and wave-filtered dashboards select one exact latest immutable cycle rather than pooling recurring occurrences;
- opportunity gaps require complete current/ideal respondent pairs and calculate both means from that paired population;
- historical dashboard, trend, comparison, scatter, impact, and reliability calculations resolve and verify the cycle's stored registry definition instead of current runtime configuration;
- metric registry version labels are derived from the content hash, so changed items, transforms, weights, scales, or reporting policy cannot silently reuse a version;
- chiefs and team leads are constrained by frozen organization-unit/reporting-line IDs in analytics and reports, action visibility follows the same cohort scope, and request filters cannot override that scope;
- hierarchy-scoped trends preserve the subgroup context through sample and invited/completion assessment, so five or six scoped respondents cannot clear the subgroup N≥7 contract;
- the pre-validation weighted ratio is labeled `WorkFit Indicator (pre-validation)` throughout customer-facing reports and is not represented as engagement.

The independent reviewer confirmed no P0/P1 remains in this late review scope. After the final roster-import hardening, SQLite and PostgreSQL each pass 184 tests/928 assertions; static analysis, formatting, lint, frontend unit/build gates, and all 14 browser/accessibility journeys pass.

### Accessibility

- Automated axe WCAG 2 A/AA and 2.1 A/AA checks pass with no serious or critical violations on:
  - public home and login;
  - manager operating loop and action workspace;
  - employee dashboard;
  - respondent data-promise entry.
- The gate found and drove remediation of global footer, page subtitle, table header, status badge, and public dashboard contrast failures.

### Backup and integrity drill

A PostgreSQL custom-format backup was restored into a separate empty database.

Source and restored counts matched:

| Record family | Source | Restored |
| --- | ---: | ---: |
| Companies | 1 | 1 |
| Users | 9 | 9 |
| Assignments | 10 | 10 |
| Responses | 8 | 8 |
| Answers | 496 | 496 |
| Audit events | 17 | 17 |

`php artisan audit:verify` passed for the restored platform stream (6 events) and company stream (11 events).

The newest governed-roster migration was rolled back and reapplied successfully against an isolated disposable database. Provider-native backup/PITR and full application rollback still require the selected staging environment.

### Local capacity smoke

The checked-in k6 readiness/login profile ran for three minutes against the local PostgreSQL-backed web process:

- 20 maximum virtual users;
- 7,504 iterations and 22,512 HTTP requests;
- 45,024/45,024 checks passed;
- 0 failed requests;
- request p95 182.82 ms and p99 191.15 ms.

This does not replace the 500-respondent provider-staging, queue-age, analytics, mail-sandbox, worker-recovery, or shared-cache/session exercises in the capacity plan.

## Evidence Boundaries

The repository is a strong release candidate, but customer launch is not yet approved. These gates require external state or accountable owner decisions:

1. The historical Sendinblue/Brevo credential must be revoked/rotated and the owner must explicitly authorize the rehearsed coordinated Git-history purge in `docs/GIT_HISTORY_SECRET_REMEDIATION_RUNBOOK.md`. GitHub CI must then pass on the rewritten committed SHA, including PostgreSQL 16, full-history Gitleaks, and all browser/accessibility specs.
2. A hosting, mail, observability, and backup provider must be selected and configured; staging must prove web, worker, scheduler, HTTPS, canonical URL, delivery webhooks, readiness, alerts, and rollback.
3. Stripe test mode must prove checkout, webhook replay/out-of-order handling, dunning, grace, cancellation, reactivation, and portal behavior with the approved catalog and prices.
4. Brevo or the selected provider must prove domain authentication, invitation/reminder delivery, bounce/complaint handling, suppression, and recovery.
5. Load and concurrency testing must run at the approved design-partner cohort and SLO targets.
6. Independent security/privacy, methodology, legal, and human accessibility reviews must approve the promise, retention, sample/suppression rules, claims, keyboard/screen-reader behavior, and customer-facing language.
7. The owner must approve the initial buyer, segment, pricing, trial/advisory packaging, contract terms, and whether governed CSV plus manual roster management is sufficient for the first cohort or a contracted integration is required.

No item above should be represented as complete based only on source code or local tests.

## Reviewer Focus

The highest-risk review surfaces are:

- `app/Services/SurveyAnalyticsService.php`
- `app/Services/MetricRegistryService.php`
- `app/Services/OrganizationScopeService.php`
- `app/Services/SurveyResponseValidationService.php`
- `app/Services/SurveyAssignmentAccessService.php`
- `app/Services/OrganizationEntitlementService.php`
- `app/Services/PrivacyGovernanceService.php`
- `app/Services/ActionLoopService.php`
- `app/Jobs/ProcessSurveyWave.php`
- `app/Http/Controllers/StripeWebhookController.php`
- `database/migrations/2026_07_27_*.php`
- `config/privacy.php`, `config/billing.php`, and `config/runtime.php`

The release owner should follow `docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md`, `docs/RELEASE_AND_ROLLBACK_POLICY.md`, and `docs/BACKUP_RESTORE_AND_DISASTER_RECOVERY.md` after selecting the staging provider.
