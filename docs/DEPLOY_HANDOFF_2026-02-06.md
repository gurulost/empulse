# Deploy Handoff (Commit e73483d)

This handoff covers the production rollout steps required after commit `e73483d`.

## What Changed (Why these steps matter)
- Wave automation reliability hardening:
  - Scheduler overlap protection and stale-processing recovery.
  - Per-wave unique queue job lock to prevent duplicate processing.
- Survey submission integrity:
  - Server rejects unknown/tampered question keys.
  - Validation error key format aligned with front-end handling.
- Analytics/report safety:
  - Wave scoping checks for report endpoints.
  - Team filter sourcing aligned to `company_worker.supervisor`.
- Scale readiness:
  - New DB indexes added for `company_worker` department/team/role filters.
- Frontend assets refreshed:
  - `public/build/manifest.json` and hashed bundles changed.

## Step 1: Run Migrations

### Commands
```bash
git pull origin main
php artisan config:clear
php artisan migrate --force
```

### Why
A new migration was added:
- `database/migrations/2026_02_06_020000_add_company_worker_filter_indexes.php`

It adds indexes used by analytics filter queries:
- `cw_company_department_idx`
- `cw_company_supervisor_idx`
- `cw_company_role_idx`

### Verify
```bash
php artisan migrate:status | rg 2026_02_06_020000
```
Expected: migration shows as `Ran`.

## Step 2: Keep Scheduler + Queue Running

### Required runtime processes
- Laravel scheduler (cron):
```cron
* * * * * cd /var/www/empulse && php artisan schedule:run >> /var/www/empulse/storage/logs/schedule.log 2>&1
```
- Queue worker (Supervisor/systemd/process manager):
```bash
php artisan queue:work --tries=1
```

### Why
Wave dispatch now relies on scheduled checks + queue execution. If either process is down, waves stall.

### Verify
```bash
php artisan schedule:list | rg survey:waves:schedule
ps aux | rg "queue:work --tries=1"
tail -n 100 storage/logs/laravel.log
```
Expected:
- `survey:waves:schedule` appears in scheduler list.
- At least one queue worker process is active.
- No repeated wave-processing failures.

## Step 3: Deploy Updated Frontend Assets (`public/build`)

### Commands (if you build in deploy pipeline)
```bash
npm ci
npm run build
```

### If deploying from Git-built artifacts
Ensure the commit's `public/build` files are present on the server, especially:
- `public/build/manifest.json`
- new hashed files referenced by the manifest

### Why
Blade uses Vite manifest lookups. If manifest and hashed files are out of sync, pages can fail to load CSS/JS.

### Verify
```bash
php artisan optimize:clear
ls -la public/build/manifest.json
```
Then open dashboard pages and confirm no missing asset errors in browser console/network.

## Post-Deploy Smoke Check (Recommended)
1. Open dashboard and reports pages; verify charts/data load.
2. Submit one survey response end-to-end.
3. Trigger one scheduler pass manually:
```bash
php artisan survey:waves:schedule
```
4. Confirm wave logs update and no duplicate dispatch behavior is observed.

## Success Criteria
- Migration ran successfully and new indexes exist.
- Scheduler and queue worker are continuously running.
- Frontend assets load without 404/mismatch errors.
- Survey submission and wave automation execute without runtime errors.
