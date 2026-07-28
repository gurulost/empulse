# Release and Rollback Policy

Empulse has no production deployment yet. This policy defines the evidence required for the first and later releases.

## Release packet

- immutable Git SHA and built artifact identity, externally matched through `app:verify-deployment`;
- passing PostgreSQL CI, PHP tests/formatting, frontend lint/unit/build, dependency audits, secret scan, and Playwright journeys;
- reviewed migration plan, estimated lock/duration, and rollback or forward-fix path;
- clean staging install plus representative load/concurrency evidence;
- backup/restore drill and audit-chain verification;
- privacy, methodology, security, and accessibility sign-offs;
- environment/config check, provider dashboards/alerts, owners, and on-call contact;
- product-owner approval of prices, contract/privacy language, and launch cohort.

## Rollout

1. Deploy the artifact to staging and run the release packet.
2. Take/confirm a restorable backup.
3. Apply migrations as a one-time release action.
4. Deploy to an internal/canary environment and run `app:verify-deployment` against the expected SHA and environment ID before verifying billing webhook test, baseline survey, analytics suppression, and action loop.
5. Admit only the approved design-partner cohort.
6. Watch error rate, latency, queue age, delivery, webhook, and integrity signals through the observation window.
7. Expand only with explicit release-owner approval.

## Rollback

- Before migrations: redeploy the last known-good artifact.
- After additive migrations: normally redeploy the prior artifact only if it is schema-compatible; otherwise roll forward.
- After a destructive or incompatible migration: stop and restore only from a tested backup under the incident process.
- Pause survey dispatch and reminders independently when measurement/delivery is suspect.
- Never delete or rewrite submitted answers, audit events, billing evidence, or frozen cohorts to make a rollback appear clean.
