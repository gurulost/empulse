# Empulse Respondent Data Promise

Policy version: `2026-07-27.1`

Status: approved engineering default for the pre-deployment release candidate. Product/privacy counsel must review this policy and issue a new version for any substantive change before a customer launch.

## Plain-language promise

Empulse is not an anonymous survey system. It uses a respondent’s identity to secure the assigned survey, save progress, prevent duplicate submissions, and preserve the correct historical cohort.

The purpose of collection is to help an organization understand patterns in work experience and record leadership follow-through. Customer leaders receive aggregate results only when sample-size, completion, hierarchy, and suppression rules are met. Normal customer roles cannot retrieve individual answer records.

Authorized WorkFit privacy operators may access limited records only for verified support, legal, security, or data-rights work. Those operations require an explicit capability and are recorded in the tamper-evident audit trail.

Draft answers are removed after submission and are eligible for removal 30 days after an assignment expires or is revoked. Delivery and onboarding operational events are retained for 400 days. Submitted analytical evidence is retained for up to seven years unless a verified legal or contractual requirement changes that period. Audit and billing records use their applicable legal schedules and are not deleted by the routine retention job.

Respondents may request access, correction, or erasure review through the designated privacy contact. Identity must be verified before execution. An active legal hold blocks erasure. Approved erasure removes direct identity and non-analytical/free-text answers while retaining only pseudonymized analytical evidence needed to reproduce historical aggregates.

## Enforced controls

- The current policy is returned with the survey definition and must be acknowledged before answers can be submitted.
- The acknowledgment stores the policy version and deterministic content hash.
- Policy changes require a new version; old responses retain the acknowledged version.
- Customer reporting fails closed below the configured sample and completion thresholds.
- Raw individual answers are not a customer capability.
- Data-subject requests follow requested → identity verified → approved → completed states.
- Retention is dry-run first and execution requires the exact reviewed plan hash.
- Company-wide or subject-specific legal holds exclude protected records.
- Erasure, export, correction, legal-hold, and retention actions are attributable.

## Initial reporting thresholds

- Company results: at least 5 valid respondents.
- Department/team results: at least 7 valid respondents.
- Completion rate: at least 40%.
- Complementary suppression: required where displayed totals or adjacent cohorts could expose a suppressed subgroup.

These are conservative product defaults, not a claim of psychometric validation. The methodology dossier owns any stricter metric-specific reliability threshold.
