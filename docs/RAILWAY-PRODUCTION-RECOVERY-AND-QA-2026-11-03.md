# Africa Ranked — Railway operations and recovery controls
*Prepared 3 November 2026. Applies to Railway project `757421e4-670a-4465-a4ba-48cf0a54015b`, production environment `e9584cd1-5734-4f4e-b9eb-f0e93eb163c8`.*

## Present infrastructure, verified
- Web: `african-ranked-web-app`; persistent volume mounted at `/app/storage`. The volume stores author-uploaded media, application runtime files and session/cache files as configured.
- Database: `Postgres`; persistent volume at `/var/lib/postgresql/data`.
- Queue: `african-ranked-worker`, Laravel database queue worker; schedule: `african-ranked-scheduler`, Laravel `schedule:work`. Both are Railway services, not Railway cron resources.
- The Railway-managed address `https://african-ranked-web-app-production.up.railway.app` is the approved current hosting origin. No custom DNS domain is required.
- A volume is **not** proof of an available backup. As of this audit, no backup schedule/restore receipt could be verified through the available Railway integration. The backup sign-off stays open.

## Required native Railway volume backup setup — NOT YET ATTESTED
Railway's [volume backup settings](https://docs.railway.com/volumes/backups) are in each service's **Backups** tab. Configure these separately:
1. **Postgres** → **Backups** → enable Daily, Weekly and Monthly. Also create an initial manual backup; confirm timestamp and status.
2. **african-ranked-web-app** → **Backups** → enable Daily, Weekly and Monthly for the mounted `/app/storage` volume; create an initial manual backup.
3. Record the snapshot timestamps, backup ownership and retention. Railway documents Daily retention ~6 days, Weekly ~27 days, Monthly ~89 days. These are snapshots within Railway, not independent off-platform copies.
4. Evaluate PostgreSQL [point-in-time recovery](https://docs.railway.com/volumes/point-in-time-recovery) for a better RPO and off-platform logical exports for protection against project-level loss.

**DO NOT restore a backup onto the live site as a test.** Restoring a Railway volume stages a replacement mount and a service redeployment; retain the original. Test recovery in an isolated nonproduction environment, verify article counts, latest revisions, media and login flows, then record RTO/RPO and only sign off recovery.

## Recovery and rollback runbook
- Freeze editorial writes; capture incident timestamp, most recent deployment SHA and backup timestamp.
- For application-only regressions, use Railway deployment rollback **without** rolling back database changes. Review migration compatibility first.
- For database or media corruption, preserve the existing volume, choose an appropriate backup, restore to a separate environment/service where possible, then compare counts, file checksums, schema and user access.
- Do not expose DB_PUBLIC_URL or credentials in chat or logs; run recovery tooling inside the project private network.
- Verify `/up`, homepage, two published articles, article editor, media access, worker/scheduler heartbeats, and a test email after recovery.
- Record incident root cause, data-loss window, downtime, and sign-off.

## Operational monitoring installed in repository
- `ops:heartbeat` runs every 5 minutes in Laravel's scheduler, writes a shared-cache timestamp and queues a `WorkerHeartbeat` job.
- `ops:heartbeat --status` reads both heartbeat timestamps, current pending/failed database job counts, cache and queue drivers; `--strict` fails on stale heartbeat or database/cache outage.
- Healthy scheduler and worker heartbeats should be less than 15 minutes old. If stale, inspect Railway scheduler/worker logs and deployment state, then check `jobs` and `failed_jobs` counts. Do **not** clear failed jobs without inspecting them.
- `ops:mail-smoke --to=john@salibay.com` sends one direct SMTP message using configured SMTP2GO credentials. SMTP acceptance is not inbox delivery. Validate inbox placement and password reset separately.
- `ops:content-crawl` scans published CMS records for source/quality regressions, missing metadata/alt text, duplicate photo identities and links to unpublished articles. It does **not** verify external URLs or factual claims.
- Railway `environment_status`, HTTP error-rate and latency telemetry are separate platform checks. Worker and scheduler services may appear online even if their queue execution is stalled, hence the end-to-end heartbeat.

## Pending evidence before release approval
- Proof of scheduled snapshots for both live volumes and an isolated restore rehearsal.
- Verified recovery-point/recovery-time objectives.
- SMTP2GO acceptance receipt **and** real inbox delivery, password reset test; newsletter double opt-in and unsubscribe before enabling newsletter.
- Scheduler and queue heartbeat verified with actual job consumed; job failure and monitoring procedures.
- CMS audit findings corrected and independently verified claims/media rights.
- Accessibility checks on 320px/390px/820px/1440px, keyboard/screen reader workflows, real Chrome/Firefox/Safari checks, and Core Web Vitals measurements.
- Privacy/legal/publisher-owner sign-off and a final decision to remove noindex. Do not change indexing merely because Railway is the current production host.
