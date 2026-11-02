# Review recovery — 2 November 2026

The current public review origin is `https://8hgcjy-ip-90-96-186-53.tunnelmole.net`. This is a temporary tunnel, not a Railway deployment; check it before sharing it again.

The frontend 500 was caused by adjacent Blade directives in the shared footer. Separate directives now compile and render correctly. Public templates also guard optional CMS tables when running without migrations. Verification: 85 PHP tests / 3,267 assertions and six frontend Chromium tests passed; the snapshot passed 14 route checks, including expected private-article and missing-page 404s.

The live web server, scheduler and durable queue worker now run from `/workspace/review-tools/approved-review-20261102-3`, rather than the editable checkout. The snapshot retains the existing review database, key, sessions and provider credentials through a shared storage symlink. No account reset or article publication was performed. The port-8003 validation server was stopped after promotion. Subsequent research/bulk changes passed 91 backend tests / 3,316 assertions on both SQLite and PostgreSQL, plus 15 browser tests. Two owner-published review articles were retained. New OpenSEO/Gemini tasks use the database queue; run `npm run review:worker` with the same REVIEW_URL and review state.

For subsequent code updates:

1. Run the backend checks, frontend build and applicable browser tests in the source checkout. Do not edit the active snapshot.
2. Run `python3 scripts/review-snapshot.py /absolute/new/snapshot/directory`. The directory must be new and outside the checkout. Only application sources, dependencies and built assets are copied; `.env`, SQLite files and secrets are not copied. Storage is shared with the source review, so this is code isolation rather than a database sandbox.
3. Start that snapshot on a different local port with the review environment and actual `REVIEW_URL`. Check its pages before changing the public server. Do not apply a new schema to shared state before establishing compatibility and taking a private backup.
4. Switch the web server on port 8002 and its scheduler to the verified snapshot, leaving Tunnelmole running. Recheck the public HTTPS pages, asset responses and noindex headers. Keep the previous snapshot for code rollback; database migrations need a separate compatibility/restore plan.

The following sections describe the original preview setup and historical verification; their older test counts are superseded by the recovery evidence above.

# Preview and testing

This platform is in development. No production release or search submission is part of this workflow.

## Shared browser review

A temporary HTTPS review was created at `https://8hgcjy-ip-90-96-186-53.tunnelmole.net`. The link is process-bound, not permanent hosting, and may stop or change when the workspace/tunnel restarts. No production deployment was performed.

The shared review uses `npm run review` (port 8002), separate ignored state under `storage/framework/review/`, a dedicated session cookie/key, debug output disabled, `X-Robots-Tag: noindex, nofollow, noarchive`, no-store HTML responses, and a visible review banner. It does not read or overwrite the development subscriber database. Only invented addresses should be used for form testing; no emails are delivered.

The public origin must be passed as `REVIEW_URL` when starting `npm run review`, so generated assets, secure cookies, social metadata, and canonical links use the same HTTPS origin. This forcing applies only when `APP_REVIEW=true`, not to normal development or production.

The original safari image URL returned 404 even after photo-host access worked. It was replaced with a visually checked Pexels elephant image (`https://www.pexels.com/photo/4577793/`); article text and rankings were not rewritten. Publication rights and editorial claims still need their normal review.

### Current workspace tunnel

The working Tunnelmole client is retained outside the checkout under `/workspace/review-tools`. It uses standard TLS verification and the environment's HTTPS proxy. Its local helper was adapted only to accept `TUNNELMOLE_STORAGE_DIR` inside the writable workspace and pass an `https-proxy-agent` to its WebSocket connection. Telemetry is disabled. No tunnel account, credentials, subscription, or paid hosting was created.

To restore a link in this prepared workspace:

1. Start `npm run review` on port 8002.
2. Start the retained helper with `TUNNELMOLE_TELEMETRY=0`, `TUNNELMOLE_STORAGE_DIR=/workspace/review-tools/state`, and `node /workspace/review-tools/node_modules/tunnelmole/dist/bin/tunnelmole.js 8002`. Keep its managed session alive and read the generated HTTPS URL from its output.
3. Restart only the review server with `REVIEW_URL` set to that URL; leave the tunnel running. Retained ignored review state preserves the review key and test data.
4. Verify the public page, same-origin HTTPS assets, real images, navigation, secure-session form POST, noindex/no-store headers, and 404s. Never claim an old URL still works without checking.

The Cloudflare quick-tunnel attempt could not connect directly through the environment. Tunnelmole succeeded using the provided egress proxy. The ordinary browser trust store is managed/read-only and did not trust the environment's interception CA. Public browser testing therefore rendered responses fetched through Playwright's Node request layer using the existing environment CA and explicit proxy; certificate verification stayed enabled. This limitation is specific to this automated browser environment, not a direction to visitors to bypass TLS warnings.

Public HTTPS checks passed for real images, CSS/JS, Lottie, desktop/mobile/edition/article navigation, newsletter submission with real CSRF and secure cookies, noindex/no-store, missing pages, private-file access returning 404, and invalid CSRF returning 419. Captures are in `artifacts/review/`. The fake smoke-test subscription is removed after validation.

## Manual interactive preview

From the project directory, after the README setup and `npm run build`:

```bash
npm run preview
```

This starts Laravel on port **8000** using the existing local development database. In the prepared cloud environment the script selects the workspace PHP toolchain automatically. The process sets `APP_PREVIEW=true` and emits `X-Robots-Tag: noindex, nofollow, noarchive`, including on discovery endpoints and error responses. Canonical tags, schema, and the page's normal metadata remain inspectable. The HTTP noindex directive takes precedence over permissive page-level robots metadata.

Noindex does not provide authentication. Do not expose this development server as a production service. The command requires uncached Laravel configuration and built assets; if it stops with a `public/hot` or configuration-cache message, stop only your Vite server, remove a stale hot file, or run `php artisan config:clear` as indicated. It does not install packages, reset the app key, apply migrations, or erase data. Set `PREVIEW_PORT` when port 8000 is already in use.

The environment currently offers shell execution and automated Chromium, but no public preview URL/port-forwarding tool. Screenshots can be opened directly from workspace artifacts. For hands-on browser testing, use port forwarding if your workspace client supplies it, or run the project in your own development environment. Do not mistake a loopback address for a shareable site link.

## Pages and interactions to try

| Area | Path / action | Expected behavior |
|---|---|---|
| Homepage | `/` | Original amber-and-stone design, six categories, featured and article cards |
| Regional edition | `/ke` or edition menu | Edition changes while existing article/category paths are preserved |
| Category | `/categories/travel` | Only travel articles, with normal links |
| Empty category | `/categories/food` in an edition with no matching records, where applicable | Clear empty state and global-edition link; noindex page metadata |
| Article | `/articles/top-10-safari-destinations-africa-2026` | Full server-rendered text, author/date, section links, FAQs, share button |
| Regional article | `/ke/articles/top-10-safari-destinations-africa-2026` | Kenya navigation context; canonical points to global article |
| Missing article | `/articles/not-a-real-article` | Actual HTTP 404 and useful recovery link |
| Mobile | Narrow viewport; open menu, Escape | No horizontal overflow; menu closes and focus returns |
| Reduced motion | Enable OS/browser preference | Readable content and static envelope, without entrance animation |
| Without JavaScript | Disable JS and reload article | Article text, links, FAQs, and metadata still available |
| Newsletter | Use an invented `@example.test` address and explicit consent | Request saved locally; no confirmation email or newsletter delivery |
| Discovery | `/sitemap.xml`, `/feed.xml`, `/robots.txt`, `/llms.txt` | Inspectable documents; noindex preview response header |

The manual preview shares the local development subscriber table. For repeatable signup tests that leave it untouched, use the automated suite below.

## Repeatable browser suite

```bash
npm run test:browser
```

Playwright launches its own Laravel server on port **8010**, uses a fresh temporary SQLite database and ephemeral app key, tests real CSRF protection, and removes the temporary database on exit. It does not reuse the manual-preview server or its subscribers. Tests require built assets, Composer dependencies, and Chromium. The prepared environment uses `/usr/bin/chromium`; otherwise set `PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` or install Playwright Chromium with `npx playwright install chromium`. The latter downloads a browser and needs network access.

The six browser tests cover desktop rendering and Lottie, mobile navigation and edition changes, article canonicals/anchors/share fallback, browser form validation and duplicate signup, no-JavaScript reading, reduced motion, discovery documents, and intentional preview noindex behavior. Page-level JavaScript errors fail the suite. Traces/screenshots are retained under `test-results/` on failure.

Current screenshot artifacts (generated by the browser suite):

- `artifacts/previews/desktop-home.png`
- `artifacts/previews/mobile-home.png`
- `artifacts/previews/mobile-article.png`

These files and browser reports are ignored by Git. Original Pexels images may show local fallbacks because the current environment blocks that host. No image rights or photography access is inferred from a passing browser test.

## Backend and build checks

```bash
# In this prepared cloud instance, first:
export PATH="/workspace/toolchain/bin:$PATH"
php artisan test
vendor/bin/pint --test
npm run check
npm run build
```

Latest run: **13 PHP tests passed, 1,394 assertions; 6 browser tests passed**. This is functional verification, not a Core Web Vitals score or approval of inherited editorial claims. See the [SEO preview review](SEO-PREVIEW-REVIEW.md).
