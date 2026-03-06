# production demo readiness implementation Checklist

Source of truth checklist for a large/intense task.

## Metadata
- Created: 2026-03-05T16:58:32
- Last Updated: 2026-03-05T16:58:32
- Workspace: /Users/davedixon/Downloads/empulse code
- Checklist Doc: /Users/davedixon/Downloads/empulse code/docs/production-demo-readiness-implementation-production-checklist.md

## Scope
- [x] Q-000 [status:verified] Capture explicit scope, constraints, and success criteria.
  - Evidence: Production/demo readiness implementation completed across billing, wave dispatch, no-data UX, builder hardening, and delivery tooling.

## Sign-off Gate
- [x] G-001 [status:verified] All queued work, findings, fixes, and validations are complete.
- [x] G-002 [status:verified] All findings are resolved or marked `accepted_risk` with rationale and owner.
- [x] G-003 [status:verified] Required validation suite has been rerun on the final code state.
- [x] G-004 [status:verified] Residual risks and follow-ups are documented.

## Rerun Matrix
- [x] G-010 [status:verified] If code changes after any checked `V-*`, reset affected validation items to unchecked.
  - Evidence: Backend validations were rerun after the Cashier schema/model compatibility fixes landed.
- [x] G-011 [status:verified] Final sign-off only after a full validation pass completed after the last code edit.

## Audit Queue
- [x] Q-001 [status:verified] Create checklist and baseline scope.
- [x] Q-002 [status:verified] Complete discovery/audit of impacted systems.
- [x] Q-003 [status:verified] Implement required changes.
- [x] Q-004 [status:verified] Expand or update automated tests.
- [x] Q-005 [status:verified] Run full validation suite.
- [x] Q-006 [status:verified] Final code-quality pass and sign-off review.

## Findings Log
- [x] F-001 [status:verified] [P1] [confidence:0.98] Stripe webhook overrides bypassed Cashier’s subscription sync handlers.
  - Evidence: [app/Http/Controllers/StripeWebhookController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/StripeWebhookController.php) returned `successMethod()` directly for subscription events, which skipped Cashier’s parent handlers.
  - Owner: Codex
  - Linked Fix: P-001
- [x] F-002 [status:verified] [P1] [confidence:0.95] The local `subscriptions` schema was not aligned with Cashier v15 and would break real webhook inserts.
  - Evidence: `php artisan test` failed on `BillingFlowTest` with `subscriptions.type` missing, then `subscriptions.name` NOT NULL after parent webhook delegation was restored.
  - Owner: Codex
  - Linked Fix: P-002
- [x] F-003 [status:verified] [P2] [confidence:0.93] Billing and checkout flows still had brittle failure paths for existing subscriptions, missing plan price ids, and portal/payment-method errors.
  - Evidence: [app/Http/Controllers/PlanController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/PlanController.php), [app/Http/Controllers/BillingController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/BillingController.php), and related Blade views lacked graceful guards and error rendering.
  - Owner: Codex
  - Linked Fix: P-003
- [x] F-004 [status:verified] [P2] [confidence:0.92] Wave creation and builder editing still allowed invalid or misleading states.
  - Evidence: Draft survey versions could be targeted for waves, full waves could be given drip cadences, live builder questions could still be edited in the UI, and the builder omitted imported item types like `dropdown` / `text_short`.
  - Owner: Codex
  - Linked Fix: P-004
- [x] F-005 [status:verified] [P3] [confidence:0.90] The new lint gate produced high warning noise and was not useful as a quality signal.
  - Evidence: Initial `npm run lint` surfaced thousands of Vue formatting warnings before the config was tightened.
  - Owner: Codex
  - Linked Fix: P-005

## Fix Log
- [x] P-001 [status:verified] Restore Cashier parent webhook handling before company tariff sync.
  - Addresses: F-001
  - Evidence: [app/Http/Controllers/StripeWebhookController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/StripeWebhookController.php) now delegates `customer.subscription.*` events to `parent::...()` and then performs tariff sync.
- [x] P-002 [status:verified] Align the app’s subscription persistence with Cashier while preserving legacy schema compatibility.
  - Addresses: F-002
  - Evidence: [database/migrations/2026_03_05_010000_align_subscriptions_table_with_cashier.php](/Users/davedixon/Downloads/empulse%20code/database/migrations/2026_03_05_010000_align_subscriptions_table_with_cashier.php), [app/Models/Subscription.php](/Users/davedixon/Downloads/empulse%20code/app/Models/Subscription.php), and [app/Providers/AppServiceProvider.php](/Users/davedixon/Downloads/empulse%20code/app/Providers/AppServiceProvider.php).
- [x] P-003 [status:verified] Harden billing controllers and pages for portal/payment-method/setup failures and existing subscriptions.
  - Addresses: F-003
  - Evidence: [app/Http/Controllers/BillingController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/BillingController.php), [app/Http/Controllers/PlanController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/PlanController.php), [resources/views/billing/index.blade.php](/Users/davedixon/Downloads/empulse%20code/resources/views/billing/index.blade.php), [resources/views/stripe/plans.blade.php](/Users/davedixon/Downloads/empulse%20code/resources/views/stripe/plans.blade.php), [resources/views/subscription/subscription.blade.php](/Users/davedixon/Downloads/empulse%20code/resources/views/subscription/subscription.blade.php).
- [x] P-004 [status:verified] Tighten survey wave and builder guards, queue-time checks, and read-only behavior.
  - Addresses: F-004
  - Evidence: [app/Http/Controllers/SurveyWaveController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/SurveyWaveController.php), [app/Jobs/ProcessSurveyWave.php](/Users/davedixon/Downloads/empulse%20code/app/Jobs/ProcessSurveyWave.php), [app/Jobs/SendSurveyAssignmentInvitation.php](/Users/davedixon/Downloads/empulse%20code/app/Jobs/SendSurveyAssignmentInvitation.php), [app/Http/Controllers/SurveyBuilderController.php](/Users/davedixon/Downloads/empulse%20code/app/Http/Controllers/SurveyBuilderController.php), [resources/js/components/builder/QuestionEditor.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/builder/QuestionEditor.vue), [resources/js/components/builder/LogicEditor.vue](/Users/davedixon/Downloads/empulse%20code/resources/js/components/builder/LogicEditor.vue).
- [x] P-005 [status:verified] Reduce Vue lint noise to an actionable essential rule set.
  - Addresses: F-005
  - Evidence: [eslint.config.js](/Users/davedixon/Downloads/empulse%20code/eslint.config.js) now uses `flat/essential`; `npm run lint` exits cleanly.

## Validation Log
- [x] V-001 [status:verified] `npm run lint`
  - Evidence: 2026-03-05 18:12 EST + pass.
- [x] V-002 [status:verified] `npm run build`
  - Evidence: 2026-03-05 18:12 EST + pass.
- [x] V-003 [status:verified] `php artisan test`
  - Evidence: 2026-03-05 18:13 EST + pass (65 tests, 231 assertions).
- [x] V-004 [status:verified] `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8001 npm run test:e2e`
  - Evidence: 2026-03-05 18:14 EST + pass (6 role smoke tests).

## Residual Risks
- [x] R-001 [status:accepted_risk] Public build artifacts and Playwright output remain part of the local worktree and should be reviewed before commit.
  - Rationale: `public/build/*` is regenerated by the verified production build and `test-results/` is a local smoke-test artifact.
  - Owner: Repo maintainer
  - Follow-up trigger/date: Review before commit/push on 2026-03-05.

## Change Log
- 2026-03-05T16:58:32: Checklist initialized.
- 2026-03-05T18:14:00: Post-implementation audit completed, regressions fixed, and final validation suite passed.
