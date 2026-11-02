# African Ranked

A Laravel 12 article platform migrated from the original Bolt React site. The amber/orange/stone palette, Plus Jakarta Sans typography, hero, category tiles, cards, newsletter panel, article content, slugs, and eight regional editions are retained. Pages are now rendered with Blade and ordinary links; React, client-side routing, and Supabase are removed from the runtime.

## Roadmap to launch

The working task list is [LAUNCH-PLAN.md](docs/LAUNCH-PLAN.md). It puts tested super-admin/author workflows and the client-facing CMS ahead of bulk content production, then gates launch on **160 unique accepted articles**, full release checks, and founder signoff. Track the inherited content in [CONTENT-REGISTER.csv](docs/CONTENT-REGISTER.csv); those 13 inherited records are not automatically launch-ready.

## Railway staging

Use the [Railway deployment runbook](docs/RAILWAY-DEPLOYMENT.md) and [server variable template](deploy/railway.env.example). The Laravel runtime needs PostgreSQL, a stable APP_KEY, database sessions/cache/queue, a worker and a scheduler. Keep APP_PREVIEW=true on the Railway domain until the custom-domain release is accepted. Old React/Caddy build overrides must be removed; the repository Dockerfile starts PHP-FPM and nginx.

The CMS includes bulk publish, queued OpenSEO-to-Gemini metadata optimisation, immutable draft revisions, homepage featuring, pages, regions, MFA and per-article permissions. See the [live research baseline](docs/SEO-RESEARCH-2026-11-02.md) and [SEO workflow review](docs/SEO-WORKSPACE-REVIEW.md). Metadata automation does not create verified full articles or publish them automatically.

Verification on 2 November 2026: 91 backend tests / 3,316 assertions on SQLite and PostgreSQL; 15 Chromium workflow tests; a successful Docker build and PHP-FPM/nginx startup with PostgreSQL; live OpenSEO research and a structured Gemini generation probe. Infrastructure acceptance on the owner's Railway service, mail delivery, content expansion and final launch checks remain outstanding.

## Local development

Requirements: PHP **8.4+** with ctype, curl, DOM/XML, fileinfo, intl, mbstring, OpenSSL, PDO SQLite, tokenizer, and zip; Composer 2; Node 22.12+ (tested with Node 24); npm. SQLite is the default local database. Set up a different Laravel-supported database through `.env` when needed.

```bash
composer install --no-interaction
npm ci
# Only when .env does not exist:
cp .env.example .env
php artisan key:generate
# Create this file only if absent; do not overwrite an existing database:
touch database/database.sqlite
php artisan migrate
npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

In this prepared cloud instance, prepend `/workspace/toolchain/bin` to PATH to use the locally installed PHP and Composer. When using that workspace PHP wrapper, append `--no-reload` to `php artisan serve` so Laravel preserves the wrapper's PHPRC configuration in its child server process. These workspace paths are an environment convenience, not a production dependency. Dependencies are locked in `composer.lock` and `package-lock.json`. With this environment's restricted network policy, `composer install --prefer-source --no-interaction` uses the existing authenticated HTTPS Git route instead of blocked GitHub API archives. Keep signature, TLS, and checksum verification enabled.

For Vite hot reload, run `npm run dev -- --host=0.0.0.0` in a second terminal while Laravel serves requests. Stop Vite before testing the production build so that a stale `public/hot` file does not select development assets.

```bash
php artisan test
vendor/bin/pint --test
npm run check
npm run build
```

## Preview and test before launch

The platform remains in preview. Use `npm run preview` for a local server with noindex response headers, and `npm run test:browser` for isolated Chromium tests with a disposable database. See [PREVIEW.md](docs/PREVIEW.md) for manual scenarios, screenshots, toolchain requirements, and current limitations. This workspace has no public preview URL tool; generated screenshots are available under `artifacts/previews/`.

Researched project-local SEO skills from Corey Haines and AgriciDaniel are documented in [SEO-SKILLS.md](docs/SEO-SKILLS.md). The business baseline is display ads plus affiliate revenue for global English-speaking readers interested in Africa. Official public OpenSEO skills and a project MCP fallback are staged; see [OpenSEO setup status](docs/OPENSEO-SETUP.md). Live keyword/competitor research is pending client connection and network access; no traffic or ROI estimates are invented.

## Staff workspace

The first author/super-admin workflow is implemented at `/staff/login` and `/staff`. It includes invite-only setup, role/ownership policies, team access management, private drafts and revision history, review feedback, approval, publication and withdrawal. Read [STAFF-WORKSPACE.md](docs/STAFF-WORKSPACE.md) for the walkthrough, secure account provisioning and unfinished launch features. There is no default administrator password.

## Content and routes

- The staff workspace manages database-backed articles/revisions. The six inherited JSON articles remain unverified preview samples, and production does not publish JSON samples implicitly. Regional editions are managed in the CMS with `content/regions.json` as initial seed data; category definitions can be imported idempotently. See the staff guide before running `content:import-seeds`, which imports private unassigned drafts and masks matching legacy sample URLs.
- Home: `/` and `/us`, `/uk`, `/au`, `/ca`, `/ng`, `/za`, `/ke`.
- Categories: `/categories/{slug}` and `/{region}/categories/{slug}`.
- Articles: `/articles/{slug}` and `/{region}/articles/{slug}`.
- Editorial approach: `/strategy` and `/{region}/strategy`.
- Discovery: `/sitemap.xml`, `/sitemaps/{region}.xml`, `/robots.txt`, `/feed.xml`, `/llms.txt`.
- `Catalog` loads the records; `PageController` renders pages; `Seo` supplies initial-HTML metadata and safe JSON-LD.

Identical regional article bodies use the global article as canonical. Regional article URLs remain usable, but are omitted from the sitemap and do not emit hreflang. Home and populated category pages use self-canonicals and reciprocal regional alternates; empty categories are noindex. Missing resources and extra path segments return a real 404. Region selection preserves the current page in the URL and does not use IP geolocation.

## Motion and Lottie

Framer Motion's React components require React. This app instead uses Motion's framework-independent `motion` package for reveal movement. Content is never hidden pending JavaScript. Reduced-motion preferences disable entrance animations. A small original envelope animation is bundled locally and Lottie loads only when its newsletter icon enters the viewport. No external animation account is needed. Images have explicit dimensions, deferred loading below the fold, and a local fallback.

## Newsletter

The form posts to Laravel with CSRF protection, validation, explicit consent, a honeypot, and a five-request/minute IP throttle. SQLite stores normalized unique email addresses, edition, and consent time. The success response is identical for existing subscriptions. There is no public subscriber read endpoint. This replaces the previous Supabase integration; existing remote subscriber records are **not** imported or altered.

Signup currently records a request only. Email delivery, double opt-in, unsubscribe links, retention rules, an operator privacy/contact page, and an email provider integration remain production work. Do not advertise confirmation emails as sent. Use fake test addresses; do not print subscribers or secrets in logs. Old Supabase environment requirements can be removed from environment settings; this Laravel app does not read them.

## Search, answer engines, and generative discovery

Titles, descriptions, canonicals, Open Graph/Twitter cards, visible FAQs, Article/FAQPage/BreadcrumbList/ItemList JSON-LD, RSS, and sitemaps are server rendered. The experimental llms.txt index lists canonical articles. None of these guarantees rankings, rich results, AI citations, traffic, or ROI.

Repository skills are in `.agents/skills/`; reviewer briefs are in `agents/`. Start with [AGENTS.md](AGENTS.md). The GEO skill concerns generative engine optimization, distinct from geographic editions. The briefs guide review tasks; no unattended agents or external services are installed.

## Before production

1. Verify inherited statistics, attributed quotations, authors, safety advice, image rights, and top-N list completeness. Some original titles promise more entries than the supplied bodies enumerate. The migration preserves those records; it does not validate their claims.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, a real HTTPS `APP_URL`, a securely generated unique `APP_KEY`, database configuration, and correct session/cookie settings. Never commit `.env` or keys. Discovery URLs derive from APP_URL, so check that they use the live origin.
3. Point the web server document root at `public/`, keep private files inaccessible, and grant the application write access to `storage/` and `bootstrap/cache/`. Terminate HTTPS through trusted infrastructure; configure trusted proxy handling for that deployment, not a blanket wildcard.
4. Run `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`, deployment-authorized migrations, and `php artisan optimize`. Back up persistent data and plan database/schema changes before deployment. Do not run the PHP development server in production.
5. Complete newsletter compliance/delivery, two-factor authentication, richer editorial/media operations and the remaining CMS launch tasks. Measure Core Web Vitals, verify image/font availability and structured data, submit the sitemap in Search Console, and connect consent-aware analytics when actually chosen.

No deployment, remote database changes, Git push, or search submission is performed by this build.

## Editorial automation

See [the current editorial automation guide](docs/EDITORIAL-AUTOMATION.md) for automatic CMS sync, SEO defaults, advanced article controls, secure provider credentials, GPT drafting, scheduler startup and outstanding launch gates. `npm run review:scheduler` runs the isolated review scheduler alongside the web server.
