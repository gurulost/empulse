# Empulse Observability and Service-Level Contract

Status: pre-launch release contract; no production service history exists yet.

## Signals

- `/api/healthz` proves only that the web process can answer. It returns the configured immutable release SHA and non-secret deployment-environment identity.
- `/api/readyz` proves database access, required runtime tables, and fresh scheduler and worker heartbeats in production. Production configuration cannot disable those heartbeat checks.
- `X-Empulse-Release` binds every HTTP response to the configured Git SHA. `app:verify-deployment` compares the externally served header and health payloads to the release owner’s expected SHA/environment and rejects redirects, stale processes, missing security headers, or identity mismatch.
- The scheduler writes its heartbeat and queues `RecordWorkerHeartbeat` every minute. A stale worker heartbeat therefore detects both a missing worker and a queue that is not draining.
- `email_delivery_events`, `survey_wave_logs`, `billing_webhook_events`, `audit_events`, `retention_runs`, and `action_loop_events` are structured operational evidence.
- Application logs must be shipped off-host with release SHA, environment, request/correlation ID, route, status, duration, job class, attempt, company ID where appropriate, and exception class. Never log assignment tokens, answer values, passwords, payment data, or direct data-subject export content.

## Initial SLOs

These are launch targets to validate in staging and revisit after a design-partner pilot:

| Service indicator | Target |
| --- | --- |
| Web availability | 99.9% monthly, excluding approved maintenance |
| Authenticated page p95 | under 1.5 seconds |
| Analytics API p95 for a 500-active-respondent company | under 3 seconds |
| Queue age p95 | under 2 minutes |
| Scheduler and worker heartbeat age | under 3 minutes |
| Invitation accepted by provider | 99% within 10 minutes, excluding suppressed/invalid recipients |
| Billing webhook processing | 99.9% within 5 minutes |
| Cross-tenant or unsuppressed-small-cohort exposure | zero |
| Submitted-response loss | zero acknowledged submissions |

## Alerts

Page the release owner for readiness failure over five minutes, queue age over ten minutes, failed billing webhook replay, audit-chain verification failure, repeated wave-job failure, or any suspected privacy/tenant incident. Create a next-business-day ticket for rising bounce rates, analytics latency budget breach, or a single recoverable job failure.

## Dashboards

The selected provider must graph request rate/error/latency, queue depth and oldest age, scheduler/worker freshness, jobs failed/retried, database connections/CPU/storage/slow queries, invitation states, webhook failures, active collection funnels, and privacy/audit integrity runs. Provider setup is required release evidence; this document does not claim it exists.

## Incident minimum

1. Declare severity and incident owner.
2. Stop the affected dispatch, feature, or release using the narrowest reversible control.
3. Preserve logs and audit evidence.
4. Assess tenant/respondent scope; do not speculate externally.
5. Restore service or roll forward.
6. If privacy or security may be involved, engage the designated legal/privacy owner.
7. Record timeline, impact, root cause, corrective actions, and verification.
