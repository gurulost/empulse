# Capacity and Performance Test Plan

No production capacity claim exists yet. Run this plan against an isolated PostgreSQL staging environment with the real web, worker, scheduler, cache, session, and mail-sandbox topology.

## Data profiles

- Launch: 100 active respondents, 3 units, 3 waves.
- Pulse: 500 active respondents, 20 units, 12 waves.
- Stress: 2,000 respondents, 50 units, 24 waves. This is a discovery profile, not an advertised plan limit.

Each profile needs frozen cohorts, realistic completion/missingness, delivery events, findings/actions, and multiple compatible and incompatible metric versions.

## Workloads

1. Run `tests/load/public-readiness.js` with k6 for web/readiness stability.
2. Dispatch one full 500-person wave and record assignment creation time, queue peak/age, provider-sandbox acceptance, retries, duplicates, and database load.
3. Submit concurrent autosaves against the same assignment revision and prove stale writes fail without loss.
4. Submit each assignment twice concurrently and prove exactly one response/answer set.
5. Exercise manager analytics, trends, comparisons, action capture, and governed follow-up creation at each data profile.
6. Run `php artisan analytics:explain` and preserve plans for the slowest representative queries.
7. Fail and restart a worker during dispatch; confirm deterministic idempotency and recovery.

## Release budgets

- authenticated page p95 under 1.5 seconds;
- analytics p95 under 3 seconds at the 500-respondent profile;
- public/readiness k6 thresholds as encoded in the script;
- queue age p95 under 2 minutes during a 500-person launch;
- zero duplicate responses, assignments per cycle/user, invitations per idempotency key, or usage events;
- no cross-tenant rows or suppressed metric values in responses/logs.

Attach raw k6 output, database metrics, EXPLAIN plans, queue measurements, release SHA, environment shape, and deviations to the release packet.

## Local release-candidate smoke — July 27, 2026

The provider-neutral local PostgreSQL/web-process smoke passed the checked-in `public-readiness.js` profile:

- 20 maximum virtual users for three minutes;
- 7,504 completed iterations and 22,512 HTTP requests;
- 45,024/45,024 checks passed;
- 0 failed requests;
- request p95 182.82 ms and p99 191.15 ms.

This proves only the health/readiness/login surfaces in the local topology. The 500-respondent dispatch, authenticated analytics, shared cache/session, mail sandbox, queue-age, worker-failure, and provider-observability exercises above remain staging release gates.
