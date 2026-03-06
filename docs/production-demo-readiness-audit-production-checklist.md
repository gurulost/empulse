# production demo readiness audit Checklist

Source of truth checklist for a large/intense task.

## Metadata
- Created: 2026-03-05T15:27:16
- Last Updated: 2026-03-05T15:27:16
- Workspace: /Users/davedixon/Downloads/empulse code
- Checklist Doc: /Users/davedixon/Downloads/empulse code/docs/production-demo-readiness-audit-production-checklist.md

## Scope
- [x] Q-000 [status:verified] Capture explicit scope, constraints, and success criteria.
  - Evidence: User requested a full production/demo readiness report covering every access level, empty states, and no-blank-page behavior.

## Sign-off Gate
- [x] G-001 [status:verified] All queued work, findings, fixes, and validations are complete.
- [x] G-002 [status:accepted_risk] All findings are resolved or marked `accepted_risk` with rationale and owner.
  - Rationale: Audit-only turn; findings documented for follow-up implementation.
  - Owner: Product + engineering
- [x] G-003 [status:verified] Required validation suite has been rerun on the final code state.
- [x] G-004 [status:verified] Residual risks and follow-ups are documented.

## Rerun Matrix
- [x] G-010 [status:verified] If code changes after any checked `V-*`, reset affected validation items to unchecked.
  - Evidence: No production code changes were made during this audit turn.
- [x] G-011 [status:verified] Final sign-off only after a full validation pass completed after the last code edit.

## Audit Queue
- [x] Q-001 [status:verified] Create checklist and baseline scope.
- [x] Q-002 [status:verified] Complete discovery/audit of impacted systems.
  - Evidence: Reviewed routes, key controllers, views, Vue components, deployment files, mail templates, and test coverage.
- [x] Q-003 [status:verified] Implement required changes.
  - Evidence: Not applicable for this audit-only request; no code changes requested.
- [x] Q-004 [status:verified] Expand or update automated tests.
  - Evidence: Not applicable for this audit-only request; test gaps documented as findings.
- [x] Q-005 [status:verified] Run full validation suite.
  - Evidence: `php artisan test`; `npm run build`
- [x] Q-006 [status:verified] Final code-quality pass and sign-off review.
  - Evidence: Findings consolidated with file-level references for final report.

## Findings Log
- [x] F-001 [status:accepted_risk] [P1] [confidence:0.96] Survey builder is not production-ready and still hard-fails or exposes "coming soon" behavior in empty/no-data states.
  - Evidence: `app/Http/Controllers/SurveyBuilderController.php:22-31`; `resources/js/components/builder/SurveyBuilder.vue:72-84`; `resources/js/components/builder/QuestionEditor.vue:17-93`
  - Owner: product + engineering
  - Linked Fix: P-001
- [x] F-002 [status:accepted_risk] [P1] [confidence:0.95] Billing and pricing surfaces are not demo-ready; they still contain placeholder copy and a parallel legacy Stripe flow.
  - Evidence: `resources/views/stripe/plans.blade.php:17-78`; `app/Http/Controllers/PlanController.php:22-38`; `app/Http/Controllers/PaymentController.php:13-61`; `resources/views/subscription/subscription_success.blade.php:1-20`
  - Owner: product + engineering
  - Linked Fix: P-001
- [x] F-003 [status:accepted_risk] [P1] [confidence:0.94] Survey management remains wired to a placeholder/legacy preview instead of the normalized instrument and wave model.
  - Evidence: `resources/views/surveys/manage.blade.php:11-27`
  - Owner: product + engineering
  - Linked Fix: P-001
- [x] F-004 [status:accepted_risk] [P1] [confidence:0.93] Analytics dashboard currently mislabels/miswires the temperature card and renders misleading empty-state output.
  - Evidence: `resources/js/components/analytics/AnalyticsDashboard.vue:87-105`; `resources/js/components/dashboard/TemperatureGauge.vue:2-35`; `app/Services/SurveyAnalyticsService.php:53-70`
  - Owner: product + engineering
  - Linked Fix: P-001
- [x] F-005 [status:accepted_risk] [P1] [confidence:0.92] Survey wave management does not guard no-company managers and can surface a broken create flow despite a non-null `company_id` schema.
  - Evidence: `app/Http/Controllers/SurveyWaveController.php:19-39`; `app/Http/Controllers/SurveyWaveController.php:90-109`; `resources/views/survey_waves/index.blade.php:28-92`; `database/migrations/2025_02_18_010000_create_survey_waves_table.php`
  - Owner: product + engineering
  - Linked Fix: P-001
- [x] F-006 [status:accepted_risk] [P1] [confidence:0.94] Invitation email content and scheduling are not production-grade; copy is placeholder/outdated and the legacy monthly command still emails every user.
  - Evidence: `resources/views/admin-msg.blade.php:67-101`; `resources/views/coworkersMsg.blade.php:11-18`; `app/Console/Commands/SendLink.php:41-59`; `routes/console.php:5-8`; `app/Services/EmailService.php:74-82`
  - Owner: product + engineering
  - Linked Fix: P-001
- [x] F-007 [status:accepted_risk] [P1] [confidence:0.97] Deployment/runtime hardening is incomplete for a production app: placeholder Dockerfile, `php artisan serve` web process, no declared worker/scheduler processes, and local-only env defaults.
  - Evidence: `Procfile:1-2`; `Dockerfile:1`; `.env.example:18-23`
  - Owner: engineering + ops
  - Linked Fix: P-001
- [x] F-008 [status:accepted_risk] [P2] [confidence:0.91] Frontend quality gates are missing; the repo has no JS test/lint/typecheck scripts and no checked-in CI workflow.
  - Evidence: `package.json:4-8`; `.github/workflows` absent
  - Owner: engineering
  - Linked Fix: P-001
- [x] F-009 [status:accepted_risk] [P2] [confidence:0.88] Role-specific demo experience is thin: chiefs/team leads share the same generic dashboard and employees only see a single current assignment card.
  - Evidence: `resources/js/components/layout/AppSidebar.vue:117-123`; `resources/views/employee/dashboard.blade.php:14-41`
  - Owner: product + engineering
  - Linked Fix: P-001
- [x] F-010 [status:accepted_risk] [P2] [confidence:0.89] Reports UI lacks wave/date controls even though the backend supports wave-specific comparisons, limiting demo depth.
  - Evidence: `resources/js/components/reports/ReportsDashboard.vue:73-121`; `app/Http/Controllers/ReportsApiController.php:31-67`
  - Owner: product + engineering
  - Linked Fix: P-001

## Fix Log
- [x] P-001 [status:verified] No code fixes were applied in this turn because the request was for a readiness report; all findings were documented for follow-up implementation.
  - Addresses: F-001, F-002, F-003, F-004, F-005, F-006, F-007, F-008, F-009, F-010
  - Evidence: Audit-only completion; final report will prioritize remediation work.

## Validation Log
- [x] V-001 [status:accepted_risk] `npm run check:types`
  - Evidence: 2026-03-05 15:33 ET outcome: not run; no typecheck script exists in `package.json`.
- [x] V-002 [status:accepted_risk] `npm run lint`
  - Evidence: 2026-03-05 15:33 ET outcome: not run; no lint script exists in `package.json`.
- [x] V-003 [status:verified] `php artisan test`
  - Evidence: 2026-03-05 15:33 ET pass; 54 tests passed, 196 assertions.
- [x] V-004 [status:verified] `npm run build`
  - Evidence: 2026-03-05 15:33 ET pass; Vite production bundle built successfully.

## Residual Risks
- [x] R-001 [status:accepted_risk] Shipping without the P1 fixes would still leave demo-visible rough edges and operational risks despite a green automated suite.
  - Rationale: Current tests cover a lot of backend behavior, but major gaps remain in builder completeness, pricing polish, email experience, deployment shape, and no-data onboarding.
  - Owner: product + engineering + ops
  - Follow-up trigger/date: Before any external production demo or customer-facing rollout.

## Change Log
- 2026-03-05T15:27:16: Checklist initialized.
- 2026-03-05T15:33:00: Discovery completed; tests and production build passed; findings logged for final audit report.
