# Analytics EXPLAIN Checklist

Use this checklist before shipping analytics changes or onboarding larger tenants.

## 1) Preconditions

- Apply latest migrations: `php artisan migrate --force`
- Ensure planner stats are fresh on production/staging DB:
  - PostgreSQL: `ANALYZE survey_responses; ANALYZE survey_answers; ANALYZE survey_assignments;`
- Pick a realistic tenant and wave selector:
  - Highest-volume tenant: run against the company with the most submitted responses.
  - Wave selector format: `wave:9`, `version:2`, or `label:Dec 2025 Pulse`.

## 2) Run the built-in EXPLAIN command

- Baseline (all core analytics queries):
  - `php artisan analytics:explain 1`
- Wave-filter profile:
  - `php artisan analytics:explain 1 --wave=wave:8`
- Planner-only mode (no runtime analyze):
  - `php artisan analytics:explain 1 --wave=wave:8 --no-analyze`

## 3) What to look for

- `latest_response_ids`
  - Should avoid correlated `EXISTS` subplans across assignments.
  - Red flag: repeated full scans on `survey_assignments` in subplans.
- `latest_response_ids_with_wave`
  - Should use single-pass join shape and keep total runtime low.
  - Red flag: very high rows removed by filter on `survey_responses` for selective wave filters.
- `answers_for_latest_responses`
  - Watch for full-table scans with large answer volumes.
  - Red flag: execution time grows superlinearly with tenant size.
- `legacy_wave_labels` / `legacy_version_ids`
  - Should be fast and scoped by company.
  - Red flag: scans return many rows due to missing wave metadata migration/backfill.

## 4) Performance budgets (starting point)

- `latest_response_ids`: < 150ms at 1M responses.
- `latest_response_ids_with_wave`: < 200ms at 1M responses.
- `answers_for_latest_responses`: < 250ms at 10M answers.

If any query exceeds budget, capture the EXPLAIN output and tune before release.

## 5) Current tuning already applied (February 6, 2026)

- Added analytics indexes on `survey_responses`, `survey_assignments`, and `survey_answers`.
- Refactored `SurveyAnalyticsService::latestResponseIdsForCompany()` to a join-based query (instead of nested `whereHas`/`EXISTS` wave subplans).
- Refactored legacy wave option lookups to direct joins on `users` for company scoping.

## 6) Remediation playbook

- Too many rows scanned in wave-filter query:
  - Prefer `wave:<id>` selectors over free-form labels.
  - Verify `survey_wave_id` is populated on responses/assignments.
- Assignment fallback still heavy:
  - Backfill missing response wave metadata from assignment rows.
  - Re-run `ANALYZE` after backfill.
- Answer fetch is hot:
  - Keep `response_id`-leading index on `survey_answers`.
  - Consider tenant-level materialized aggregates for dashboard cards.

## 7) Release gate

Do not ship analytics query changes without attaching:

- Command used (`analytics:explain` args)
- DB engine/version
- EXPLAIN output for all five profiled queries
- Any index or query-shape changes made in response
