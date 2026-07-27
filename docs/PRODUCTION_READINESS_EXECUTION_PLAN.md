# Empulse Production Readiness Execution Plan

Status: Active implementation contract
Started: July 27, 2026
Branch: `codex/production-readiness`
Governing goal: Bring Empulse to a production-ready release candidate aligned with the product north star.

## Authorities

Read these in order:

1. `PRODUCT_VISION_AND_BUSINESS_MODEL.md` owns product intent and commercial direction.
2. `ARCHITECTURE.md` owns current-source architecture.
3. `EMPULSE_FULL_PROGRAM_AUDIT_AND_AGENT_PLAN_2026-07-27.md` and `EMPULSE_AGENT_EXECUTION_BACKLOG_2026-07-27.json`, supplied by the owner, provide the validated finding and ticket catalog.
4. `EMPULSE_PRODUCTION_READINESS_CHECKLIST.md` owns live execution status and evidence.

If an old route, schema field, screen, test, or phase note conflicts with these authorities, it is evidence of current implementation rather than a product requirement.

## Pre-deployment adaptation

Empulse has not been deployed, sold, or used with real customer/respondent data. The audit remains the finding authority, but its execution assumptions are adjusted:

- There is no active incident, customer migration, production database, or live deployment to preserve.
- Do not build dual-write, backfill, legacy reconciliation, temporary billing bridges, or customer continuity machinery solely to protect nonexistent production data.
- Development databases may be rebuilt from a clean schema and seed path.
- Prefer direct replacement of unsafe foundational models over patching a legacy model and migrating it later.
- Keep valuable Laravel, Vue, survey, analytics, wave, billing, and onboarding scaffolding when it fits the target model.
- Do not perform a framework rewrite merely because domain foundations are changing.
- Deployment-provider-specific work remains a release gate, but the repository must expose a provider-neutral production contract first.

## House rules

1. The company/organization is the durable commercial and analytical account.
2. Membership, role, and organizational placement are explicit and tenant-scoped.
3. Roster provisioning never launches measurement.
4. Every wave has an immutable instrument version, metric version, audience snapshot, and denominator.
5. Every assignment has revocable/expiring access and at most one immutable final response.
6. Server validation is authoritative; the browser is assistance only.
7. Ordinary account changes cannot erase or rewrite response evidence.
8. Results fail closed when privacy, sample reliability, cohort, version, or comparability requirements are not satisfied.
9. The product does not claim anonymity, scientific validation, benchmarking, prediction, or causality without an approved enforceable policy and evidence.
10. The retained-value loop is diagnosis, finding, leadership action, follow-up measurement, and learning.
11. Billing and entitlements belong to the organization, not whichever manager row is returned first.
12. A passing mechanical test is evidence only for what that test actually proves.

## Revised execution gates

### Gate A — Direct foundational reset

Replace the unsafe trust and domain foundations directly:

- remove dangerous password, destructive GET, global mutation, and broad support paths;
- establish canonical organizations, memberships, capabilities, organizational units, and deactivation;
- establish immutable instrument/version publication;
- separate roster, invitation, wave, audience freeze, assignment, and dispatch;
- establish revocable assignment access, strict validation, and atomic/idempotent response persistence;
- establish truthful wave and delivery state;
- establish organization entitlement authority;
- prove one clean PostgreSQL baseline journey.

Audit tickets primarily absorbed: EMP-001–015 and EMP-101–110. EMP-000 becomes a source/schema baseline rather than production backup work. EMP-113/114 are replaced by clean-schema verification because no customer data exists.

### Gate B — Trustworthy diagnosis and respondent experience

- approve respondent-data promise and methodology/claim limits;
- reduce or justify instrument burden and branching;
- introduce a versioned metric registry;
- score per respondent before cohort aggregation;
- expose denominator and missingness;
- enforce minimum sample, complementary suppression, access scope, reliability, and comparability;
- repair full-survey accessibility, mobile behavior, and autosave confidence;
- verify formulas with golden fixtures and production-scale query tests.

Audit tickets primarily absorbed: EMP-108–112 and EMP-201–215.

### Gate C — Findings-to-action-to-learning product

- create immutable finding snapshots;
- create owned action plans and curated WorkFit intervention guidance;
- communicate the leadership response without exposing individuals;
- predeclare follow-up measurements;
- evaluate movement, implementation fidelity, comparability, and uncertainty;
- give WorkFit advisors scoped audited access;
- redesign manager experience around the operating loop;
- prove the complete loop end to end.

Audit tickets: EMP-301–310.

### Gate D — Commercial and recurring operation

- make billing organization-owned;
- normalize plans, prices, trials, features, limits, and entitlements;
- make Stripe processing idempotent and reconcilable;
- establish usage measurement;
- create governed Pulse variants and fatigue controls;
- center retention on accumulated learning.

Audit tickets: EMP-401–409. EMP-410 remains demand-gated.

### Gate E — Production assurance

- PostgreSQL and real-process CI;
- static analysis, formatting, dependency, secret, and security gates;
- Vue component and substantive browser journeys;
- accessibility, load, concurrency, and capacity evidence;
- liveness/readiness, logs, metrics, tracing, and SLOs;
- provider-neutral deployment, migration, rollback, backup, restore, and incident runbooks;
- independent fresh-context product, privacy, security, methodology, and accessibility review.

Audit tickets: EMP-501–510.

## Validation ladder

Run the narrowest relevant proof after each mechanism, then broaden:

1. focused unit/feature/component test;
2. affected subsystem suite;
3. PHP suite;
4. frontend lint and build;
5. PostgreSQL integration;
6. browser role and product journeys;
7. security, accessibility, dependency, and static-analysis gates;
8. load/concurrency and operational drills;
9. fresh-context adversarial review;
10. final checklist validation with sign-off enforcement.

Any code change after a checked validation invalidates the affected validation item.

## Owner decisions

Implementation may use the north-star working defaults, but customer-facing interpretation and commercialization cannot pass their gates until the owner approves:

- respondent confidentiality and individual-answer access;
- retention, correction, export, erasure, and legal-hold policy;
- minimum sample and complementary-suppression policy;
- approved metric definitions, claims, and comparisons;
- treatment of the unused organizational-culture block;
- role of desire/importance in prioritization;
- initial customer segment;
- offers, pricing, trial, limits, and grandfathering;
- company owner and billing-admin model;
- WorkFit advisor service boundary;
- customer instrument-editing boundary.

## Completion rule

Empulse is production-ready only when every required checklist gate is verified, the final validation suite has run after the last code change, no unresolved P0/P1 finding remains, residual risks have explicit owners, and a fresh reviewer fails to disprove readiness.
