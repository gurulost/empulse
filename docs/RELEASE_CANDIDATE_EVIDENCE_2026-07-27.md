# Empulse Release-Candidate Evidence — 2026-07-27

## Purpose

This handoff records what the production-readiness branch actually proves. It does not claim that Empulse is deployed, sold, or approved for customer use. No deployment provider has been selected and no live environment exists.

Current product, architecture, and release truth are separated from historical implementation records. Obsolete audits, completed checklists, and the February deploy handoff live under `docs/archive/` with an explicit warning that they are not deployment instructions or current sign-off evidence.

## Scope

Canonical repository: `gurulost/empulse`

Validated implementation candidate: `363346d8901e890c35686d01d69fc28a5d092276`

Implementation branch: `codex/production-readiness`

Base commit: `e166a6b9b26ae95584bb34b8fd7fa9410718f320`

The branch is a direct pre-deployment reset across:

- authorization, organization identity, invitations, historical cohort custody, and tamper-evident audit;
- survey publication, version pinning, expiring assignment access, mandatory validation, autosave concurrency, and atomic submission;
- delivery state, wave lifecycle, recurring Pulse governance, frozen audiences, reminders, and fatigue exclusions;
- respondent promise, privacy operations, retention/legal hold, sample and suppression policy, metric registry, reliability, and comparability;
- reliable findings, evidence-labeled intervention guidance, immutable leadership actions, communication, follow-up measurement, non-causal outcome evaluation, and privacy-safe value-loop reporting;
- company-owned billing, explicit administrator appointment/revocation, immutable catalog/entitlement history, Stripe reconciliation, usage, and billing-owner continuity;
- PostgreSQL/process topology, health checks, worker/scheduler heartbeats, CI, static analysis, dependency security, browser tests, accessibility automation, and operational runbooks.

The unsafe legacy roster importer remains removed. Its replacement uses encrypted CSV staging, stable company-scoped external identities, strict header/row/unit/supervisor validation, cross-tenant conflict detection, row-level reconciliation, an expiring confirmation token, stale-preview detection, atomic commit, tamper-evident audit, queued account invitations, scheduled delivery recovery with stable idempotency, and downloadable sanitized results. Missing rows never imply deactivation. Detailed rows expire after 30 days through the hash-confirmed, legal-hold-aware retention workflow while summary and audit evidence remain.

## Validation Completed

### Application and database

- `composer test` on SQLite: 200 tests, 1,121 assertions passed.
- Clean PostgreSQL 14 migration and canonical 62-item demo seed: passed.
- `composer test` on PostgreSQL 14: 200 tests, 1,121 assertions passed.
- Browser gate: all 17 role, roster-import authorization, route-failure, privacy-acknowledgment, required-answer, submission, completed-state, automated accessibility, admin-governance, and north-star action-loop journeys passed together against a fresh seeded PostgreSQL application with real web and queue processes. The baseline respondent journey answered all 62 canonical items and persisted exactly 62 answers. The north-star journey captured evidence, created an owned dated action and measurement plan, dispatched a governed three-item Pulse, collected five completions, evaluated a comparable non-causal outcome, and verified the WorkFit-admin value report through the UI.
- CI is configured to repeat the database and process journey on PostgreSQL 16 with real web and worker processes.

### Code and dependencies

- `vendor/bin/pint --test`: passed.
- `composer analyse`: passed for the release-critical authorization, audit, billing, survey-access, validation, and sample-policy paths with no baseline or ignored errors.
- `composer audit --no-interaction`: no security advisories.
- `npm audit --audit-level=high`: zero vulnerabilities.
- Dependency-license inventory: all PHP packages are permissive or usable under an available permissive license; the unused direct PHPMailer dependency was removed. Direct JavaScript dependencies are MIT/Apache-2.0, except the development-only axe Playwright adapter under MPL-2.0. No direct dependency has an unknown license.
- `npm run lint`: passed.
- `npm run test:unit`: 2 files / 6 tests passed, including untouched slider, exclusive multiselect, and analytics state coverage.
- `npm run build`: passed with 182 modules transformed.
- Laravel configuration, route, and Blade view caches build and clear successfully.
- Gitleaks current-source scan: no findings after removal of the legacy attachment directory.
- Gitleaks unignored full-history baseline: exactly three pre-existing findings remain—two generic-key matches in removed attachment history and one old Sendinblue/Brevo token in a historical controller commit. The repository owner and WorkFit mail administrator confirmed that the credential was revoked and deactivated.
- The owner declined a Git-history rewrite and accepted the residual visibility of the dead credential in old commits. The root `.gitleaksignore` contains only the three exact finding fingerprints. CI-pinned Gitleaks 8.24.3 proves that the unignored baseline is exactly that set, the approved full-history scan passes, and a newly generated unrecognized finding still fails.
- The first committed-SHA GitHub run exposed an npm lock inconsistency before tests began. The lock was regenerated from a clean dependency graph and then passed `npm ci` with the exact npm 10.9.8 version used by that runner.
- The initial GitHub secret job fetched full history but the action’s push-event command scanned only `-1` commit. The workflow now retains the proposed-change scan and adds an explicit fail-closed `gitleaks git --log-opts="--all"` history scan; a one-commit green result is not accepted as full-history evidence.
- GitHub Actions run `30377166138` on the product candidate promoted to `main`, `96985c01728197781471aae84d8367a58e753609`, passed the complete product job: PostgreSQL 16 migration/seed, 200 tests/1,121 assertions, Composer/npm audits, cache construction, Pint, static analysis, frontend lint and six component tests, the 182-module production build, real web/worker readiness, and all 17 Playwright role/failure/respondent/accessibility/governance/product-loop journeys. The proposed-change secret scan also passed. The overall workflow is red only because the explicit full-history job correctly detects the three historical findings.
- A disposable mirror rehearsal previously removed the two obsolete affected paths across all 366 commits and four affected refs. After rewriting, the old tainted commits/blobs were unreachable, `git fsck` passed, and Gitleaks scanned the rewritten history with no findings. The release-candidate tree remained exactly `cf4a3a028e652770c81bf4c5ec1050f2af84906c`, with a matching recursive tree-listing SHA-256 of `a493d9cff743dff816962ce60c54ea46872428c34788ba1785396f7f3f8a5387`. No remote ref was changed. The owner has now declined this rewrite; `docs/GIT_HISTORY_SECRET_REMEDIATION_RUNBOOK.md` retains it only as a future contingency.

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

The independent reviewer confirmed no P0/P1 remains in this late review scope. The final local candidate passes 200 tests/1,121 assertions on SQLite and PostgreSQL, static analysis, formatting, lint, six frontend tests, the production build, and all 17 browser/accessibility/product journeys.

### Final governance and product-loop closure

The last pre-deployment pass additionally proves:

- survey content follows draft → review → approval → publication with reviewer/approver/publisher identity, semantic content hashing, change summary, one-live-version enforcement, and edit locks;
- diagnostic findings, action plans, measurement definitions, published communications, and evaluated outcomes are immutable or append-only at their evidence boundaries, while lifecycle changes remain audited;
- action follow-up exposes owner, dates, hypothesis, change, success criteria, guardrails, open/due state, sample, comparability, movement, result, and causality limits;
- the WorkFit-admin value-loop report has a stable schema and returns aggregate chain completion only—never survey answers or employee identity;
- customer-approved advisor access governs both a scoped work queue and append-only customer-shared versus WorkFit-internal notes;
- billing access comes only from active company billing-admin appointments, not a static customer role; owner-controlled appointment/revocation and acceptance-gated transfer are audited;
- login throttling fails closed, provider response bodies and stack traces are not retained, contact input is validated/escaped/throttled, and avatar uploads are decoded and normalized onto a configured filesystem disk before the profile record changes;
- deterministic database seeding refuses to run in production.

The process-backed north-star journey found three defects that narrower tests had missed: labels not associated with action fields, a post-dispatch wave page that attempted forbidden lazy loading, and one-time Pulse dispatch incorrectly closing the collection before respondents could enter. Each was fixed at the product boundary and has regression coverage.

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

The governed-roster migration and the final four evidence/advisor/publication/billing-history migrations were rolled back and reapplied successfully against isolated disposable PostgreSQL databases. Provider-native backup/PITR and full application rollback still require the selected staging environment.

### Local capacity smoke

The checked-in k6 readiness/login profile ran for three minutes against the local PostgreSQL-backed web process:

- 20 maximum virtual users;
- 7,504 iterations and 22,512 HTTP requests;
- 45,024/45,024 checks passed;
- 0 failed requests;
- request p95 182.82 ms and p99 191.15 ms.

This does not replace the 500-respondent provider-staging, queue-age, analytics, mail-sandbox, worker-recovery, or shared-cache/session exercises in the capacity plan.

### Local Pulse analytics rehearsal

The new fail-closed `readiness:capacity-rehearsal` command passed from clean committed source `363346d8901e890c35686d01d69fc28a5d092276` against an isolated PostgreSQL 14 Pulse profile:

- 508 distinct assigned users and 411 submitted, valid responses (80.91% completion);
- 25,482 answers in the selected completed wave;
- ten recorded application-service runs after warm-up;
- analytics p95 1,088.99 ms against the 3,000 ms budget;
- eligible privacy/sample result;
- zero duplicate assignment, response, or answer groups;
- zero cross-tenant assignment/response rows or response-to-assignment mismatches.

The checked-in raw report is [`evidence/capacity-rehearsal-363346d.json`](evidence/capacity-rehearsal-363346d.json). Its `production_signoff` field is deliberately `false`. The companion PostgreSQL EXPLAIN ANALYZE profile completed the unfiltered latest-response query in 1.156 ms, selected-wave latest-response query in 2.230 ms, and unfiltered latest-answer query in 7.666 ms. These are local query-shape observations, not provider capacity claims.

Provider-backed roster parsing, 500-person dispatch and queue-age measurement, concurrent submissions, shared cache/session, mail sandbox, Stripe, worker recovery, alerting, backup/PITR, and deployed-topology evidence remain open.

## Accepted Risk: Preserved Git History

On 2026-07-28, the repository owner and WorkFit mail administrator confirmed that the historical Sendinblue/Brevo credential was revoked and deactivated. The owner chose to preserve Git history and explicitly declined the rehearsed force-push rewrite.

This accepted risk does not claim that the credential was removed. The dead value remains visible in historical commits, existing clones, and potentially cached historical views. The exception is restricted to the three exact fingerprints in `.gitleaksignore`; no rule, path, regular expression, commit, or general secret class is allowlisted. Current-source and proposed-change scans remain strict, and any unrecognized historical finding still fails the policy check.

## Evidence Boundaries

The repository is a strong release candidate, but customer launch is not yet approved. These gates require external state or accountable owner decisions:

1. A hosting, mail, observability, and backup provider must be selected and configured; staging must prove web, worker, scheduler, HTTPS, canonical URL, delivery webhooks, readiness, alerts, and rollback.
2. Stripe test mode must prove checkout, webhook replay/out-of-order handling, dunning, grace, cancellation, reactivation, and portal behavior with the approved catalog and prices.
3. Brevo or the selected provider must prove domain authentication, invitation/reminder delivery, bounce/complaint handling, suppression, and recovery.
4. Load and concurrency testing must run at the approved design-partner cohort and SLO targets.
5. Independent security/privacy, methodology, legal, and human accessibility reviews must approve the promise, retention, sample/suppression rules, claims, keyboard/screen-reader behavior, and customer-facing language.
6. The owner must approve the initial buyer, segment, pricing, trial/advisory packaging, contract terms, and whether governed CSV plus manual roster management is sufficient for the first cohort or a contracted integration is required.

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
