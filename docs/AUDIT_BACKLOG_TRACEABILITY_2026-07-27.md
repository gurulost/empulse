# Empulse Audit Backlog Traceability

This register reconciles every ticket in the owner-supplied 75-ticket execution backlog with the current pre-deployment reset. It prevents grouped checklist entries from hiding an omitted requirement.

Source artifacts reviewed on 2026-07-27:

- full audit SHA-256: `e6c7b2b9176b22d9aa8fd241c8410d36fefb879ec54ac07550a0e204d4f0ae83`;
- execution backlog SHA-256: `6d25b6c7d41771ea90e60126982e8e4697750809bdde8dfc52a8bcbb62e397c6`;
- execution backlog ticket count: 75.

This document records implementation disposition, not permission to deploy. [`EMPULSE_PRODUCTION_READINESS_CHECKLIST.md`](EMPULSE_PRODUCTION_READINESS_CHECKLIST.md) owns sign-off, residual risk, and external gate status. “Verified local” means current source plus repository-controlled tests or drills prove the repository portion; it does not substitute for provider, independent-review, commercial, legal, or production evidence.

## Disposition legend

- **Verified** — repository requirement is implemented and covered by current evidence.
- **Verified local; external gate** — repository controls exist, but an environment, provider, independent reviewer, or owner must supply the remaining evidence.
- **Superseded by pre-deployment reset** — the ticket assumed production/customer data that the owner confirmed does not exist; clean-schema proof replaces migration/repair machinery.
- **Governed deferred** — intentionally excluded until the trigger in product policy is met.
- **External gate** — cannot be truthfully completed by source code or local automation.

## Phase 0 — Contain and establish truth

| Ticket | Disposition | Authoritative evidence or remaining gate |
| --- | --- | --- |
| EMP-000 | Superseded by pre-deployment reset | [`PRODUCTION_READINESS_EXECUTION_PLAN.md`](PRODUCTION_READINESS_EXECUTION_PLAN.md) records the no-deployment/no-customer reset; release branch, checklist, reproducible clean schema, restore drill, and rollback notes replace production freeze/backup claims. |
| EMP-001 | Verified | `SecurityBoundaryTest` proves the arbitrary password, role, and company mutation path is absent and cannot mutate another identity. |
| EMP-002 | Verified | `SecurityBoundaryTest`, `RoleAccessFlowTest`, explicit capabilities, and audited support boundaries replace state-changing GET, hard delete, direct impersonation, and broad internal bypasses. |
| EMP-003 | Verified local; external gate | `ProductionConfigurationTest`, `CheckProductionConfiguration`, trusted-host/proxy/security-header policy, canonical URL rules, fail-closed login throttling, sanitized provider failures, and removal of stored login stack traces pass; selected-provider HTTPS/proxy proof remains in Gate E. |
| EMP-004 | Verified | Laravel bootstrap, route, middleware, and Stripe webhook CSRF behavior are consolidated in current source and exercised by route, billing, and production-configuration tests. |
| EMP-005 | Verified local; external gate | `Dockerfile`, `Procfile`, Compose topology, health/readiness endpoints, and [`PRODUCTION_DEPLOYMENT_RUNBOOK.md`](PRODUCTION_DEPLOYMENT_RUNBOOK.md) agree on separate web, worker, and scheduler processes; provider execution remains. |
| EMP-006 | Verified | Restrictive evidence foreign keys, effective-dated organization history, deactivation, legal hold, retention, and audit-chain tests prevent ordinary destructive tenant/evidence deletion. |
| EMP-007 | Verified | Survey definition, count, clone, builder, and submission paths emit each standalone/section item once; the canonical fixture proves 62 rendered and 62 unique QIDs. |
| EMP-008 | Verified | `SurveySubmissionValidationTest` and `SurveyResponseValidationService` enforce schema-derived server validation without a runtime bypass. |
| EMP-009 | Verified | Hashed, expiring, revocable assignment access plus wave/user/lifecycle eligibility is enforced by `SurveyAssignmentAccessService` and survey controller tests. |
| EMP-010 | Verified | Optimistic draft revisions, row locking, uniqueness constraints, atomic response/answer persistence, and replay tests cover autosave and final submission concurrency. |
| EMP-011 | Verified | Published instrument content hashes, one-live-version rules, wave-cycle pins, response pins, semantic clone equality, and metric-registry bindings are tested. |
| EMP-012 | Verified | Roster creation issues account-only setup invitations; assignment creation occurs only from explicit wave/cycle paths. `AccountInvitationTest`, `RosterImportTest`, and `SurveyWaveTest` cover the separation. |
| EMP-013 | Verified | Wave state, assignment state, delivery attempt, provider acceptance, delivery events, suppressions, retries, and completion truth are separate and tested. |
| EMP-014 | Verified | Company billing accounts, normalized entitlements, lifecycle state, limits, and locked usage—not an arbitrary manager tariff—authorize dispatch. |
| EMP-015 | Verified | Clean PostgreSQL migration/seed, the complete backend suite, role/failure/respondent browser journeys, and CI process topology are recorded in [`RELEASE_CANDIDATE_EVIDENCE_2026-07-27.md`](RELEASE_CANDIDATE_EVIDENCE_2026-07-27.md). |

## Phase 1 — Durable tenant, identity, and privacy foundation

| Ticket | Disposition | Authoritative evidence or remaining gate |
| --- | --- | --- |
| EMP-101 | Verified | Companies, users, billing authority, effective-dated memberships, activation/deactivation, and ownership continuity are canonical and covered by provisioning, organization, and billing tests. |
| EMP-102 | Verified | `organization_units` and effective-dated `organization_assignments` own department/reporting truth; legacy text tables are compatibility projections only. |
| EMP-103 | Verified | Immutable cycle audience rows and response cohort copies retain unit, reporting line, role, invitation denominator, and reproducibility hashes. |
| EMP-104 | Verified | `config/capabilities.php`, `RequireCapability`, policies, frozen hierarchy scopes, and authorization/privacy tests enforce role and row scope. |
| EMP-105 | Verified | Expiring single-use account invitations replace plaintext temporary passwords; delivery tokens are encrypted at rest and cleared after delivery/acceptance. |
| EMP-106 | Verified | Registration and social identity creation are transactional; companyless or ambiguous social attachment fails closed in provisioning/security tests. |
| EMP-107 | Verified | Governed CSV staging, row validation/reconciliation, stable external IDs, explicit confirmation, stale-preview rejection, atomic commit, result sanitation, retention, and invitation recovery pass on SQLite/PostgreSQL. |
| EMP-108 | Verified local; external gate | Versioned disclosure and acknowledgment implement the non-anonymous respondent promise; independent privacy/legal approval remains. |
| EMP-109 | Verified local; external gate | Verified access/correction/erasure, legal hold, dry-run/hash-confirmed retention, and durable audit evidence are implemented; jurisdictional/legal approval remains. |
| EMP-110 | Verified | Append-only platform/company HMAC audit chains, verification command, mutation guards, restore-chain proof, and a sanitized/filterable WorkFit investigation screen with audited views are implemented. |
| EMP-111 | Verified local; external gate | Deterministic attempt keys, encrypted stable links, provider idempotency, retry TTL, webhook events, contact suppression, truthful funnel state, and account-invitation recovery are implemented; selected mail-provider drills remain. |
| EMP-112 | Verified local; external gate | Respondent labels, untouched-slider handling, focus/error/live regions, autosave state, mobile layout, component tests, and axe journeys pass; independent keyboard/screen-reader review remains. |
| EMP-113 | Superseded by pre-deployment reset | The owner confirmed no production/customer data. A clean canonical seed, schema invariants, and PostgreSQL install/restore proof replace one-time legacy repair tooling. |
| EMP-114 | Superseded by pre-deployment reset | With no live data or deployment, direct clean-schema cutover replaces dual read/write migration. Migration rollback/reapply and release rollback controls remain required. |

## Phase 2 — Methodology and trustworthy diagnosis

| Ticket | Disposition | Authoritative evidence or remaining gate |
| --- | --- | --- |
| EMP-201 | Verified local; external gate | [`METHODOLOGY_AND_CLAIMS_DOSSIER.md`](METHODOLOGY_AND_CLAIMS_DOSSIER.md) defines descriptive constructs, formulas, limits, and prohibited claims; independent methodology approval remains. |
| EMP-202 | Verified local; external gate | Canonical v2 reduces the baseline to 62 purpose-bound items, removes contact/unjustified demographic and unused culture burden, and publishes count/time metadata; research validation remains. |
| EMP-203 | Verified | Normalized schema, governed importer, publication linter, semantic clone checks, and explicit draft → review → approved → published workflow preserve content hash, reviewer, approver, publisher, change summary, locks, audit evidence, and one-live-version enforcement. |
| EMP-204 | Verified | Versioned metric registry content is hash-derived and pinned to cycles/responses; historical calculations resolve the stored definition and fail closed on mismatch. |
| EMP-205 | Verified | Respondent-level complete-case pairing precedes aggregation; outputs expose eligible, paired, missing, invited, and completed counts. Golden analytics tests cover the calculation. |
| EMP-206 | Verified local; external gate | Customer-facing labels use conservative descriptive names, including `WorkFit Indicator (pre-validation)`; independent methodology approval remains. |
| EMP-207 | Verified local; external gate | Unused organizational-culture items were removed from the canonical baseline; remaining governed constructs are documented and versioned. Any future construct requires approval/publication. |
| EMP-208 | Verified local; external gate | Opportunity ranking transparently combines paired current/ideal gap with declared desire/importance inputs and never presents prioritization as an outcome or causal score. |
| EMP-209 | Verified local; external gate | Company N≥5, subgroup N≥7, completion≥40%, metric-valid paired N, complementary suppression, and reliability labeling are enforced; final owner/methodology approval remains. |
| EMP-210 | Verified | Trends select exact immutable cycles, require matching instrument/metric hashes, preserve subgroup context, expose denominators, and refuse incomparable series. |
| EMP-211 | Governed deferred | No benchmark is calculated or claimed. Provenance, minimum data, methodology approval, privacy policy, and validated commercial demand are mandatory triggers before implementation. |
| EMP-212 | Verified | Immutable finding snapshots expose evidence, reliability, interpretation limits, decision rationale, and a governed action path. |
| EMP-213 | Verified | Golden fixtures independently assert paired gaps, sample/missingness, suppression, reliability, registry transforms, exact-cycle selection, and comparability. |
| EMP-214 | Verified | Frozen hierarchy scope, suppression, advisor grants, no raw-answer customer path, tenant-scoped report/action APIs, and audited roster/privacy exports are covered by privacy and governance regressions. |
| EMP-215 | Verified local; external gate | Query-shape refactors, production indexes, numeric-answer prefiltering, reproducibility pins, `analytics:explain`, and capacity budgets exist; approved-scale provider PostgreSQL evidence remains. |

## Phase 3 — Findings-to-action-to-learning product loop

| Ticket | Disposition | Authoritative evidence or remaining gate |
| --- | --- | --- |
| EMP-301 | Verified | `diagnostic_findings` preserve immutable cycle, metric, cohort, reliability, and evidence snapshots. |
| EMP-302 | Verified | Leadership actions require attributable decision, rationale, owner, hypothesis, planned change, guardrails, success criterion, start/target dates, and audit history. The plan is append-only after creation except for governed lifecycle transitions. |
| EMP-303 | Verified local; external gate | Five immutable curated playbooks expose source, conservative evidence grade, applicability, limitations, guardrails, and claim limits; “investigate first” is available when context is uncertain. Independent methodology/advisory approval remains. |
| EMP-304 | Verified | Attributable leadership communications and follow-through records are linked to the action and audited. |
| EMP-305 | Verified | Measurement plans predeclare metric, audience, timing, success criterion, expected direction, minimum change, and a compatible governed follow-up wave; the manager workspace exposes open/due dates and follow-up collection state. |
| EMP-306 | Verified | Outcome evaluation is idempotent, immutable, hash/comparability gated, denominator-aware, and uses non-causal language. The manager workspace exposes baseline/follow-up values, target movement, valid sample, comparability, guardrails, result, and causality limit. |
| EMP-307 | Verified | WorkFit advisory requires named, customer-approved, purpose-bound, expiring/revocable grants; the operations queue is grant-scoped and audited, and append-only customer-shared versus WorkFit-internal notes have enforced visibility. |
| EMP-308 | Verified | Manager home is organized around listening readiness, trustworthy finding, decision, owned action, communication, and next measurement—not a chart inventory. |
| EMP-309 | Verified | First-wave/response milestones and tamper-evident finding/action/measurement/communication/outcome events feed a versioned WorkFit-admin value-loop report. It reports privacy-safe counts/rates and organization summaries without answer content or employee identity and makes no deployment-usage claim. |
| EMP-310 | Verified | Feature coverage and the PostgreSQL/queue-backed Playwright north-star journey prove reliable finding → decision → owned dated action → measurement plan → communication → governed Pulse → five employee completions → comparable remeasurement → non-causal outcome → admin value report through the product UI. |

## Phase 4 — Recurring product and commercial system

| Ticket | Disposition | Authoritative evidence or remaining gate |
| --- | --- | --- |
| EMP-401 | Verified | Billing account, explicitly appointed active administrator, subscription snapshot, entitlements, usage, and lifecycle state belong to the company rather than an arbitrary role or user. Static manager/chief roles do not grant billing access. |
| EMP-402 | Verified local; external gate | Immutable content-hashed catalog versions and per-company entitlement history preserve original plan/features/limits/price mappings while the current projection gates features; materialization fails closed. Exact owner-approved prices/packaging remain. |
| EMP-403 | Verified | Billing-owner transfer requires explicit initiation/acceptance, preserves continuity, and is tested across authorization and lifecycle cases. The active owner can separately appoint or revoke additional billing administrators; all changes are tenant-scoped and audited. |
| EMP-404 | Verified local; external gate | Stripe event identity, replay/out-of-order guards, reconciliation state, and webhook tests are implemented; Stripe test-mode provider drills remain. |
| EMP-405 | Verified local; external gate | Trial, active, grace, past-due, canceled, and reactivation semantics are encoded conservatively; owner-approved commercial terms and Stripe drills remain. |
| EMP-406 | Verified | Active-respondent reservation and measurement usage commit under a company lock, remain idempotent under retry, and expose privacy-safe customer derivation by event count, quantity, unit, definition, and evidence window. |
| EMP-407 | Verified local; external gate | WorkFit-owned immutable Pulse variants contain only the frozen finding metric items and require the recurring entitlement; methodology approval remains. |
| EMP-408 | Verified | Each cycle freezes eligible audience and exclusions; reminders, rest periods, rolling frequency, completion, and fatigue rules are enforced. |
| EMP-409 | Verified | Dashboard, reports, actions, communications, measurement plans, trends, and outcomes center the recurring learning loop. |
| EMP-410 | Governed deferred | SSO/SCIM, data residency, integrations, benchmark products, and enterprise hierarchy/SLAs remain outside core until a contracted sales motion defines requirements. |

## Phase 5 — Engineering quality, operations, and release discipline

| Ticket | Disposition | Authoritative evidence or remaining gate |
| --- | --- | --- |
| EMP-501 | Verified local; external gate | PostgreSQL CI, clean migration/seed, real web/worker browser topology, and readiness checks exist; final committed SHA must pass after history remediation. |
| EMP-502 | Verified local; external gate | Pint, scoped no-baseline PHPStan, Composer/npm audit, lint, license inventory, and strict current/proposed secret scans are permanent gates. The revoked historical mail credential is an explicit `accepted_risk`: the owner declined a force-push rewrite, so the exact three-fingerprint exception preserves full-history scanning while any new or unrecognized finding still fails. |
| EMP-503 | Verified | Vue/Vitest covers respondent slider/exclusive-choice contracts plus analytics loading, suppression, setup-empty, and error states; ESLint, Vite build, and backend API/serialization tests are permanent gates. |
| EMP-504 | Verified | Seventeen Playwright journeys pass together against fresh PostgreSQL with real web and queue processes. They cover public pages, all roles, authorization/token failures, roster-import visibility, privacy acknowledgment, all 62 answers, completion, automated accessibility, and the complete finding-to-measured-outcome north-star loop. |
| EMP-505 | Verified local; external gate | Local PostgreSQL k6 readiness/login and 500-person analytics evidence pass. A source-bound 500-person full-wave rehearsal creates assignments/jobs in 1.686 seconds, remains unchanged when the wave job is replayed, and restores 500 intentionally removed synthetic queue jobs without duplicate assignments or dispatch counts. A clean-commit paired-process race proves one same-revision autosave winner/one conflict and one final-submission winner/one conflict with exactly one 62-answer response and usage event. Approved-scale shared-service/provider execution remains. |
| EMP-506 | Verified local; external gate | Liveness/readiness, web/worker/scheduler heartbeats, delivery/wave/audit telemetry, SLOs, alert thresholds, and incident ownership are documented; selected-provider alert routing/drills remain. |
| EMP-507 | Verified local; external gate | Backup/restore/DR runbook, local PostgreSQL restore, row-count reconciliation, audit-chain verification, and migration rollback/reapply are proven; provider-native encrypted backup/PITR drill remains. |
| EMP-508 | External gate | Automated axe and adversarial source reviews pass, but independent security/privacy, methodology/legal, keyboard/screen-reader, and commercial reviews require accountable humans. |
| EMP-509 | Verified local; external gate | [`RELEASE_AND_ROLLBACK_POLICY.md`](RELEASE_AND_ROLLBACK_POLICY.md) and deployment runbook define saved artifact identity, migration gates, feature controls, canary, rollback, and incident stop rules; selected-provider staging/canary/rollback remains. |
| EMP-510 | Verified | Product vision and architecture are authoritative; current runbooks/contracts are linked; stale audits are isolated under `docs/archive`; documentation portability/link tests and checklist validation fail closed. |

## Reconciliation result

- All 75 ticket identifiers appear exactly once in this register.
- 45 tickets are repository-verified without a ticket-specific external gate.
- 24 tickets have repository controls verified but still require provider, independent-review, commercial, legal, or committed-SHA evidence.
- 1 ticket is wholly an external assurance gate (`EMP-508`).
- 3 tickets use the owner-approved pre-deployment-reset disposition: `EMP-000` becomes the evidence baseline, while `EMP-113` and `EMP-114` are replaced by clean-schema proof.
- 2 tickets remain governed deferred (`EMP-211`, `EMP-410`).

The counts above describe ticket disposition, not production approval. Any status change must update this register, the readiness checklist, and the cited evidence together.
