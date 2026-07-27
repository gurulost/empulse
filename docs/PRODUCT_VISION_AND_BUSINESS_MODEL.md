# Empulse Product Vision and Business Model

Status: Working north star for product, design, engineering, operations, and commercial planning
Last updated: July 27, 2026

## Why this document exists

This document defines what Empulse is trying to become, which outcomes matter, and how the product is expected to create and capture value. It is the first product document future contributors should read.

It deliberately separates:

- **Product direction** — the experience and business we intend to build.
- **Implemented truth** — what the current repository can do today.
- **Open decisions** — choices that still require an explicit product-owner answer.

Do not treat a legacy screen, route, database field, or old phase note as product strategy when it conflicts with this document. When the product direction changes, update this document deliberately and record the decision.

## The product in one sentence

**Empulse is WorkFit's company-level workplace diagnostic and continuous-listening platform: it helps leaders discover the gap between what people need from work and what they currently experience, understand the culture around those gaps, choose where to intervene, and measure whether conditions improve over time.**

Empulse supports the broader WorkFit mission of [changing lives by building healthy workplace cultures](https://workfitdx.com/about-workfit-dx/). It should turn that mission and the WorkFit assessment methodology into a repeatable operating system for organizations, not merely a collection of survey forms and charts.

## The problem Empulse solves

Many organizations collect employee feedback but still cannot answer the decisions that matter:

- What do our people value most from work?
- Where is the largest mismatch between the experience people have and the experience they consider ideal?
- Are the most important problems about rewards, flexibility, growth, purpose, relationships, progression, or impact?
- Which teams or departments have meaningfully different conditions?
- Is the surrounding culture psychologically safe, ethical, and supportive?
- Did an intervention actually improve the employee experience?
- Where should leaders act first?

A one-time engagement score can describe sentiment without explaining the underlying fit or showing whether change worked. Empulse is intended to close that gap through a research-informed instrument, cohort analytics, repeated measurement, and guided interpretation.

## Who the product serves

### Economic buyer

The working buyer assumption is the person accountable for workforce outcomes at a company or business unit. Depending on the organization, that may be:

- a founder, owner, or general manager;
- a People/HR leader;
- an operating executive;
- an organizational-development or culture leader.

The current application represents this buyer and account owner as the **Manager** role. The durable commercial account is the **company**, even though Stripe billing is currently attached to the manager's user record.

### Product users

| Actor | Job to be done |
| --- | --- |
| Company manager | Set up the organization, authorize billing, launch measurement, and decide what to do with the results. |
| Chief / executive | Understand conditions across their area and align action with company priorities. |
| Team lead | Understand the experience and culture of the team they influence. |
| Employee / respondent | Give thoughtful input through a trustworthy, understandable, recoverable survey experience. |
| WorkFit admin | Protect the instrument and methodology, publish survey versions, support customer activation, and monitor account health. |
| WorkFit advisor / operator | Help leadership interpret results and translate findings into action. This is a target service role, not yet a distinct application role. |

## The value promise

Empulse should give a customer four connected forms of value:

1. **Diagnosis** — reveal the largest and most consequential gaps in the employee experience.
2. **Prioritization** — show leaders which work-content and culture conditions deserve attention first.
3. **Alignment** — make differences across teams, departments, and leadership layers visible without losing the company-wide picture.
4. **Learning over time** — repeat measurement so leaders can see whether actions changed conditions.

The dashboard is not the product's final value. A credible management decision, followed by measurable improvement, is.

## What Empulse measures

The current canonical instrument is the **Organizational Culture & Work Content Survey** in [`survey_instrument.json`](../survey_instrument.json). The current analytics model includes:

- twelve work-content attributes measured as:
  - **current** — what the person experiences now;
  - **ideal** — what the person believes the experience should be;
  - **desire** — how strongly the person wants the condition;
- seven roll-up indicators:
  - Relationships & Team
  - Learning & Growth
  - Purpose & Meaning
  - Rewards
  - Flexibility
  - Progression
  - Impact
- team-culture measures, including psychological safety;
- workplace ethics and leadership;
- perceived impact on society;
- a weighted indicator score;
- a team-culture evaluation;
- a composite temperature index;
- department, team, and survey-wave comparisons and trends.

Question-to-metric mappings and weights belong in [`config/survey.php`](../config/survey.php). A change to a formula or mapping is a methodology change, not a cosmetic refactor. It must be documented, versioned, and tested.

## The core customer journey

```mermaid
flowchart LR
    A["Create company account"] --> B["Add or import the roster"]
    B --> C["Confirm billing and live WorkFit instrument"]
    C --> D["Send a full baseline wave"]
    D --> E["Employees complete secure assignments"]
    E --> F["First reliable company diagnosis"]
    F --> G["Leadership chooses an intervention"]
    G --> H["Run recurring pulse waves"]
    H --> I["Compare teams and trends"]
    I --> G
```

The first completed response is the **workflow activation milestone**: it proves that roster, content, dispatch, delivery, survey-taking, and persistence are connected. It is not, by itself, a reliable company diagnosis.

The first analytical success is reaching a documented minimum sample at which the company or cohort result can be interpreted responsibly. That policy and its UI safeguards are not implemented yet. Until they are, developers must not turn one response—or any very small cohort—into a confident company-level finding.

The complete value loop is:

1. Establish the company and its organizational structure.
2. Load the people who should participate.
3. Ensure the WorkFit-owned instrument is live and billing permits dispatch.
4. Start with a full, manual baseline wave.
5. Collect complete, valid responses through secure assignments with autosave.
6. Show work-content gaps, indicator satisfaction, culture, impact, and group differences.
7. Help leaders select and communicate an action.
8. Use recurring waves to measure movement.
9. Preserve a longitudinal record that makes improvement—or lack of improvement—visible.

## Product positioning

### Empulse is

- a B2B workplace diagnostic and continuous-listening system;
- the organization-facing software layer of the WorkFit methodology;
- a way to connect employee needs, present experience, team culture, and leadership action;
- a recurring measurement system, not just a survey launch tool;
- a platform that can support both self-service operations and WorkFit-guided engagements.

### Empulse is not

- a generic do-it-yourself form builder;
- a replacement for an HRIS, payroll system, or project-management tool;
- a personality test;
- a dashboard that should produce unsupported claims from small or low-quality samples;
- an anonymous survey today.

The current survey content is globally versioned and published by WorkFit admin. Managers can operate waves but do not own the methodology. The public promise and product UI should not imply that every manager can freely create arbitrary surveys unless that ownership model is intentionally changed.

Current responses are linked to an assignment and user. Product copy must describe that truth plainly and must not promise anonymity. If anonymity or confidentiality thresholds become part of the offer, they require an explicit data model, aggregation rules, minimum cohort sizes, access policy, and employee-facing explanation.

The repository does not yet define a complete respondent-data policy: which customer roles may inspect which response detail, how long identifiable answers are retained, when cohorts are suppressed, or which disclosures employees must acknowledge. “Secure assignment” means protected access to the assigned survey; it does not establish anonymous or confidential reporting.

## Product principles

### 1. Diagnose before decorating

Prefer improvements that increase the validity, clarity, or actionability of the diagnosis over generic dashboard polish.

### 2. Preserve methodology integrity

Survey versions, question mappings, polarity, weights, and formulas are product IP. Changes require evidence, versioning, and release notes.

The repository encodes the current methodology but does not contain a scientific validation dossier or approved interpretation thresholds. Do not make predictive, benchmark, or validation claims solely because a formula exists in configuration.

### 3. Earn employee trust

Tell respondents who receives their answers, how the data is used, whether responses are identifiable, how long the survey takes, and how progress is saved. Do not make broader privacy claims than the system can enforce.

### 4. Design for action

Every metric should help a leader understand what it means, why it matters, where to look, and what decision it supports. A chart without an interpretation path is incomplete.

### 5. Make repeated measurement valuable

The strongest reason to remain a customer is not access to another survey. It is the accumulated ability to compare waves, see movement, and learn which interventions work.

### 6. Keep customer and WorkFit ownership explicit

Customers own their organization setup, audience, dispatch timing, and leadership response. WorkFit owns the shared instrument, methodology, publication process, and platform-level operations unless a future product decision changes that boundary.

### 7. Protect cohort truth

Filters and comparisons must stay tenant-scoped, use consistent populations, disclose sample size, and avoid presenting tiny cohorts as reliable organizational findings.

## Working business model

This section is a **recommended working direction inferred from the current product and WorkFit's public service model**. The product owner must confirm it before pricing, marketing, or entitlement work treats it as final.

### Commercial thesis

Empulse should use a hybrid B2B model:

1. **Land with a baseline diagnostic** that gets a company from roster setup to an interpretable first result.
2. **Convert to a recurring Pulse subscription** for automated listening, longitudinal analytics, comparisons, and ongoing operational value.
3. **Expand through advisory services and enterprise scope** when customers need facilitated interpretation, action planning, multiple entities, governance, or deeper support.

This matches how value develops. The first wave proves relevance; recurring waves prove whether change works; advisory services help leadership convert evidence into results.

### Recommended offer structure

| Offer | Customer outcome | Revenue shape |
| --- | --- | --- |
| Baseline / Launch | Company setup, roster readiness, one full baseline wave, initial diagnostic, and optional executive debrief. | One-time implementation or diagnostic fee, likely scaled by respondent band and level of WorkFit support. |
| Starter | Team management, occasional full/manual measurement, core company analytics, and billing/support access. | Company subscription for customers who do not need automated recurring pulses. |
| Pulse | Recurring weekly/monthly/quarterly listening, automated dispatch, wave history, trends, comparisons, and operational logs. | Higher recurring company subscription; cadence automation is the clearest current paid entitlement. |
| Enterprise / Partner | Multiple companies or business units, higher respondent volume, advanced governance, exports/integrations, service levels, approved benchmarking, and facilitated interpretation. | Annual contract with custom pricing. |
| Advisory add-ons | Executive readout, action planning, facilitated team sessions, culture consulting, and measurement design. | Project, retainer, or bundled professional-services revenue. |

Under the current ownership model, “measurement design” means WorkFit-guided choices about audience, cadence, rollout, interpretation, and action planning. It does not promise customer-managed item editing. Any customer-specific instrument change would need to remain WorkFit-authored, versioned, published, and methodology-reviewed unless the product owner explicitly approves a different content model.

### Value metric

The commercial account should remain company-centered. A durable pricing model should reflect the amount of value and operational load through one or both of:

- active respondent/employee bands; and
- measurement cadence and service level.

Avoid pricing primarily by the number of internal manager logins. The value is created by the breadth and continuity of organizational insight.

### Expansion logic

Empulse can grow within an account when a customer:

- moves from a baseline to recurring listening;
- adds departments, teams, locations, or related companies;
- adds guided interpretation or action-planning support;
- needs longer trend history, benchmarks, exports, integrations, or governance;
- adopts adjacent WorkFit services where the fit is real.

### Implemented billing truth

The current repository already has:

- Stripe subscriptions through Laravel Cashier;
- plan records in the database;
- billing attached to the company manager's user account;
- webhook-driven subscription state;
- a `tariff` compatibility field propagated to company users;
- plan-aware survey scheduling;
- recurring drip cadences limited to tariff `1`, labeled **Pulse (Drip Enabled)**;
- billing states that pause automation when a subscription is not eligible.

The current seed creates one **Business Plan** at **$100/month**. Other code and copy refer to **Starter**, **Pulse**, a **14-day free trial**, and a $199 Pulse demo plan. Those are implementation artifacts, not a confirmed pricing strategy. Do not add more price-dependent behavior until plan names, prices, trial policy, respondent limits, and service inclusions are explicitly approved.

Subscriptions currently belong to one manager user even though the intended customer account is the company. Ownership transfer, multiple billing administrators, and manager-departure behavior are not defined. Do not assume a user-bound subscription is the final commercial account model.

## Measures of success

### Activation

- time from company creation to roster ready;
- time to first wave dispatched;
- time to first completed response, which proves workflow activation;
- percentage of new companies reaching first completed response;
- time and percentage reaching a defined minimum reliable sample, which marks analytical activation.

### Product value

- invitation delivery and response rate;
- survey completion and autosave recovery;
- number of organizations completing a baseline;
- recurring wave completion;
- trend and comparison usage;
- percentage of findings that lead to a recorded leadership action;
- improvement in prioritized gaps across later waves.

### Commercial

- baseline-to-paid conversion;
- baseline-to-Pulse conversion;
- trial-to-paid conversion, if a trial remains part of the model;
- logo and revenue retention;
- recurring-revenue expansion;
- advisory attach rate;
- time from first diagnosis to renewal or expansion.

### Trust and quality

- invalid or incomplete response rate;
- cohort suppression incidents;
- cross-tenant access defects;
- methodology changes without version evidence;
- respondent questions or complaints about data use.

## Development priority test

Before approving substantial product work, ask:

1. Does this reduce time to trustworthy first data?
2. Does it improve diagnostic validity or interpretability?
3. Does it help a leader choose or evaluate an action?
4. Does it make recurring measurement more valuable?
5. Does it strengthen a paid entitlement or expansion path?
6. Does it preserve employee trust, tenant isolation, and methodology integrity?

Work that answers none of these should not outrank work that advances the core loop.

## Current product horizon

The repository already implements the operational spine:

- company registration and roster management;
- role-based experiences;
- a normalized, versioned internal survey engine;
- autosave, validation, and response capture;
- full and recurring survey waves;
- invitation jobs, scheduling, logs, and recovery behavior;
- company, department, team, and wave analytics;
- WorkFit-admin survey publication and customer activation monitoring;
- Stripe subscription management.

The next product horizon is to make that spine commercially and managerially complete:

1. confirm the buyer, initial customer segment, packaging, prices, and trial policy;
2. document the methodology evidence, interpretation limits, and approved claims;
3. make every result interpretable and action-oriented;
4. define the respondent privacy/confidentiality promise and enforce it;
5. make sample size and cohort reliability visible;
6. connect findings to recommended and recorded actions;
7. make wave-over-wave learning the center of the retained customer experience;
8. define where WorkFit advisory service enters the product journey;
9. add enterprise capabilities only when they reinforce a confirmed sales motion.

## Product-owner decisions still required

| Decision | Why it matters | Current working assumption |
| --- | --- | --- |
| Commercial identity | Controls positioning, onboarding, sales, and which workflows remain WorkFit-operated. | Hybrid software plus diagnostic/advisory services. |
| Initial customer segment | Controls messaging, defaults, integrations, support, and pricing. | Small-to-mid-sized organizations with a leader accountable for culture and retention. |
| Packaging and pricing | Controls entitlements and every billing-facing surface. | Baseline offer, Starter, Pulse, and custom Enterprise/Advisory. Exact prices unconfirmed. |
| Trial policy | Current marketing promises 14 days, but product behavior is not defined here. | Keep the claim provisional until eligibility, start, expiry, and conversion behavior are approved. |
| Privacy promise | Controls respondent trust, reporting thresholds, and data design. | Identifiable secure assignments; no anonymity claim. |
| Survey ownership | Controls whether Empulse competes as a methodology product or a generic survey builder. | WorkFit owns and publishes the shared instrument; customers operate audiences and waves. |
| Interpretation service | Controls whether action guidance is software, human service, or both. | Software explains evidence; WorkFit advisory can provide higher-touch interpretation and action planning. |
| Methodology evidence | Controls which scientific, predictive, benchmark, and outcome claims the product may make. | The code implements approved-looking formulas, but repository documentation does not establish their validation. |
| Commercial account owner | Controls transfer, renewal, billing administration, and continuity when a manager leaves. | The company is the intended account; one manager user currently owns the Cashier subscription. |

When these decisions are confirmed, replace the assumptions in this table with dated decisions and update plan configuration, customer-facing copy, sales materials, and tests together.
