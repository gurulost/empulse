# Backup, Restore, and Disaster-Recovery Runbook

Status: provider-neutral pre-launch contract. A successful staging drill is required before release.

## Objectives

- PostgreSQL point-in-time recovery target: RPO 15 minutes or better.
- Core service restore target: RTO 4 hours.
- Audit, survey evidence, billing reconciliation, and privacy-governance records are critical data.
- Object storage, environment secrets, webhook configuration, DNS, and release artifacts must be recoverable independently of the database.

## Required provider controls

1. Encrypted automated PostgreSQL backups with at least 35 daily restore points and point-in-time recovery.
2. Backups in a separate failure domain and access restricted to named operators with MFA.
3. Quarterly restore into an isolated, access-restricted database—not over the source.
4. Configuration/secrets inventory and tested replacement procedure.
5. A versioned release artifact and exact migration list for every release.

## Restore drill

1. Open a change record and identify source backup, target database ending in `_restore_drill`, owner, and deletion time.
2. Restore without modifying the source.
3. Run migrations in read-only status mode, then:

```bash
php artisan app:production-check
php artisan audit:verify
php artisan analytics:explain <fixture-company-id> --no-analyze
composer test -- --filter="HealthEndpointTest|AuditTrailTest|OrganizationCohortIntegrityTest"
```

4. Compare row counts and sampled hashes for companies, memberships, wave cycles/audiences, assignments, responses/answers, audit events, entitlements/webhooks/usage, privacy governance, and action-loop evidence.
5. Verify one historical baseline and follow-up result reproduces from pinned instrument/metric hashes.
6. Record actual RPO/RTO, discrepancies, commands, operator, timestamps, and evidence location.
7. Destroy the isolated target only after approval and evidence capture.

## Disaster decision

Prefer provider point-in-time recovery and forward-fix. Never run a rollback migration against customer evidence unless that exact rollback has passed an isolated restore rehearsal. If integrity is uncertain, make the application unavailable and preserve the database rather than serving potentially incorrect results.
