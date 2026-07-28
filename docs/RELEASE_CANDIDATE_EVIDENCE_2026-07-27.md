# Empulse Release-Candidate Evidence — 2026-07-27

## Purpose

This handoff records what the production-readiness branch actually proves. It does not claim that Empulse is deployed, sold, or approved for customer use. No deployment provider has been selected and no live environment exists.

Current product, architecture, and release truth are separated from historical implementation records. Obsolete audits, completed checklists, and the February deploy handoff live under `docs/archive/` with an explicit warning that they are not deployment instructions or current sign-off evidence.

## Scope

Canonical repository: `gurulost/empulse`

Validated implementation candidate: `070447da2ec42379da52509b9b6b9d860fbbb7f0`

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
- Clean implementation `3dcf75a8bb702d05c28ad855a6a28aec6ab1f71a` binds a future artifact to an exact `APP_RELEASE_SHA` and stable `DEPLOYMENT_ENVIRONMENT_ID`. Production configuration rejects absent/malformed identity, disabled process-heartbeat enforcement, or a heartbeat-age policy outside 60–600 seconds. Health JSON and `X-Empulse-Release` expose the configured non-secret identity consistently.
- `app:verify-deployment` is an external fail-closed surface check for the canonical HTTPS origin, exact served SHA/environment, liveness/readiness, database/runtime-table status, fresh scheduler/worker heartbeats, and login security headers. Its machine-readable report always sets `production_signoff=false` and names the mail, Stripe, load, backup, rollback, alert, accessibility, privacy, methodology, legal, and commercial gates it does not prove. No deployed target has been exercised.

### Code and dependencies

- `vendor/bin/pint --test`: passed.
- `composer analyse`: passed for the release-critical authorization, audit, billing, survey-access, validation, and sample-policy paths with no baseline or ignored errors.
- `composer audit --no-interaction`: no security advisories.
- `npm audit --audit-level=high`: zero vulnerabilities.
- Dependency-license inventory: all PHP packages are permissive or usable under an available permissive license; the unused direct PHPMailer dependency was removed. Direct JavaScript dependencies are MIT/Apache-2.0, the development-only axe Playwright adapter is MPL-2.0, and the self-hosted DM Sans, Outfit, and Inter font packages are OFL-1.1. Bootstrap Icons is MIT. No direct dependency has an unknown license.
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
  - respondent data-promise entry and the actual survey state.
- The gate found and drove remediation of global footer, page subtitle, table header, status badge, and public dashboard contrast failures.
- Clean implementation `070447da2ec42379da52509b9b6b9d860fbbb7f0` adds a Tier 2 original-design respondent experience brief and proves keyboard privacy entry, validation-to-first-error focus, arrow-key slider operation, page-heading focus, reduced-motion behavior, saved-state visibility, 44px primary controls, and no horizontal overflow or clipped navigation at 375, 390, 768, 1440, and 1920 pixels. The focused real PostgreSQL/web/worker Chromium gate passed all three accessibility journeys in 11.9 seconds with no browser console or page errors.
- Console review exposed that current layouts referenced CDN fonts/icons/scripts forbidden by their own CSP. The active layouts now use Vite-bundled Bootstrap, MIT Bootstrap Icons, and OFL-1.1 fonts; unused global Chart CDN scripts and legacy Toastr CDN paths were removed. CSP font/style sources are restricted to self/data/inline styles, and a permanent source test rejects those runtime CDN hosts.
- This remains automated implementation evidence. Independent keyboard and screen-reader review by an accountable accessibility reviewer is still required before customer launch.

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

The new fail-closed `readiness:capacity-rehearsal` command passed from clean committed source `2ff15d156f1fc68fa64e0b51c71ece43ffe2ca34` against an isolated PostgreSQL 14 Pulse profile:

- 508 distinct assigned users and 411 submitted, valid responses (80.91% completion);
- 25,482 answers in the selected completed wave;
- ten recorded application-service runs after warm-up;
- analytics p95 1,087.80 ms against the 3,000 ms budget;
- eligible privacy/sample result;
- zero duplicate assignment, response, or answer groups;
- zero cross-tenant assignment/response rows or response-to-assignment mismatches.

The checked-in raw report is [`evidence/capacity-rehearsal-2ff15d1.json`](evidence/capacity-rehearsal-2ff15d1.json). Its `production_signoff` field is deliberately `false`. The companion PostgreSQL EXPLAIN ANALYZE profile completed the unfiltered latest-response query in 1.156 ms, selected-wave latest-response query in 2.230 ms, and unfiltered latest-answer query in 7.666 ms. These are local query-shape observations, not provider capacity claims.

Provider-backed roster parsing, queue-age measurement, concurrent submissions, shared cache/session, mail sandbox, Stripe, worker recovery, alerting, backup/PITR, and deployed-topology evidence remain open.

### Local governed-roster capacity and idempotency rehearsal

Clean implementation commit `919d367afe0f2fd789638a235590fc0d386f0dc2` passed the isolated 500-row PostgreSQL/database-queue path:

- staging stored encrypted source, queued exactly one parse job in 12.21 ms, and did not expose the synthetic email prefix in the stored value;
- direct parse produced an error-free 500-row create preview in 150.3 ms and cleared the encrypted source;
- identical preview upload reused the same import;
- atomic commit created exactly 500 users, compatibility roster rows, stable external identities, account invitations, and invitation jobs in 4,820.07 ms;
- commit replay added no jobs or invitations, repeated upload after commit remained read-only, one commit audit event existed, and no synthetic user crossed tenants.

The checked-in raw report is [`evidence/roster-rehearsal-919d367.json`](evidence/roster-rehearsal-919d367.json). Its `production_signoff` field is `false`: the parse job was executed directly after queue proof, the worker was stopped, queued invitations were not processed, and no provider request occurred.

### Local full-wave dispatch and recovery rehearsal

Clean implementation commit `6212f48b43d10ae26121a273ca3452cbbc5fd5ce` also passed a 500-employee PostgreSQL/database-queue rehearsal:

- initial full-wave freeze and dispatch created one 500-member cycle, 500 assignments, 500 dispatch-usage records, and 500 queued invitation jobs in 1,685.88 ms;
- replaying the same `ProcessSurveyWave` payload completed in 24.4 ms and left assignments, per-assignment dispatch counts, queued jobs, and usage totals unchanged;
- after deliberately removing all 500 synthetic queue rows and aging the assignment delivery state, report-only recovery identified 500 records without mutation;
- executable recovery restored exactly 500 jobs in 0.24 seconds;
- no assignment had a dispatch count above one and no wave/user assignment group was duplicated.

The checked-in raw report is [`evidence/dispatch-recovery-rehearsal-6212f48.json`](evidence/dispatch-recovery-rehearsal-6212f48.json). It is intentionally local and non-production: the worker was stopped, no provider request occurred, and the cache was not the durable shared cache required by the production contract. Provider-backed queue age, worker-supervisor failure, mail sandbox, shared-service, and alert evidence remain open.

### Local autosave and final-submission concurrency rehearsal

Clean implementation commit `e9472f0fd218a468a7431eec1a7af078d91d5983` passed a real paired-process PostgreSQL race against unused synthetic assignments:

- both autosave processes validated the same 62-answer payload and expected revision; one saved, one returned a conflict, the revision advanced once, and the stored payload hash matched;
- both final-submission processes validated the same complete payload before release; one committed and one returned “already completed” after the assignment row lock;
- the durable result was exactly one response, 62 answers, one completed-response usage event, a completed assignment, and a revoked access token.

The checked-in raw report is [`evidence/submission-concurrency-e9472f0.json`](evidence/submission-concurrency-e9472f0.json). It explicitly records `production_signoff: false`: this was an isolated local PostgreSQL race, not a deployed load-balancer/shared-session test or a provider-backed exercise.

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
