# Git History Secret Remediation Runbook

Status: history rewrite declined; accepted-risk exception approved; rewrite retained only as a future contingency

Last rehearsal: 2026-07-27

Owner decision: 2026-07-28

Repository: `gurulost/empulse`

## Current Owner Decision

The repository owner and WorkFit mail administrator confirmed that the historical Sendinblue/Brevo credential was revoked and deactivated. The owner explicitly declined a Git-history rewrite and instructed the project to preserve all existing commits.

The three historical detections are therefore an explicit accepted risk:

- the revoked credential and two generic-key detections remain visible in old commits, existing clones, and any cached historical views;
- the root `.gitleaksignore` contains exactly the three approved fingerprints and no rule-wide, path-wide, regular-expression, commit-wide, or blanket exception;
- current-source and proposed-change scanning remain strict;
- `.github/verify-gitleaks-history-policy.sh` uses the CI-pinned Gitleaks version to prove that the unignored baseline is exactly those three fingerprints, the approved full-history scan passes, and a new unrecognized finding still fails.

This decision does not claim that the credential was removed from history. It records that the credential is dead, preserves historical integrity, and accepts its continued visibility.

## Purpose

This runbook records the accepted-risk decision and preserves the previously rehearsed rewrite procedure only as a future contingency. It is intentionally separate from application deployment. No production environment exists, and neither accepting this risk nor executing the contingency deploys Empulse.

The affected historical paths are:

- `attached_assets/Pasted-STACK-CONTEXT-FLARE-SHARE-Share-with-Flare-Docs-STACK-C_1764950893439.txt`
- `app/Http/Controllers/ContuctUsController.php`

Both paths are absent from the release-candidate tree but remain reachable in historical commits.

## Stop Rules

Do not rewrite or force-push under the current owner decision. The repository owner declined that action. The remaining phases are contingency documentation, not authorization.

A future rewrite may be reconsidered only after a new explicit owner decision and only if all of the following are true:

1. The historical Sendinblue/Brevo credential has been revoked or rotated by the mail administrator.
2. The repository owner explicitly authorizes a coordinated history rewrite and force push.
3. Repository writes are frozen for the maintenance window.
4. All collaborators have acknowledged that existing clones and branches must not merge old history back into the repository.
5. Branch protections and ruleset changes needed for the maintenance window have an owner and restoration checklist.
6. A release owner and rollback owner are present.

Never make an active or uncertain credential pass through an exception. The present exception is permitted only because the mail administrator confirmed revocation/deactivation and each finding is identified by its exact fingerprint.

Minimum future authorization if the owner reverses the current decision:

> I confirm the historical Sendinblue/Brevo credential has been revoked or rotated. I authorize the coordinated `git-filter-repo` rewrite and force-mirror push of `gurulost/empulse`, including `main` and `codex/production-readiness`, followed by pull-request reference cleanup with GitHub Support.

## Rehearsal Evidence

The rehearsal used a fresh disposable mirror of the actual GitHub remote and `git-filter-repo` v2.47.0. Nothing was pushed.

| Check | Result |
| --- | --- |
| Remote branch tips before rehearsal | `main` at `e166a6b`; candidate at `d9725de` |
| Remote pull-request refs observed | 2 |
| Commits rewritten in the mirror | 366 of 366 |
| Refs reported as changed | 4: two branches and two pull-request heads |
| First changed commit | `3829974caded6854ba5b90df57fa3a3792332be8` |
| LFS | Not in use |
| Candidate tree before and after | `cf4a3a028e652770c81bf4c5ec1050f2af84906c` |
| Candidate recursive tree listing SHA-256 before and after | `a493d9cff743dff816962ce60c54ea46872428c34788ba1785396f7f3f8a5387` |
| Removed paths reachable after rewrite | No |
| Old tainted commits/blobs reachable after rewrite | No |
| `git fsck --full --no-reflogs` | Passed |
| Gitleaks full-history result | 169 relevant commits scanned, no leaks found |
| GitHub refs after rehearsal | Unchanged |

The current `main` tree does not contain either obsolete path. Historical commits still do, and the accepted-risk decision preserves them.

## Future Contingency Only

The following phases must not be executed under the 2026-07-28 decision. They remain available only if the owner later withdraws the accepted risk and explicitly authorizes a coordinated rewrite.

## Contingency Phase 1: Rotate and Freeze

1. Revoke or rotate the historical mail credential. Record the provider-side confirmation without storing the credential value.
2. Pause repository writes and queued merge automation.
3. Record branch protection and ruleset settings.
4. Notify collaborators of the maintenance window and the no-merge rule for old clones.
5. Record remote heads, tags, and pull-request heads:

   ```bash
   git ls-remote git@github.com:gurulost/empulse.git \
     'refs/heads/*' 'refs/tags/*' 'refs/pull/*/head'
   ```

6. If a pre-rewrite bundle is required by the rollback owner, treat it as toxic data: encrypt it, restrict access, keep it offline, and destroy it after GitHub confirms server-side cleanup.

## Contingency Phase 2: Fresh Mirror and Rewrite

Use a new directory. Do not run this in a developer’s normal checkout.

1. Install or download `git-filter-repo` v2.47.0 or later. The `--sensitive-data-removal` option is required.
2. Create a fresh mirror:

   ```bash
   git clone --mirror \
     git@github.com:gurulost/empulse.git \
     empulse-history-remediation.git
   cd empulse-history-remediation.git
   ```

3. Record the candidate tree before rewriting:

   ```bash
   git rev-parse refs/heads/codex/production-readiness^{tree}
   git ls-tree -r refs/heads/codex/production-readiness | shasum -a 256
   ```

4. Remove both obsolete paths from all history:

   ```bash
   git filter-repo \
     --sensitive-data-removal \
     --invert-paths \
     --path attached_assets/Pasted-STACK-CONTEXT-FLARE-SHARE-Share-with-Flare-Docs-STACK-C_1764950893439.txt \
     --path app/Http/Controllers/ContuctUsController.php
   ```

5. Inspect:

   - `filter-repo/changed-refs`
   - `filter-repo/commit-map`
   - `filter-repo/first-changed-commits`
   - `filter-repo/ref-map`
   - `filter-repo/suboptimal-issues`

Stop if the changed refs, first changed commit, or current-tree effect differs materially from the rehearsal.

## Contingency Phase 3: Verify Before Any Push

All checks must pass in the rewritten mirror:

```bash
git fsck --full --no-reflogs
git rev-list --all --objects \
  | rg 'ContuctUsController.php|Pasted-STACK-CONTEXT-FLARE-SHARE'
gitleaks git --redact --log-opts=--all
git rev-parse refs/heads/codex/production-readiness^{tree}
git ls-tree -r refs/heads/codex/production-readiness | shasum -a 256
```

Expected results:

- `git fsck` has no errors.
- The removed-path search returns no matches.
- Gitleaks reports no leaks.
- The candidate tree remains `cf4a3a028e652770c81bf4c5ec1050f2af84906c`.
- The candidate recursive tree-listing digest remains `a493d9cff743dff816962ce60c54ea46872428c34788ba1785396f7f3f8a5387`.

Compare the current remote ref list with the maintenance-window snapshot. Stop if an unplanned remote write occurred.

## Contingency Phase 4: Authorized Push

This phase is destructive, is currently declined, and requires a new explicit authorization before execution.

1. Temporarily adjust branch protections only as narrowly as necessary.
2. Force-push the rewritten mirror:

   ```bash
   git push --force --mirror origin
   ```

3. Failures for `refs/pull/*` are expected because GitHub pull-request refs are read-only. Any other failed ref is a stop condition.
4. Immediately restore branch protections and repository rulesets.
5. Confirm remote branch and tag tips match the rewritten mirror.

## Contingency Phase 5: GitHub and Collaborator Cleanup

Open a GitHub Support request containing:

- repository: `gurulost/empulse`;
- affected pull requests: 2;
- first changed commit: `3829974caded6854ba5b90df57fa3a3792332be8`;
- LFS orphaning: none reported;
- confirmation that all writable refs were rewritten.

Ask GitHub Support to dereference affected pull-request refs, remove cached views, and run server-side garbage collection.

Collaborators should delete and reclone. If a clone must be retained, follow the `git-filter-repo` sensitive-data cleanup instructions. Old branches must be rebased onto rewritten history, never merged.

## Contingency Phase 6: Release Reverification

1. Run a fresh remote full-history Gitleaks scan.
2. Run the complete GitHub product CI job on the rewritten candidate SHA.
3. Confirm the candidate tree still matches the pre-rewrite candidate tree.
4. Confirm `main` and the release branch contain no removed path.
5. Promote the verified candidate to `main` only after every required check is green.
6. Record new SHAs in the release evidence packet and invalidate old SHA-based handoffs.

History cleanup is not deployment approval. Provider staging, backup, alerting, Stripe/mail drills, and independent legal, privacy, methodology, security, and accessibility approvals remain separate gates.

## Rollback

Before the force push, discard the disposable mirror and restart; GitHub is unchanged.

After the force push, rollback requires the designated rollback owner and the secured pre-rewrite ref snapshot or encrypted bundle. Rolling back re-exposes the sensitive history and is therefore a last-resort incident action. If rollback occurs:

1. keep the credential revoked;
2. freeze writes again;
3. restore refs under incident control;
4. diagnose the rewrite failure;
5. repeat the cleanup before reopening repository writes.

Do not restore old history merely to make an individual collaborator’s checkout easier to reconcile.

## Authoritative References

- [GitHub: Removing sensitive data from a repository](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)
- [`git-filter-repo` installation](https://github.com/newren/git-filter-repo/blob/main/INSTALL.md)
- [`git-filter-repo` repository and manual](https://github.com/newren/git-filter-repo)
