# remaining gaps hardening Checklist

Source of truth checklist for a large/intense task.

## Metadata
- Created: 2026-03-06T01:34:08
- Last Updated: 2026-03-06T01:58:48 EST
- Workspace: /Users/davedixon/Downloads/empulse code
- Checklist Doc: /Users/davedixon/Downloads/empulse code/docs/remaining-gaps-hardening-production-checklist.md

## Scope
- [x] Q-000 [status:verified] Capture explicit scope, constraints, and success criteria.
  - Evidence: Implement the remaining production-readiness gaps identified in the current-state audit: truthful invitation delivery, production web runtime alignment, lossless builder logic editing, complete builder option editing, editable survey waves, and Workfit Admin search/role-polish fixes. Success requires updated automated coverage and a rerun validation pass on the final code state.

## Sign-off Gate
- [x] G-001 [status:verified] All queued work, findings, fixes, and validations are complete.
- [x] G-002 [status:verified] All findings are resolved or marked `accepted_risk` with rationale and owner.
- [x] G-003 [status:verified] Required validation suite has been rerun on the final code state.
- [x] G-004 [status:verified] Residual risks and follow-ups are documented.

## Rerun Matrix
- [x] G-010 [status:verified] If code changes after any checked `V-*`, reset affected validation items to unchecked.
  - Evidence: No code edits were made after the final validation pass.
- [x] G-011 [status:verified] Final sign-off only after a full validation pass completed after the last code edit.
  - Evidence: `php artisan test`, `npm run lint`, `npm run build`, and `npm run test:e2e` were all rerun after the last implementation edits.

## Audit Queue
- [x] Q-001 [status:verified] Create checklist and baseline scope.
- [x] Q-002 [status:verified] Complete discovery/audit of impacted systems.
- [x] Q-003 [status:verified] Implement required changes.
  - Evidence: Updated the mail service, builder editor components, wave management controller/routes/UI, Procfile/docs, and Workfit Admin user management.
- [x] Q-004 [status:verified] Expand or update automated tests.
  - Evidence: Added feature coverage in `SurveyBuilderTest`, `WorkfitAdminControllerTest`, `SurveyWaveTest`, `AdminRefactorTest`, and an admin role-label Playwright smoke test.
- [x] Q-005 [status:verified] Run full validation suite.
  - Evidence: `php artisan test`, `npm run lint`, `npm run build`, and `npm run test:e2e` all passed on the final code state.
- [x] Q-006 [status:verified] Final code-quality pass and sign-off review.
  - Evidence: Post-validation audit completed across the changed files, additional controller/builder edge cases were fixed, and the full validation suite was rerun successfully.

## Findings Log
- [x] F-001 [status:verified] [P1] [confidence:0.98] Invitation delivery can be marked successful even when Brevo is not configured.
  - Evidence: [app/Services/EmailService.php](/Users/davedixon/Downloads/empulse%20code/app/Services/EmailService.php#L15) and [app/Services/EmailService.php](/Users/davedixon/Downloads/empulse%20code/app/Services/EmailService.php#L86) return `['status' => 200]` when `services.brevo.key` is missing outside the invitation job, causing [app/Jobs/SendSurveyAssignmentInvitation.php](/Users/davedixon/Downloads/empulse%20code/app/Jobs/SendSurveyAssignmentInvitation.php) to mark assignments as sent.
  - Owner: Codex
  - Linked Fix: P-001
- [x] F-002 [status:verified] [P1] [confidence:0.95] The checked-in Procfile still advertises Laravel’s development server as the production web runtime.
  - Evidence: [Procfile](/Users/davedixon/Downloads/empulse%20code/Procfile#L2) sets `web: php artisan serve --host=0.0.0.0 --port=${PORT:-5000}` while the repo docs describe an Apache/PHP production runtime.
  - Owner: Codex
  - Linked Fix: P-002
- [x] F-003 [status:verified] [P1] [confidence:0.95] Builder display logic editing is lossy and can rewrite imported logic into a different rule shape.
  - Evidence: [resources/js/components/builder/LogicEditor.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/builder/LogicEditor.vue#L54) only preserves the first `equals_any` value and [resources/js/components/builder/LogicEditor.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/builder/LogicEditor.vue#L77) drops `operator` / `combinator`.
  - Owner: Codex
  - Linked Fix: P-003
- [x] F-004 [status:verified] [P2] [confidence:0.93] Builder option editing is still incomplete for dropdowns and option metadata.
  - Evidence: [resources/js/components/builder/QuestionEditor.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/builder/QuestionEditor.vue#L65) excludes `dropdown` from the options editor and the UI does not expose `exclusive` or `freetext_placeholder` metadata that the runtime already understands.
  - Owner: Codex
  - Linked Fix: P-004
- [x] F-005 [status:verified] [P2] [confidence:0.92] Survey waves are not editable after creation.
  - Evidence: [routes/web.php](/Users/davedixon/Downloads/empulse%20code/routes/web.php#L151) only exposes create, status toggle, and dispatch routes for waves.
  - Owner: Codex
  - Linked Fix: P-005
- [x] F-006 [status:verified] [P3] [confidence:0.90] Workfit Admin user management still has search scoping and role-label polish bugs.
  - Evidence: [app/Http/Controllers/WorkfitAdminController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/WorkfitAdminController.php#L51) uses an ungrouped `orWhere`, and [resources/js/components/admin/UserList.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/admin/UserList.vue#L82) omits role `2`.
  - Owner: Codex
  - Linked Fix: P-006

## Fix Log
- [x] P-001 [status:verified] Make invitation delivery status truthful when the mail provider is unavailable or fails.
  - Addresses: F-001
  - Evidence: [app/Services/EmailService.php](/Users/davedixon/Downloads/empulse%20code/app/Services/EmailService.php), [app/Jobs/SendSurveyAssignmentInvitation.php](/Users/davedixon/Downloads/empulse%20code/app/Jobs/SendSurveyAssignmentInvitation.php), and [tests/Feature/AdminRefactorTest.php](/Users/davedixon/Downloads/empulse%20code/tests/Feature/AdminRefactorTest.php)
- [x] P-002 [status:verified] Replace the Procfile web process with the supported production Apache/PHP runtime and align docs.
  - Addresses: F-002
  - Evidence: [Procfile](/Users/davedixon/Downloads/empulse%20code/Procfile), [README.md](/Users/davedixon/Downloads/empulse%20code/README.md), and [docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md](/Users/davedixon/Downloads/empulse%20code/docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md)
- [x] P-003 [status:verified] Rework the builder logic editor to preserve combinators and full `equals_any` arrays.
  - Addresses: F-003
  - Evidence: [resources/js/components/builder/LogicEditor.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/builder/LogicEditor.vue) and [tests/Feature/SurveyBuilderTest.php](/Users/davedixon/Downloads/empulse%20code/tests/Feature/SurveyBuilderTest.php)
- [x] P-004 [status:verified] Complete builder option editing for dropdowns and option metadata.
  - Addresses: F-004
  - Evidence: [resources/js/components/builder/QuestionEditor.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/builder/QuestionEditor.vue) and [tests/Feature/SurveyBuilderTest.php](/Users/davedixon/Downloads/empulse%20code/tests/Feature/SurveyBuilderTest.php)
- [x] P-005 [status:verified] Add editable wave management for existing waves.
  - Addresses: F-005
  - Evidence: [app/Http/Controllers/SurveyWaveController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/SurveyWaveController.php), [resources/views/survey_waves/index.blade.php](/Users/davedixon/Downloads/empulse%20code/resources/views/survey_waves/index.blade.php), [routes/web.php](/Users/davedixon/Downloads/empulse%20code/routes/web.php), and [tests/Feature/SurveyWaveTest.php](/Users/davedixon/Downloads/empulse%20code/tests/Feature/SurveyWaveTest.php)
- [x] P-006 [status:verified] Fix Workfit Admin user filtering and chief role rendering.
  - Addresses: F-006
  - Evidence: [app/Http/Controllers/WorkfitAdminController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/WorkfitAdminController.php), [resources/js/components/admin/UserList.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/admin/UserList.vue), [tests/Feature/WorkfitAdminControllerTest.php](/Users/davedixon/Downloads/empulse%20code/tests/Feature/WorkfitAdminControllerTest.php), and [tests/e2e/role-smoke.spec.js](/Users/davedixon/Downloads/empulse%20code/tests/e2e/role-smoke.spec.js)

## Validation Log
- [x] V-001 [status:verified] `php artisan test`
  - Evidence: 2026-03-06 01:58 EST - passed, 80 tests / 283 assertions.
- [x] V-002 [status:verified] `npm run lint`
  - Evidence: 2026-03-06 01:58 EST - passed.
- [x] V-003 [status:verified] `npm run build`
  - Evidence: 2026-03-06 01:58 EST - passed.
- [x] V-004 [status:verified] `npm run test:e2e`
  - Evidence: 2026-03-06 01:58 EST - passed, 7 Playwright smoke tests.

## Residual Risks
- [x] R-001 [status:accepted_risk] Real Stripe and Brevo staging verification still depends on deployment credentials that are not available in the local workspace.
  - Rationale: The code can be hardened and locally validated here, but real provider callbacks and delivery outcomes still require staging or production integration credentials.
  - Owner: Deploy operator
  - Follow-up trigger/date: Validate after the next staging deploy.

## Change Log
- 2026-03-06T01:34:08: Checklist initialized.
- 2026-03-06T01:46:00: Scope, findings, and fix plan updated for remaining-gap implementation pass.
- 2026-03-06T01:49:16 EST: All planned fixes implemented, automated coverage expanded, final validation rerun, and residual staging-only risk accepted for external provider verification.
- 2026-03-06T01:51:46 EST: Tightened the non-premium drip-wave coverage to assert the actual cadence gate, then reran the full validation suite and Playwright smoke tests on the final state.
- 2026-03-06T01:58:48 EST: Fixed wave status/dispatch edge cases, hardened builder handling for generated option sources and stale type-specific metadata, then reran all automated validations.
