# Empulse Launch Decisions and External Review Handoff

Status: owner decisions and external evidence pending

Packet version: 1.0

Prepared: July 28, 2026

Empulse has not been deployed, sold, or approved for customer use. This handoff turns the remaining launch gates into named decisions and review deliverables. It does not approve those decisions, substitute implementation evidence for an independent review, or claim that a provider environment exists.

The last fully validated canonical `main` snapshot before this document update was `3e311fd91142003a62593a51e894df14199c386c`. [GitHub Actions run 30392355036](https://github.com/gurulost/empulse/actions/runs/30392355036) is bound to that `headSha` and passed PostgreSQL 16 migration/seed, 219 backend tests with 1,302 assertions, dependency audits, formatting and static analysis, eight frontend component tests, the 200-module production build, all 17 real-process browser journeys, the strict proposed-change secret scan, and the exact three-fingerprint full-history policy.

That repository evidence establishes a release-candidate baseline. It does not establish provider readiness, legal or scientific approval, human accessibility approval, commercial approval, or production sign-off.

## Current evidence and target artifact

| Field | Current value |
| --- | --- |
| Evidence as of | July 28, 2026, 3:41 p.m. America/New_York |
| No-deployment/no-sale attestation | Repository owner statement as of the evidence time above |
| Validated canonical source snapshot | `3e311fd91142003a62593a51e894df14199c386c` |
| Bound CI evidence | GitHub Actions run `30392355036`; successful `headSha` matches the source snapshot |
| Build/image digest | Pending provider/build selection |
| Staging environment ID and canonical origin | Pending LD-010 |
| Review packet version | `1.0`; all external review results pending |
| Production state | Not deployed; no production change record or live verification exists |

The final launch artifact will be a later named commit and immutable build digest if any required change follows this packet. Every approval and staging result must name the exact artifact it reviewed. A material code, configuration, policy, instrument, or customer-copy change invalidates the affected evidence and requires a scoped rerun or reissued review.

## Terms used in this handoff

- **WorkFit** — the platform operator and owner of the shared Empulse instrument and methodology.
- **Baseline/Launch** — the proposed assisted entry engagement that takes a company through setup, one governed full baseline wave, an eligible aggregate diagnosis, and an initial leadership response.
- **Eligible aggregate finding** — a server-recomputed company or subgroup result that passes the sample, completion, complementary-suppression, metric-valid-N, and role/tenant-scope rules.
- **Governed follow-up** — a versioned Pulse derived from a recorded finding and predeclared measurement plan, with a frozen audience and fatigue/reminder controls.
- **Value report** — the privacy-safe WorkFit-admin summary of aggregate finding-to-action-to-measurement-to-outcome completion; it contains no answer content or employee identity.

## Recommended launch boundary

The recommended first commercial boundary is deliberately narrower than everything the architecture could eventually support:

- one to three design-partner companies whose sponsor can act on workforce findings;
- a company-centered account with a named billing owner and explicit billing administrators;
- a WorkFit-governed 62-item baseline followed by leadership action;
- Starter for up to 100 active respondents; Pulse may be enabled for a first-cohort company only after it completes the baseline and records an owned action, while the first cohort remains capped at 100 active respondents per company;
- manual and governed CSV roster management for the first cohort;
- WorkFit-owned survey content with no customer item editing;
- optional WorkFit interpretation and action-planning services;
- no public trial;
- no claim of anonymity, psychometric validation, causality, benchmarking, employment suitability, HRIS integration, SSO, or enterprise functionality that has not been separately contracted and proven.

This is a recommendation derived from the implemented product and the working north star. The product owner must approve or replace it before customer-facing launch language, prices, contracts, or Stripe production catalog entries are finalized.

The implemented Pulse entitlement supports up to 500 active respondents, but that technical ceiling is not the recommended first-cohort size. Expansion above 100 requires separate release-owner approval after provider capacity and operations evidence.

## Owner decision register

“Recommended default” is not an approval. Record the binding decision in the ledger, then update the downstream sources named in the detailed register.

### Decision ledger

| ID | Status | Binding decision | Decision owner | Date and evidence |
| --- | --- | --- | --- | --- |
| LD-001 | Pending | — | — | — |
| LD-002 | Pending | — | — | — |
| LD-003 | Pending | — | — | — |
| LD-004 | Pending | — | — | — |
| LD-005 | Pending | — | — | — |
| LD-006 | Pending | — | — | — |
| LD-007 | Pending | — | — | — |
| LD-008 | Pending | — | — | — |
| LD-009 | Pending | — | — | — |
| LD-010 | Pending | — | — | — |
| LD-011 | Pending | — | — | — |

### Decision detail

| ID | Decision required | Recommended default | Owner evidence required | Downstream changes after approval |
| --- | --- | --- | --- | --- |
| LD-001 | Initial customer and buyer | A small-to-mid-sized employer with an executive or People/HR sponsor who owns culture, retention, and a real leadership action. Exclude buyers requiring anonymous collection, HRIS/SSO, benchmarks, or employee-level reporting for the first cohort. | Named segment, buyer title, excluded-fit criteria, decision owner, date. | Product vision, public positioning, onboarding defaults, sales qualification, and launch-cohort acceptance criteria. |
| LD-002 | First design-partner cohort | One to three companies, initially no more than 100 active respondents each; expand only after the baseline and action loop complete successfully. | Named cohort or written eligibility rule, accountable sponsor, support owner, observation period. | Staging fixtures, capacity target, support plan, rollout record, and release packet. |
| LD-003 | Offer structure | Baseline/Launch as an assisted entry offer; Starter for governed baseline/action up to 100 active respondents; Pulse for recurring learning up to 500; Enterprise remains sales-assisted and disabled for self-checkout. | Approved offer names, included services, respondent limits, billing periods, cancellation/refund terms, and grandfathering rule. | `config/billing.php`, public plan copy, Stripe catalog, contracts, tests, and sales material. |
| LD-004 | Prices | Do not guess. Approve integer Starter and Pulse prices and any Baseline/Launch or advisory fee before production checkout is enabled. | Currency, amount, billing period, tax treatment, implementation fee, discount authority, decision owner, date. | `BILLING_PRICE_*_CENTS`, Stripe price IDs, catalog sync, invoices/contracts, and checkout evidence. |
| LD-005 | Trial | Keep disabled for the first launch. | Explicit approve/decline decision; if approved, eligibility, duration, entitlements, expiry, conversion, abuse, and data-access rules. | Trial configuration, lifecycle implementation, copy, Stripe behavior, tests, and contract language. |
| LD-006 | Advisory boundary | Offer executive readout and action planning as optional WorkFit services; keep customer-data access purpose-bound, grant-scoped, expiring, revocable, and audited. | Approved service scope, delivery owner, response time, fee model, and customer-consent language. | Advisory offer copy, service playbook, contracts, support operations, and any entitlement changes. |
| LD-007 | Roster boundary | Treat manual entry plus governed CSV as sufficient for the design-partner cohort. Add HRIS/SCIM/SSO only behind contracted demand. | Written approval or named required integration with customer and deadline. | Qualification criteria or an integration project with its own security and release gates. |
| LD-008 | Privacy and legal policy | Use the implemented identifiable-assignment and aggregate-reporting model as the review baseline; do not describe responses as anonymous. | Counsel/privacy-owner approval or an issue list for the respondent promise, retention, rights, legal holds, cross-border processing, DPA, terms, and incident obligations. | New policy version, configuration, respondent/customer copy, contracts, migration/retention changes, and regression tests. |
| LD-009 | Methodology and claims | Keep current outputs descriptive, privacy-gated, and non-causal. Do not add validation, benchmark, prediction, or causal claims without evidence. | Independent methodology review of the instrument, scoring, reliability, sample/completion thresholds, suppression, comparison rules, opportunity ordering, and claims limits. | Versioned dossier/registry/instrument changes, customer copy, publication compatibility decision, and golden tests. |
| LD-010 | Hosting and operations | Select a container/buildpack provider capable of independent web, worker, and scheduler processes, PostgreSQL 16, durable shared cache/session, persistent object storage, HTTPS, secrets, metrics/logs, alerting, and isolated backup restore. | Provider names, account owner, region, data residency, service tiers, backup/PITR terms, cost approval, domain, and on-call owner. | Staging configuration and every provider evidence item below. |
| LD-011 | Launch authority | Separate product, legal/privacy, methodology, security, accessibility, operations, billing, and release approvals. | Named accountable approver for each lane and a final release owner who must affirmatively authorize the exact production artifact, may stop launch, and signs a dated release record. | Signed release packet and checklist status changes. |

## External review packets

Each reviewer receives the listed source material and returns a dated written result. A result is one of: recommended for approval as written, recommended with named residual risks, or changes required. Silence, a meeting, automated tests, or an internal engineering opinion is not a review result.

The reviewer must be independent of the implementation decision being assessed and have authority or relevant expertise for that lane; conflicts are disclosed in the result. The accountable lane owner separately accepts or rejects the recommendation. Both records are version-bound. A material change requires the affected reviewer to issue a scoped re-review or replacement result against the final artifact.

| Review lane | Status | Reviewer | Accountable lane owner | Version-bound result |
| --- | --- | --- | --- | --- |
| Privacy and legal | Pending | — | — | — |
| Methodology and claims | Pending | — | — | — |
| Security | Pending | — | — | — |
| Human accessibility | Pending | — | — | — |

### Privacy and legal

Review:

- [`RESPONDENT_DATA_PROMISE.md`](RESPONDENT_DATA_PROMISE.md);
- `config/privacy.php`;
- data collection identity, support access, exports, correction, erasure/pseudonymization, retention, legal hold, breach response, subprocessors, data residency, contract terms, and customer/employee notices.

Return:

- reviewer identity and authority;
- jurisdiction and customer cohort covered;
- approved policy version or redlined replacement;
- required DPA/terms/subprocessor language;
- accepted risks with owner and review date;
- explicit launch recommendation or stop.

### Methodology and claims

Review:

- [`METHODOLOGY_AND_CLAIMS_DOSSIER.md`](METHODOLOGY_AND_CLAIMS_DOSSIER.md);
- `survey_instrument.json`;
- `config/survey.php`;
- frozen metric registry, complete-case calculations, reliability disclosure, sample/completion thresholds, complementary suppression, longitudinal comparability, intervention guidance, and non-causal outcome wording.

Return:

- reviewer identity and relevant expertise;
- approved instrument and metric versions;
- findings by severity;
- permitted and prohibited customer-facing claims;
- required sensitivity or validation work;
- explicit launch recommendation or stop.

### Security

Review the deployed staging surface and the current-source paths named in [`RELEASE_CANDIDATE_EVIDENCE_2026-07-27.md`](RELEASE_CANDIDATE_EVIDENCE_2026-07-27.md), including tenant scope, capabilities, invitation tokens, survey submission, provider webhooks, billing administration, advisor grants, uploads, logs, privacy operations, and operational secrets.

Return:

- scope, dates, tester, methods, and staging SHA/environment;
- reproducible findings with severity and affected boundary;
- retest evidence for every launch-blocking issue;
- residual risks with owner;
- explicit launch recommendation or stop.

The repository owner and WorkFit mail administrator confirmed revocation and deactivation of the historical Sendinblue/Brevo credential on July 28, 2026. Git history was intentionally preserved, so the dead value remains visible in old commits. This is the existing accepted risk R-004 in [`EMPULSE_PRODUCTION_READINESS_CHECKLIST.md`](EMPULSE_PRODUCTION_READINESS_CHECKLIST.md), governed by [`GIT_HISTORY_SECRET_REMEDIATION_RUNBOOK.md`](GIT_HISTORY_SECRET_REMEDIATION_RUNBOOK.md) and the three exact fingerprints in [`.gitleaksignore`](../.gitleaksignore). It must not be described as credential removal. The security reviewer confirms that the existing controls are still operating and reports any reason the owner must reassess the acceptance.

### Human accessibility

Review the public, login, employee entry, respondent promise, complete 62-item survey, validation recovery, autosave/resume, completion, manager analytics, and leadership-action paths with keyboard-only use and representative screen-reader/browser combinations.

Return:

- reviewer, assistive technology, browser, operating system, viewport, and date;
- path-by-path results for focus order, focus visibility, names/roles/states, errors, live updates, slider operation, tables/charts, zoom/reflow, reduced motion, and completion;
- reproducible findings and retest evidence;
- explicit launch recommendation or stop.

Automated axe and browser checks are supporting evidence, not this independent human approval.

## Provider staging acceptance

After LD-010 is decided, create a non-production staging environment from an immutable Git SHA and stable environment ID. The release owner must attach evidence for every row.

| Gate | Status | Owner | Required evidence |
| --- | --- | --- | --- |
| Served identity | Pending | — | `app:verify-deployment` passes against the canonical HTTPS origin and exact SHA/environment while still reporting `production_signoff=false`. |
| Process topology | Pending | — | Independently supervised web, worker, and scheduler processes; heartbeat age no more than the configured 180 seconds; restart and worker-failure recovery evidence. |
| Data services | Pending | — | PostgreSQL 16, durable shared cache/session, persistent avatar storage or disabled avatar upload, encrypted secrets, and verified tenant-safe connectivity. |
| Mail | Pending | — | Authenticated sending domain, invitation/reminder sandbox delivery, signed webhook updates, bounce/complaint/unsubscribe suppression, idempotent retry, and recovery evidence; provider acceptance meets the [initial service targets](OBSERVABILITY_AND_SERVICE_LEVELS.md#initial-slos). |
| Stripe | Pending | — | Test-mode checkout, signed webhook replay and out-of-order behavior, dunning, grace, cancellation, reactivation, billing portal, and approved catalog/price evidence; processing meets the [initial service targets](OBSERVABILITY_AND_SERVICE_LEVELS.md#initial-slos). |
| Capacity | Pending | — | Approved cohort profile, authenticated-page p95 under 1.5 seconds, 500-respondent analytics p95 under 3 seconds, queue-age p95 under 2 minutes, concurrent autosave/submission, roster import, dispatch replay/recovery, and zero integrity/privacy breach. |
| Observability | Pending | — | External logs, metrics, dashboards, and fired/recovered alerts for the signals and thresholds in [`OBSERVABILITY_AND_SERVICE_LEVELS.md`](OBSERVABILITY_AND_SERVICE_LEVELS.md). |
| Backup and rollback | Pending | — | Provider backup/PITR configuration, isolated restore with row/hash and audit-chain checks, RPO 15 minutes or better, RTO no more than four hours, application rollback/forward-fix rehearsal, and artifact retention under the [DR objectives](BACKUP_RESTORE_AND_DISASTER_RECOVERY.md#objectives). |
| Synthetic product canary | Pending | — | Disposable synthetic data completes baseline dispatch through eligible aggregate finding, owned action, governed follow-up, comparable non-causal outcome, and WorkFit value report. No design-partner employee data is admitted to staging. |

Use [`PRODUCTION_DEPLOYMENT_RUNBOOK.md`](PRODUCTION_DEPLOYMENT_RUNBOOK.md), [`OBSERVABILITY_AND_SERVICE_LEVELS.md`](OBSERVABILITY_AND_SERVICE_LEVELS.md), [`BACKUP_RESTORE_AND_DISASTER_RECOVERY.md`](BACKUP_RESTORE_AND_DISASTER_RECOVERY.md), [`CAPACITY_AND_PERFORMANCE_TEST_PLAN.md`](CAPACITY_AND_PERFORMANCE_TEST_PLAN.md), and [`RELEASE_AND_ROLLBACK_POLICY.md`](RELEASE_AND_ROLLBACK_POLICY.md) as the execution contracts.

## Recommended sequence

1. Approve LD-001 through LD-007 so the offer and launch cohort are bounded.
2. Name the privacy/legal, methodology, security, accessibility, operations, billing, and final release authorities required by LD-008, LD-009, and LD-011.
3. Complete privacy/legal and methodology review before finalizing customer copy or contracts.
4. Decide LD-010, select the staging-provider stack, and configure only test/sandbox credentials.
5. Deploy the named candidate to staging and execute the provider acceptance table.
6. Complete security and human accessibility review against that staging SHA, build digest, environment ID, and canonical origin.
7. Resolve every required change, rerun the affected repository and staging evidence, require affected reviewers to reissue version-bound results, and update the live readiness checklist.
8. Run `php artisan readiness:checklist --require-signoff`. If it passes, the named lane owners and final release owner may sign a dated authorization for one exact production artifact.
9. Execute production as a separate approved change record with the backup, migration, rollback/forward-fix, DNS, secrets, and on-call owners attached. Admitting design-partner employee data occurs only in this separately authorized production or limited-production phase.
10. Run `app:verify-deployment` against the live canonical origin and authorized SHA/environment, execute the production smoke checks, and observe the SLO, queue, mail, billing, integrity, and privacy signals through the recorded monitoring window.
11. Record `production_signoff=true` only in the accountable release record after the live evidence is attached. Expand beyond the approved cohort only through another explicit release-owner decision.

For control purposes, “limited production” is production: steps 8 through 11, the same privacy/security/legal obligations, and every release/rollback control apply without exception. The recommended initial monitoring window is at least 24 continuous hours after live verification. The final release authorization records its exact duration and may only close it when readiness stayed healthy, no paging alert or unresolved P0/P1 finding remains, queue/mail/billing indicators met the approved SLOs, and no tenant, privacy, audit-integrity, or submitted-response-loss incident occurred. Any breach restarts or extends the window after remediation and re-verification.

## Evidence rule

A checklist item may move to `verified` only when its named evidence exists and covers the item’s full scope. An unresolved issue stays `open` or `in_progress`; a deliberate residual risk requires a named owner, rationale, controls, and review trigger. Do not mark provider, reviewer, commercial, or deployment gates complete from repository tests alone.
