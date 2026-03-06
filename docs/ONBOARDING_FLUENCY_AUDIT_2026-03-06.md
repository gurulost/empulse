# Empulse Onboarding Fluency Audit

Date: March 6, 2026

## Scope

This audit focused on the real first-session paths exposed by the current codebase:

- guest landing and auth
- manager analytics dashboard
- team setup
- survey management and wave dispatch
- employee survey completion

The goal was not to add a generic tutorial. The goal was to shorten the path from first login to first meaningful value.

## Product Understanding

Empulse is a multi-tenant employee feedback platform built on Laravel 11 with Vue 3 islands. The main value loop is:

1. a manager or admin sets up a company roster
2. a live survey version exists
3. a wave is dispatched
4. employees submit responses
5. analytics and reports become meaningful

The codebase already supports most of this operationally:

- internal survey engine with autosave and validation
- survey waves with billing and cadence controls
- company analytics and reports
- role-based dashboards

## First Meaningful Success

For a manager, the first meaningful success is:

`first completed survey response received for the company`

Why this is the correct success event:

- creating an account is not value
- importing teammates is setup, not value
- creating a wave record is still not value
- the dashboard only becomes useful after real response data arrives

Failure event:

- manager reaches the analytics dashboard and cannot tell what exact prerequisite is missing

## Role-Based Entry Analysis

### Guest / sign-up

Strengths:

- landing page clearly sells the product outcome
- auth surfaces are visually stronger than the legacy Blade screens

Weaknesses:

- sign-up does not set expectations for the activation path after account creation

### Manager

This is the most important onboarding path and the weakest one before this pass.

Observed path from code:

- `/dashboard/analytics`
- `/team/manage`
- `/surveys/manage`
- `/survey-waves`
- `/account/billing`

Primary friction:

- these are separate surfaces with no persistent through-line
- the dashboard previously explained that data was missing, but not which prerequisite was missing
- the Team page empty state did not distinguish `empty roster` from `filtered to zero results`
- the manager can verify survey availability, but cannot directly publish a survey version if none is live

### Employee

The employee flow is comparatively healthy:

- dashboard explains whether an assignment exists
- current assignment, draft state, and history are visible
- the survey renderer has progress, autosave, validation, and completion confirmation

Main remaining opportunity:

- reinforce confidentiality and expected completion time near the survey launch CTA

### Workfit admin

The Workfit admin has strong system-level access, but the activation path for a selected company is still mostly implicit.

## Mental-Model Gaps

### Gap 1: "Dashboard empty" did not mean anything precise

A blank analytics state could mean any of these:

- no company attached
- no recipients
- billing blocked
- no live survey version
- wave created but never dispatched
- dispatched wave but no completed responses yet

The old UI mostly collapsed these into one generic empty card.

### Gap 2: team setup did not maintain direction

`No team members found` is accurate but weak. It does not answer:

- what belongs here
- what task this unlocks next
- whether importing or manual add is the better first move

### Gap 3: support/help rediscovery had a broken path

The authenticated sidebar linked to `/contact-us`, but the actual route is `/contact`.

## UI vs Tutorial Decision

Per the onboarding decision framework, this product did not need a launch tour.

Why:

- the interaction model is not novel
- the main failure mode is sequencing and missing affordance, not hidden control mechanics
- a modal walkthrough would add extraneous load without reducing the real setup ambiguity

Chosen pattern stack:

1. self-descriptive UI
2. empty states with strong CTAs
3. visible checklist for multi-step activation
4. persistent route-based recovery instead of one-time overlays

## Implemented in This Pass

### 1. Setup-aware analytics API contract

`GET /analytics/api/dashboard` now returns a `setup` payload alongside dashboard data and filter options.

Current fields include:

- recipient count
- department count
- billing status and dispatch eligibility
- active survey presence
- wave counts and latest wave info
- assignment count
- completed response count

This lets the UI tell the truth about what is missing.

### 2. Manager activation checklist on the analytics dashboard

When analytics data is empty, the dashboard now renders a checklist that maps directly to the real activation path:

1. add recipients
2. confirm billing
3. verify a live survey exists
4. dispatch the first wave
5. receive the first completed response

The checklist uses live backend state and highlights the next incomplete step.

### 3. Team empty-state redesign

The Team Members screen now:

- distinguishes `no matches for current filters` from `no team yet`
- explains why the roster matters
- offers direct actions: import roster, add first member, create/review departments

### 4. Help-path repair

The authenticated sidebar now points to `/contact`, which matches the registered contact route.

## Remaining Fluency Gaps

### Manager cannot resolve every blocker alone

If no live survey version exists, the manager can inspect status but not fix it directly. This is an ownership mismatch, not just a copy issue.

Recommended follow-up:

- either give managers a constrained publish path for the default survey
- or make the UI explicitly state that Workfit admin owns this prerequisite

### Survey Waves page still assumes product knowledge

The page is operationally strong, but still reads like an admin console instead of a first-run guide.

Recommended follow-up:

- add a compact "before you send" checklist above the create-wave form
- explain `full` vs `drip` in plain language near the controls

### No onboarding instrumentation yet

This gap is now partially addressed:

- first-party onboarding telemetry is stored in `onboarding_events`
- the dashboard checklist and survey-waves explainer both emit onboarding events
- backend milestones now record `first_wave_dispatched` and `first_response_completed`
- Workfit admin now has an internal onboarding telemetry report that aggregates activation stage by company

What is still missing:

- deeper cohort analysis over onboarding telemetry
- alerting or periodic review of TTFMS/activation trends
- segmentation beyond basic `novice` / `expert` labels

## Recommended Metrics

Track at minimum:

- time to first completed response
- rate of managers reaching first dispatched wave
- rate of managers reaching first completed response
- count of users who visit Team before first wave
- count of users who revisit the setup checklist after first session

Recommended event names:

- `session_started`
- `first_wave_dispatched`
- `first_response_completed`
- `onboarding_checklist_viewed`
- `onboarding_step_cta_clicked`
- `help_entry_opened`

## Recommended Next Pass

1. Build a simple admin/internal report for `onboarding_events` so the new telemetry becomes actionable.
   Status: completed in the Workfit admin dashboard.
2. Make survey availability ownership explicit for managers when no live version exists.
3. Add employee-facing reassurance near the survey entry point: confidentiality, time estimate, autosave behavior.
4. Add stronger role-based segmentation and compare activation by manager cohorts.
