# Railway staging deployment and launch gates

## Verified deployment — 2 November 2026

The Laravel migration has been merged into GitHub main (PR #1). Railway now deploys main using `.railway/railway.ts`; legacy JSON configuration files have been removed. PostgreSQL, the web service, worker and scheduler reported successful deployment. The public staff login is a real Laravel CSRF form and emits staging noindex headers. A stable APP_KEY, private database reference, secure sessions, queue/cache settings and provider credentials are configured. The temporary review account/database remains separate.

First-owner invitation creation is still pending. This cloud execution environment cannot reach Railway SSH/SFTP, so an operator must run `php artisan staff:invite-admin johnkiunga01@gmail.com --name=John` inside the Railway web service, then privately read `storage/app/private/super-admin-setup.url`. No default password or public bootstrap endpoint was added. See section 4 for safe setup handling.

The sections below retain the initial deployment diagnosis and runbook; the verified status above supersedes the earlier statement that Railway still serves React.

As checked on 2 November 2026, `https://african-ranked-web-app-production.up.railway.app` serves the original React application. `/staff/login` and `/up` return the same React shell, not Laravel. The uploaded logs show a Caddy startup, without a fatal runtime error. Internal HTTP/TLS notices are expected behind Railway's public HTTPS proxy. That deployment has no staging noindex header and permits crawling; do not treat it as the accepted release candidate.

## Current Railway configuration update

The Railway CLI connection is verified. PostgreSQL, persistent web storage and three application services have been provisioned in the existing Africa Ranked project. Server-only staging variables are configured; provider credentials were transferred privately. The platform rejected legacy config-file updates, so `.railway/railway.ts` now manages the service topology through Railway's current IaC SDK. It preserves the existing volume and leaves variables unmanaged. The following original JSON instructions are historical; use the IaC definition instead. Deployment and public acceptance remain separately verified.

## 1. Deploy the Laravel source

Review the Laravel migration changes and use the branch containing `artisan`, `composer.json`, `Dockerfile` and `railway.json`. GitHub main was still the React source when checked. A deployment cannot use uncommitted files in this cloud workspace. Do not merge or replace the running service before its environment is configured.

In Railway, use the repository root, Dockerfile builder and `railway.json` config path for the web service. Remove old React/Vite/Caddy build or start-command overrides. The Docker entrypoint starts PHP-FPM and nginx. Railway supplies PORT. Use `/up` for the health check, and verify `/staff/login` returns the real Laravel form, not a generic 200 shell.

## 2. PostgreSQL and shared server variables

Add a PostgreSQL service. Set the web, worker and scheduler variables using [deploy/railway.env.example](../deploy/railway.env.example). Set `DB_URL` to a Railway reference to that PostgreSQL service's **private** `DATABASE_URL`, such as `${{Postgres.DATABASE_URL}}` when its service name is Postgres. Do not paste database credentials into chat, the repository, a VITE variable or a browser bundle.

Generate one stable APP_KEY locally with `php artisan key:generate --show`; enter it privately in Railway. Use the same key and database on web, worker and scheduler. Do not regenerate the key on redeploy. The existing temporary review has a different key/database; its account passwords and encrypted provider credentials do not transfer automatically.

Required staging values include:

```dotenv
APP_ENV=staging
APP_URL=https://african-ranked-web-app-production.up.railway.app
APP_DEBUG=false
APP_PREVIEW=true
APP_REVIEW=true
APP_ROLE=web
DB_CONNECTION=pgsql
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
STAFF_REQUIRE_ADMIN_2FA=true
EDITORIAL_LEGACY_PREVIEW=false
LOG_CHANNEL=stderr
MAIL_MAILER=log
```

Leave CMS_IMPORT_OWNER_EMAIL blank for the first bootstrap; an account that does not exist cannot own an import. Configure it after the owner has accepted the invitation. Initial repository sync creates private, unassigned drafts when no active verified owner exists. Assign those articles through the CMS before publication. Changing the import-owner variable affects future imports and does not reassign existing records.

The web config runs migrations, idempotent content sync and local metadata defaults before deployment. It never auto-publishes articles or calls paid SEO/AI services during startup.

## 3. Worker, scheduler and persistent files

Create two additional services from the same Laravel source and revision:

| Service | APP_ROLE | Config file path | Public domain |
|---|---|---|---|
| Web | web | `/railway.json` | The staging domain |
| Worker | worker | `/deploy/railway/worker.json` | None |
| Scheduler | scheduler | `/deploy/railway/scheduler.json` | None |

Start with one worker replica and one scheduler replica. The worker executes the durable database queue, including OpenSEO research and Gemini optimisation. The scheduler handles automatic repository sync and metadata maintenance. Worker-only services must not inherit the web health-check path or an old web start command. Keep worker timeouts below DB_QUEUE_RETRY_AFTER. Provider errors are not automatically retried; a failed or stale article gets a per-item result.

Mount persistent storage for the **web** service at `/app/storage` for private uploads, invitation files and future local media. Railway volumes are service-specific; do not assume the same volume is shared by all three services. Jobs, provider credentials, research, sessions and cache use PostgreSQL. Worker and scheduler can use their own ephemeral framework storage. Move durable media to object storage before scaling web replicas.

## 4. First super-admin and provider setup

Using a private Railway shell/CLI session against the web service, run:

```sh
php artisan staff:invite-admin johnkiunga01@gmail.com --name=John
```

Read `storage/app/private/super-admin-setup.url` privately, open the one-time link and set the password. Do not post that URL in issues, public logs or chat. It expires after 24 hours. Enrol the authenticator and retain the recovery codes privately. Do not assume the temporary review password exists in the new PostgreSQL database. The CLI refuses to overwrite an existing setup file; use a fresh private `--link-file` path if issuing a new invitation deliberately.

Add the OpenSEO and Gemini keys through Staff → Integrations, or private Railway variables. Set OPENSEO_PROJECT_ID for the African Ranked project and the research market (US English benchmark defaults to 2840). Verify OpenSEO using the free check. The verified Gemini default is `gemini-3.5-flash-lite`; Google rejected the former 2.5 model for new users. Google billing status cannot be verified by this app. New drafting through the GPT endpoint also requires a separately configured OpenAI credential.

Do not copy the entire temporary review database into Railway: it contains review accounts, sessions, encrypted secrets and test data. Curated editorial revisions and saved research need a deliberate export/import plan if they should move, rather than reverting to seed content. That transfer remains a migration task.

## 5. Staging acceptance

- Real Laravel homepage, staff login, `/up`, assets and deliberate 404s work over public HTTPS.
- Responses, including sitemap/feed, include `X-Robots-Tag: noindex, nofollow, noarchive`; debug remains off.
- Sessions survive navigation and redeploy; CSRF forms work on the Railway origin.
- Owner MFA, an invited author, ownership boundaries and recovery are tested with disposable content/accounts.
- Database and storage survive redeploy. Create a backup and restore it into a disposable database; verify article counts and revision pointers.
- Queue a small research/optimisation batch; observe queued → researching → running → completed/failed results. Verify API failures do not publish content or silently invoke another provider.
- Publish and withdraw a disposable article, then validate homepage selection, category membership, sitemap/feed and canonical URLs.
- Run `php artisan launch:check --json`. This intentionally fails staging/160-article gates; it does not replace operational acceptance.

## 6. Gates before public launch

Complete mail delivery, password-reset inbox testing, unsubscribe/consent operations, media and factual/source verification, author/trust/legal pages, monitoring, restore rehearsal and a tested rollback plan. Validate real analytics and Search Console ownership on the eventual custom domain. Produce at least 160 distinct complete articles from researched briefs; AI snippets, drafts and regional copies do not count as accepted articles.

Only after founder acceptance of the concrete release candidate: connect the custom domain, update APP_URL, keep stable keys, test redirects/canonicals, switch the public environment out of review/preview and enable indexing. Submit the production sitemap after these gates. The Railway domain stays staging until then.
