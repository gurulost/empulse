# Agent Notes

## Current Phase
We are executing **Phase 1** of the survey overhaul: capture the full Qualtrics instrument inside our own schema. The following artifacts are now in place:

- Normalized schema covering `survey_versions`, `survey_pages`, `survey_sections`, `survey_items`, `survey_options`, `survey_option_sources`, and `survey_scale_presets`.
- Import command `php artisan survey:import {path} [--activate]` that ingests the JSON spec and optionally marks the version live. The importer stores scale presets, option metadata (including exclusivity, free-text placeholders), algorithmic/ISO option generators, and coding hints for analytics.
- Models for each new table so later phases (renderer, analytics, scheduling) can rely on Eloquent relationships.

## How to Use the Importer

1. Place the JSON instrument somewhere accessible (e.g., `storage/app/instruments/org_culture.json`).
2. Run `php artisan survey:import storage/app/instruments/org_culture.json --activate`.
3. The command wraps everything in a transaction. If `--activate` is passed, it deactivates any prior version and sets the new one live.

The importer preserves `instrument_id`, `version`, page/section sort order, attribute labels, coding hints, response metadata, and option source definitions.

## Phase 2 Design Notes (Renderer + Response Flow)

### Goals
1. Render the active survey version directly from the DB (pages → sections → items).
2. Support all item types from the JSON spec: sliders (with presets), short/long text, numeric inputs, dropdowns (static + ISO/calc-driven), single/multi-select with exclusive options, conditional display logic, and page-level metadata (attribute labels, end-of-survey message).
3. Capture responses with autosave, enforce validation, and submit final payloads for normalization + analytics.

### Architecture Overview
- **API**: `GET /survey/{token}/definition` returns normalized survey JSON for the assignment (resolved scale presets + options). `POST /survey/{token}/autosave` stores partial responses; `POST /survey/{token}` finalizes submission.
- **Front-end**: use Vue 3 via Vite to mount `<survey-app>` inside the existing Blade shell. Components: `SurveyApp` (pagination, progress), `SurveyPage`, `SurveySection`, `ItemRenderer` (delegates to Slider/Text/Select/MultiSelect/Number components). State holds responses keyed by QID plus validation errors and progress.
- **Display Logic**: evaluate simple `when` rules client-side (AND/OR with equals_any). Hidden items skip validation and aren’t sent on submit.
- **Autosave**: debounce changes and send to `/autosave`; add `draft_answers` JSON column on `survey_assignments` to persist partials.

### Response Persistence
- `survey_assignments`: add `survey_version_id` (if missing) + `draft_answers` column.
- `survey_responses`: include `survey_version_id`, `assignment_id`, `user_id`, `meta` (duration, device info).
- `survey_answers`: columns `question_key`, `value`, `value_numeric`, `metadata` (attribute label, coding hint, “series_role” like A/B/C for gap logic).

### Option & Scale Resolution
- PHP helper resolves presets (fetch `SurveyScalePreset` by key, merge with inline overrides) and option sources (ISO-3166 lists, years-of-service/years-since-degree generators, exclusive option flags).

### Validation Rules
- Sliders: enforce integer steps within range; treat as required unless instrument marks optional.
- Text/email: honor `format_hint` and optional `max_length`.
- Number fields: enforce min (>=0 for children counts) and integer type.
- Multi-select exclusive: selecting “None” clears others and vice versa.
- Items hidden by display logic skip validation/submission.

### Implementation Steps
1. ✅ Add migrations for assignment draft storage and response metadata columns (done in `2025_02_02_020000_update_survey_assignments_and_responses.php`, models updated accordingly).
2. ✅ Build server-side survey definition serializer + option/scale resolvers and expose via API route (`SurveyDefinitionService`, `SurveyOptionSourceResolver`, `/survey/{token}/definition`).
3. ✅ Implemented Vue renderer + autosave/submit endpoints (see `SurveyController`, `SurveyDefinitionService`, `SurveyOptionSourceResolver`, Vue components in `resources/js/components/survey`).
4. Phase 3 underway: first step is building analytics aggregations (gap, satisfaction, team culture) from `survey_answers`. The `SurveyAnalyticsService::workContentAttributesForUser` method now computes current/ideal/desire averages for every WCA attribute, `HomeController@index` passes the resulting `work_attributes` array to `home.blade.php`, and the dashboard now renders a “Work Content Gap Monitor” card using this data. Next up: port the remaining charts (gap bars, satisfaction indicator, temperature, team 2×2) to the new analytics service and remove legacy Qualtrics JSON usage.

## Phase 3 Design Notes (Analytics + Dashboards)

### Objectives
1. Compute all dashboard metrics (gap analysis, satisfaction indicator, temperature, team 2×2, team culture evaluation) from `survey_responses` + `survey_answers` instead of the legacy `qualtrics` table.
2. Support filtering by company / department / team / assignment wave and differentiate between “want vs. get” questions, team culture questions, and demographics.
3. Provide a service layer that returns pre-aggregated datasets for the existing Blade charts so we can swap them without rewriting the entire UI in one go.

### Proposed Architecture
- **SurveyAnalyticsService v2**
  - Input: company (and optional department/team) + time window + survey type (full vs drip).
  - Reads responses joined with `survey_assignments`, `users`, and `company_worker` to enrich metadata.
  - Uses per-item metadata (`metadata.series_role`, `attribute_label`, coding hints) to classify answers:
    - `series_role: current` (QID *_A) vs `ideal` (QID *_B) vs `desire` (QID *_C) to compute gap = ideal − current, desire backlog, etc.
    - Team culture items flagged via `coding_hint.polarity` for reversible scoring.
  - Returns structured aggregates:
    ```json
    {
      "gap": [{"attribute": "Building relationships", "current": 6.2, "ideal": 8.7, "gap": 2.5, "desire": 7.9}],
      "indicator_scores": {...},
      "team_culture": {...},
      "raw": [...]
    }
    ```
- **Caching**: heavy computations (gap per department) can be cached per assignment wave using `cache()->remember()` keyed by company+wave+filter.

### Implementation Steps
1. Define mapping metadata per question (store in `survey_items.metadata`, e.g., `{ "series_role": "current", "indicator": "relationships" }`). Extend importer to populate these from the JSON if present; for now, we can add a seeder/array mapping keyed by QID.
2. Update `SurveyAnalyticsService` to consume `survey_answers` instead of synthesizing values. Build helper methods:
   - `collectResponses(filters)` returns a collection grouped by user/team.
   - `calculateGapSet(responses)` iterates over attributes (A/B pairings) and computes `current`, `ideal`, `gap`, `desire`.
   - `calculateIndicatorScores(responses)` to drive the satisfaction indicator chart.
   - `calculateTeamCulture(responses)` implementing the Q7.2/7.3/7.4 weighted formula.
   - `calculateWeightedIndicatorSatisfaction()` + `temperatureIndex()` for the gauge + team 2×2 X-axis.
3. Update `HomeController` to request analytics via the new service (`$analytics = $analyticsService->forCompany($companyId, filters…)`) and pass the resulting datasets to `home.blade.php` instead of `$qualtrics` JSON.
4. Remove legacy Qualtrics table usage, backfill data migration in case we need to import historical results.

Keep expanding this section with formulas/results as we implement each metric.

Keep logging architectural decisions/phases here so the next agent can pick up immediately.

## Update – 2025-11-18
- Replaced `survey_instrument.json` with the complete spec the client provided (see latest chat). Validated parsing locally via `python3 -m json.tool` so the importer can now load the canonical version without `qid` typos or truncated sections.
- **Action still required:** run `php artisan survey:import survey_instrument.json --activate` the next time you have a PHP CLI to refresh the DB copy of the instrument. (Not possible in this sandbox.)
- Phase 3 next steps (highest priority):
  1. Extend `SurveyAnalyticsService` to cover the remaining dashboards: map team-culture QIDs (TC_*, WEL_*, IMPACT_*) with polarity adjustments, calculate weighted indicator satisfaction + temperature index per company/department/team, and expose aggregate data suitable for the gap chart, indicator list, and team 2×2.
  2. Update `HomeController` / `resources/views/home.blade.php` to stop reading `$qualtrics->data` once the new analytics endpoints exist; progressively replace the inline JS helpers with Blade/Vue components backed by `SurveyAnalyticsService`.
  3. After analytics are in place, continue with Phase 4 tasks (scheduler/autosend, admin tooling, billing hooks) per the plan above.

Document any new mappings (QID → indicator/series_role/polarity) inside this file or `config/survey.php` so downstream agents know exactly how analytics derive from the instrument.

### Sub-update – Team Culture & Impact wiring
- Added team culture and impact mappings to `config/survey.php` so analytics can distinguish positive vs negative culture items and the impact-on-society triplets.
- `SurveyAnalyticsService::workContentAnalyticsForUser()` now also returns `team_culture` (overall score + breakdown) and `impact` aggregates, and `HomeController@index` passes the derived arrays to the dashboard view. The Blade template still needs to consume these datasets (replace the legacy Qualtrics-driven charts) in a follow-up step.
- Remaining for Phase 3: wire the new analytics outputs into `resources/views/home.blade.php` (or Vue widgets), remove the Qualtrics JS, and add weighted indicator + temperature/2×2 calculations per the roadmap.

### Sub-update – Dashboard cards (2025-11-18 later pass)
- `resources/views/home.blade.php` now renders two new analytics-driven cards: **Team Culture Pulse** (shows positive vs negative averages, net score, and top drivers) and **Impact on Society Snapshot** (current impact vs importance vs desire). Both consume the new data from `SurveyAnalyticsService`, eliminating the legacy Qualtrics dependence for those sections.
- `HomeController@index` already passes `team_culture` and `impact_series`; no JS changes yet, but these sections confirm we can power dashboard widgets directly from our own schema.
- Remaining high-priority tasks for Phase 3:
  1. Replace the old Qualtrics-based Chart.js code for the gap report, satisfaction indicator, and team 2×2 with equivalents sourced from `SurveyAnalyticsService`.
  2. Extend the analytics service to compute weighted indicator satisfaction (for the temperature gauge + 2×2 X-axis) and the team culture evaluation formula described in the product notes (Q7.2/7.3/7.4 weighting).
  3. Introduce filters (company/department/team, date range) once the service returns per-cohort aggregates, then begin Phase 4 automation/billing enhancements.

### Sub-update – Dashboard analytics plumbing (2025-11-18, later)
- `SurveyAnalyticsService` now exposes `companyDashboardAnalytics($companyId)` which aggregates the latest response per employee (current/ideal/desire, indicator averages, team culture balance, impact, gap chart data, and a basic team scatter dataset derived from employee departments/teams). This replaces the old ad-hoc query in `workContentAnalyticsForUser`, so controllers simply call the service for a company-wide payload.
- `team_scatter` currently returns points for company aggregate plus any departments/teams that have responses (indicator average on X, culture net on Y). This dataset isn’t rendered yet—next step is to wire it into the dashboard and remove the legacy Qualtrics-driven Chart.js bubble chart.
- `gap_chart` (top 10 unmet attributes) is ready for the gap report widget; once the Blade/JS gets updated we can drop the Qualtrics JSON entirely.

### Sub-update – Dashboard UI wiring (2025-11-18, latest)
- `HomeController@index` now passes the new `gap_chart` and `team_scatter` arrays from `SurveyAnalyticsService`.
- Added two Blade cards in `resources/views/home.blade.php`:
  1. **Top Unmet Work Content Needs** – renders `gap_chart` as a table so we no longer depend on the Qualtrics-driven Chart.js bar.
  2. **Team Satisfaction vs Culture Map** – shows the upcoming 2×2 data in tabular form (label, level, count, indicator, culture) to prove out the schema-driven dataset before re-implementing the chart.
- Legacy Chart.js blocks (gapReport canvas, Qualtrics AJAX) have now been fully removed from `home.blade.php`; the only remaining `<script>` tags at the bottom load our standard JS bundles. Use the new datasets when reintroducing charts.
- Remaining tasks:
  - Delete/rewrite the Qualtrics JavaScript and modals for gap report / team bubble chart, replacing them with lightweight Alpine/Vue widgets or simple server-rendered components fed by `gap_chart` and `team_scatter`.
  - Implement the weighted indicator calculation (X-axis) + team culture evaluation (Y-axis) exactly as per the product formula, then hook the values into a real chart component.
  - Once these charts are done, proceed to filter controls and Phase 4 automation/billing tasks noted earlier.

### Sub-update – Weighted indicator & culture eval plumbing
- `SurveyAnalyticsService::companyDashboardAnalytics()` now also returns `weighted_indicator` (average of indicator currents with optional config weights) and `team_culture_evaluation` (current net culture score). Helper methods `weightedIndicatorScore()` and `teamCultureEvaluation()` encapsulate the math for future reuse.
- `HomeController@index` passes the new metrics through to the view so the upcoming chart components can render them without touching Qualtrics JSON.
- Next: expose these values in the dashboard UI (temperature gauge + 2×2), then proceed to add filters and Phase 4 ops tasks.

### Sub-update – Dashboard UI: weighted indicator & culture eval
- The indicator card now shows both the per-indicator table and a “Weighted Indicator” progress bar derived from `weighted_indicator`.
- The team scatter card includes a “Team Culture Eval” progress bar (from `team_culture_evaluation`) so we can visualize the Y-axis metric even before the new chart lands.
- With the old Qualtrics block removed, these cards are the only representations; next developer should swap the tables for true charts when ready.

### Sub-update – Indicator card tweaks
- Indicator table now includes a miniature progress bar per indicator (current score vs ideal target) to approximate the future bar chart without any Qualtrics data. This keeps the dashboard informative while we finish the Vue/Chart.js replacement.

### Chart implementation plan
- We now have all server-side datasets needed for the dashboard: `gap_chart`, `indicator_scores`, `weighted_indicator`, `team_scatter`, `team_culture_evaluation`.
- Next pass should introduce lightweight Vue components (mounted via `resources/js/app.js`) for:
  1. **Gap Bar Chart** – horizontal bars for `gap_chart`, sorted by gap. Data is already sanitized; component just needs props + simple chart lib (Chart.js via Vite or plain SVG).
  2. **Indicator Satisfaction Chart** – either reuse the table with Vue for interactivity or build a stacked/dual bar representation using `indicator_scores`.
  3. **Team Satisfaction vs Culture Scatter** – bubble plot fed by `team_scatter`, with axes labeled per product spec (X=Weighted Indicator, Y=Team Culture Eval). Use Chart.js scatter + tooltips showing label/level/count.
- Once the Vue components exist, update `resources/js/app.js` to mount them conditionally (e.g., look for `data-gap-chart` JSON on container elements).
- After charts render from the new analytics, we can add filter controls (company/department/team, survey wave) and move into Phase 4 automation tasks.

### Sub-update – Gap chart component scaffold
- Added a simple Vue component (`resources/js/components/dashboard/GapChart.vue`) plus a mount point in `home.blade.php` so the “Top Unmet Work Content Needs” card is now powered by Vue using `gap_chart` JSON data.
- `resources/js/app.js` now mounts the survey renderer and the new GapChart independently; run `npm install && npm run build` (or `npm run dev`) when possible to compile the assets, since Vite isn’t available in this sandbox (`vite: command not found`).
- Next: add similar Vue components for the indicator list and team scatter, then introduce filters and Phase 4 work as planned.

### Sub-update – Indicator & Team Scatter components
- Added `IndicatorList.vue` and `TeamScatter.vue`, mounted via `indicator-list-root` / `team-scatter-root` in `home.blade.php`. Both consume JSON serialized by Blade, so once assets are built they replace the old Blade tables without referencing Qualtrics.
- `TeamScatter.vue` now draws a lightweight canvas-based scatter plot (indicator on X, culture on Y) plus preserves the tabular data for quick scanning. This removes the need for the legacy Chart.js bubble.
- `resources/js/app.js` now mounts three dashboard components (gap, indicator list, team scatter). Remember to run `npm install && npm run build` locally to compile these assets.
- Remaining chart TODOs: polish the scatter (legends/tooltip), consider using Chart.js once dependencies are available, then introduce filter controls + Phase 4 automation tasks.

### Next steps – Filters & Phase 4
- Implement filter controls (company / department / team / survey wave) that feed parameters into `SurveyAnalyticsService::companyDashboardAnalytics()`. A starter endpoint now exists at `GET /dashboard/analytics` (see `DashboardAnalyticsController` + route). The service now accepts filter arrays and supports department/team filtering via `company_worker` data (wave filtering still TODO).
- After filters work, proceed with Phase 4: survey scheduling automation, admin tooling, billing alignment, documentation/tests.

### Filter UI plan (front-end)
- Add a small filter bar on `home.blade.php` (above the cards) with dropdowns for Department, Team, Survey Wave. Populate the dropdowns with data from `company_worker` (unique departments/supervisors) and available survey waves (once defined).
- Introduce a lightweight Vue/Alpine component that captures filter selections, calls `/dashboard/analytics?company_id=...&department=...&team=...&wave=...`, and updates the `gap_chart`, `indicator_scores`, etc., via a global event or direct re-render.
- Ensure `SurveyAnalyticsService` adds wave filtering once we define how to tag responses per survey wave (likely `survey_assignments` metadata). Until then, filters should at least work for department/team.

### Sub-update – Filter bar scaffolding
- Added a basic Department/Team filter bar above the dashboard cards (`home.blade.php`). Buttons call new JS hooks that fetch `/dashboard/analytics` with department/team query params and re-mount the Vue components with the filtered data.
- `resources/js/app.js` now handles mounting/unmounting of Gap/Indicator/Team scatter components and refreshing them after filter changes. Wave filtering + more graceful state handling (loading/error states) still TODO.

### Sub-update – Wave labeling groundwork
- Added `wave_label` columns to `survey_assignments` and `survey_responses` plus logic in `SurveyService` to assign a default label combining the survey version and current month whenever an assignment is created or updated. `SurveyResponse` now inherits the label from its assignment.
- `SurveyAnalyticsService::filterResponses()` now honors a `wave` filter by matching against either `survey_version_id` or `assignment.wave_label`, so the new `/dashboard/analytics?wave=...` parameter works immediately.
- Next: expose a wave dropdown in the dashboard filter bar that passes either the version ID or label to the endpoint, and later replace the auto-generated label with explicit wave names once Phase 4 scheduling is finalized.

### Wave filtering & Phase 4 kickoff (next work)
- Wave labels are stored on assignments/responses; `SurveyService` auto-generates a label combining the survey version + current month. `SurveyAnalyticsService::filterResponses()` supports filtering by either version id or wave label, and `HomeController` exposes an `available_waves` array for the UI. The dashboard filter bar now shows this dropdown and `/dashboard/analytics?wave=...` honors the selection.
- Next: replace the auto-generated labels with explicit wave names once scheduling automation is finalized, add loading/error states to the filter UI, then proceed into Phase 4 tasks (survey scheduling, admin tooling, billing gating, documentation/tests).

### Sub-update – Wave status/cadence + automation polish
- Added `status`, `cadence`, and `last_dispatched_at` columns to `survey_waves` plus UI controls (status/cadence dropdowns, table badges). Waves can now be paused or set to drip cadences (weekly/monthly/quarterly) and the listing shows last dispatch times.
- `SurveyWaveController` validates/stores the new fields and exposes option lists to the Blade view.
- `ScheduleSurveyWaves` command now skips paused/completed waves, honors opens/due windows, enforces drip cadence spacing via `last_dispatched_at`, and records the transition to `processing`. Each dispatch queues `ProcessSurveyWave`, which updates `last_dispatched_at` and auto-completes full waves once assignments are created.
- **Migration:** `2025_02_18_020000_update_survey_waves_status` adds the new columns. Run `php artisan migrate` (locally) after pulling.
- Front-end filters now show a loading/error indicator while `/dashboard/analytics` fetches refreshed data.

### Phase 4 kickoff plan
1. **Wave catalog + scheduling metadata**
   - Added initial `survey_waves` table + `survey_wave_id` FK on assignments/responses. Next, expose admin UI to create/manage waves with `kind`, `opens_at`/`due_at`, etc.
   - Future work: replace the current auto-generated labels with entries from this table when scheduling automation runs.
2. **Automation service**
   - Console command `survey:waves:schedule` now exists (see `app/Console/Commands/ScheduleSurveyWaves.php`, scheduled weekly via `Kernel`). It dispatches `ProcessSurveyWave` jobs per wave, skips inactive subscriptions, and respects `opens_at`/`due_at`. Next iteration: integrate drip cadence + wave status transitions.
3. **Admin tooling**
   - Dashboard for internal admins to monitor upcoming/active/completed waves, retry failed assignments, and force re-sync.
4. **Billing alignment**
   - Ensure `BillingController` gates survey scheduling by plan tier (e.g., drips only for premium).
5. **Docs/tests**
   - Update README/AGENTS with new flow, add feature tests covering wave creation, assignment scheduling, analytics filtering by wave id.

### Next implementation steps
1. Build `SurveyWaveController` + admin UI (list/create/edit waves per company).
2. Add console command `survey:waves:schedule` that examines active waves and creates assignments (respecting billing status).
3. Wire billing checks so scheduling pauses if subscription inactive.
4. Enhance the dashboard filter bar with loading/error states for async fetches. ✅ Added basic loading/error text tied to the async fetch (see `home.blade.php` + `resources/js/app.js`).
5. After waves + scheduling are stable, move into the remaining Phase 4 tasks (docs/tests).

### Phase 4 status – automation & billing polish
1. **Per-assignment cadences** – `ProcessSurveyWave` now tracks `last_dispatched_at` + `dispatch_count` on `survey_assignments`, and the scheduler only fires drips when specific assignments fall outside their cadence windows. Manual cadences become one-shot per user.
2. **Status transitions & monitoring** – wave statuses automatically move from `processing` → `scheduled`/`completed`, and `survey_wave_logs` capture dispatch summaries plus manual actions. The admin UI surfaces per-wave progress (sent vs. completed), expandable logs, and reminder banners for queue/scheduler health.
3. **Billing alignment** – drip cadences are locked to the Pulse plan (tariff `1`) via `config/survey.php`. Billing states read from the manager’s Cashier subscription; `past_due`/`canceled` waves pause automatically with descriptive logs and UI badges.
4. **Docs/tests** – README and this file document the automation architecture, operational commands, and billing gates. Feature coverage includes wave creation guards, cadence enforcement, billing pauses, and the `/dashboard/analytics` filter endpoint (mocked service) so regressions are caught.
5. **Operational reminders** – Always run `php artisan queue:work --tries=1` and schedule `php artisan survey:waves:schedule` via cron (`* * * * * php artisan schedule:run`). Without both, drip jobs will stall and the UI will show stale statuses.
