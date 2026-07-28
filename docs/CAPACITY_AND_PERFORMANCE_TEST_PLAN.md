# Capacity and Performance Test Plan

No production capacity claim exists yet. Run this plan against an isolated PostgreSQL staging environment with the real web, worker, scheduler, cache, session, and mail-sandbox topology.

## Data profiles

- Launch: 100 active respondents, 3 units, 3 waves.
- Pulse: 500 active respondents, 20 units, 12 waves.
- Stress: 2,000 respondents, 50 units, 24 waves. This is a discovery profile, not an advertised plan limit.

Each profile needs frozen cohorts, realistic completion/missingness, delivery events, findings/actions, and multiple compatible and incompatible metric versions.

## Workloads

1. Run `tests/load/public-readiness.js` with k6 for web/readiness stability.
2. Upload and parse a 500-person governed roster CSV; record parse time, queue age, encrypted-staging cleanup, preview response size, commit time, invitation queue burst, and repeated-file idempotency.
3. Dispatch one full 500-person wave and record assignment creation time, queue peak/age, provider-sandbox acceptance, retries, duplicates, and database load.
4. Submit concurrent autosaves against the same assignment revision and prove stale writes fail without loss.
5. Submit each assignment twice concurrently and prove exactly one response/answer set.
6. Exercise manager analytics, trends, comparisons, action capture, and governed follow-up creation at each data profile.
7. Run `php artisan analytics:explain` and preserve plans for the slowest representative queries.
8. Fail and restart a worker during roster parsing and wave dispatch; confirm deterministic idempotency and recovery.

## Release budgets

- authenticated page p95 under 1.5 seconds;
- analytics p95 under 3 seconds at the 500-respondent profile;
- public/readiness k6 thresholds as encoded in the script;
- queue age p95 under 2 minutes during a 500-person launch;
- zero duplicate responses, assignments per cycle/user, invitations per idempotency key, or usage events;
- no cross-tenant rows or suppressed metric values in responses/logs.

Attach raw k6 output, database metrics, EXPLAIN plans, queue measurements, release SHA, environment shape, and deviations to the release packet.

## Bounded analytics rehearsal

The repository includes a fail-closed rehearsal for the Pulse analytics slice. Run it only from a clean committed checkout against an isolated PostgreSQL database. A disposable local profile can be created with:

```bash
php artisan migrate:fresh --force
php artisan demo:seed --import-instrument --employees=500 --months=11 --force
```

Select a completed wave with at least 500 assignments, then run:

```bash
php artisan readiness:capacity-rehearsal {company_id} \
  --wave=wave:{wave_id} \
  --iterations=10 \
  --minimum-invited=500 \
  --analytics-p95-ms=3000 \
  --output=/path/to/release-packet/capacity-rehearsal.json

php artisan analytics:explain {company_id} --wave=wave:{wave_id}
```

`readiness:capacity-rehearsal` records the exact source SHA, source cleanliness, database engine/version, cohort and answer counts, repeated analytics timings, privacy availability, and duplicate/cross-tenant/response-assignment findings. It fails on an uncommitted or unidentified checkout, a non-PostgreSQL database, fewer than 500 assigned users, suppressed/ineligible analytics, a p95 budget miss, or any bounded integrity finding. It never sets `production_signoff` to true.

This command exercises the real analytics service and database rows, but it is only one workload in the plan. Its JSON explicitly does not prove roster parsing, dispatch creation, concurrent autosave/submission, shared cache/session, mail acceptance, Stripe, queue age, worker recovery, provider alerts, backup/PITR, or a deployed topology. Those remain separate staging evidence.

## Local release-candidate smoke — July 27, 2026

The provider-neutral local PostgreSQL/web-process smoke passed the checked-in `public-readiness.js` profile:

- 20 maximum virtual users for three minutes;
- 7,504 completed iterations and 22,512 HTTP requests;
- 45,024/45,024 checks passed;
- 0 failed requests;
- request p95 182.82 ms and p99 191.15 ms.

This proves only the health/readiness/login surfaces in the local topology. The 500-respondent dispatch, authenticated analytics, shared cache/session, mail sandbox, queue-age, worker-failure, and provider-observability exercises above remain staging release gates.
