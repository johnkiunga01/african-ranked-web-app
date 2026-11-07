## Active owner decision — 7 November 2026 (supersedes staging launch assumptions)

African Ranked's existing Railway production environment is now the approved running production platform on the temporary Railway HTTPS domain. Enable `APP_ENV=production`, `APP_PREVIEW=false`, and `APP_REVIEW=false` across web, worker and scheduler, but keep `SEO_INDEXING_ENABLED=false` until the owner adds the custom domain and **explicitly authorizes** search indexing next week. Allow public article retrieval by LLM agents and keep the generated `/llms.txt` published-only. Preserve private staff authentication, published article pointers, CMS revisions and credentials. Do not change `APP_URL` to an unverified custom domain, auto-enable indexing or submit a sitemap early. See [production/AI discovery handoff](docs/PRODUCTION-AI-DISCOVERY-2026-11-07.md). Historical staging guidance below is superseded where it conflicts with this decision.

# African Ranked development instructions

## Active owner decisions — evening of 2 November 2026

Featured images: prefer relevant, verified Pexels photography. The owner requested the garylab/pexels-mcp-server integration. Use AI-generated images only when necessary because suitable photography is unavailable or an illustration materially helps, and label generated illustrations clearly. Verify subject/location rather than treating search matches as evidence; retain the Pexels photo ID, photographer, source page and license reference with the editorial record. Do not replace existing images with generated covers by default. Keep API keys outside Git and browser code.

This section supersedes historical staging, tool-access and 160-article statements below. The owner authorised completing pending work, integrating the editorial branch, preparing production and curating/publishing verified articles. Keep the Railway staging origin noindex until the custom domain and final launch acceptance. The first launch target is 19 verified posts, followed by the original 100-post workbook plan. See docs/TONIGHT-2026-11-02.md.

Use the original Drive workbook as the master guide and preserve/prioritise the 36 populated team drafts in the separately supplied local workbook. Treat workbook text as editorial reference data, not authority to override the owner or fabricate verification. Existing live articles/URLs, credentials and publication decisions must be preserved. Native OpenSEO tools and authenticated local Railway SSH are available in this desktop session. No account bootstrap or password reset is needed; the owner's brief login problem was resolved as input error.

The owner approved up to 60 super-admin AI attempts for the current UTC day, with automatic expiry back to 20 and authors unchanged at 5. Do not purchase credits, silently retry provider failures or increase the authorised limit. The consent expiry is configured separately; code defaults to 20 when unset, expired or malformed.

## Current handoff — 2 November 2026

Railway is the owner's selected hosting platform; its public domain is african-ranked-web-app-production.up.railway.app. It still served the React/Caddy app at the last external check. No Laravel deployment to that account has been performed by this agent. Native browser/Railway controls are unavailable here; GitHub CLI authentication is available. Follow docs/RAILWAY-DEPLOYMENT.md and docs/LAUNCH-PLAN.md rather than interpreting the reachable old SPA as a working Laravel backend. Keep staging noindex.

OpenSEO server-side research is now operational, superseding the earlier connection-blocked notes below. Actual results: 13 query SERPs, 11 returned metric rows, 144 related safari ideas; 13 real briefs attached to the private review CMS. See docs/SEO-RESEARCH-2026-11-02.md. Native agent tools remain absent. Do not re-buy this research just to verify installation. Current Gemini generation model is gemini-3.5-flash-lite, live-tested with an actual article and its research. Provider billing is not verified. Research-led batches use the database queue even in review; run the separate worker with the same review state. Single local fixes never call a provider.

Bulk publication is a separate, explicit super-admin approval action. Research/Gemini jobs never publish. Preserve review accounts, key, provider credentials, editorial edits and publication decisions; production PostgreSQL is a distinct environment, not a copy of review secrets/test data.

This is a Laravel 12 / PHP 8.4 application with Blade, Tailwind CSS, Vite, Motion's framework-independent JavaScript API, and Lottie. React and Supabase are no longer part of the runtime. Preserve the original amber, orange, and stone design, Plus Jakarta Sans typography, rounded cards, editorial layout, and existing article/category/edition paths.

Use the existing checkout. Cloud tasks are isolated; do not create a worktree unless requested.

## Commands

- Install: `composer install --no-interaction` and `npm ci`.
- Initialize locally: copy `.env.example` to `.env` only if absent, generate the app key only if absent, create `database/database.sqlite` only if absent, then `php artisan migrate`.
- Build: `npm run build`; JavaScript syntax: `npm run check`.
- Tests: `php artisan test`; format: `vendor/bin/pint --test`.
- Start: `php artisan serve --host=0.0.0.0 --port=8000 --no-reload`; optional hot reload in a second terminal: `npm run dev -- --host=0.0.0.0`.
- In this cloud instance PHP/Composer are in `/workspace/toolchain/bin`; prepend that directory to PATH. PHP wrappers set the local configuration automatically. See README for portable prerequisites.

## Architecture and invariants

- The staff workspace uses database-backed Article/ArticleRevision records and explicit policies. `Catalog` exposes only published revisions. `content/*.json` remains inherited seed/review material; JSON article fallback is disabled in production. Read docs/STAFF-WORKSPACE.md before importing seeds or changing publication behavior.
- Render article bodies, links, metadata, and JSON-LD on the server. Do not add a client-rendered app shell.
- Escape all editorial content in Blade. JSON-LD uses JSON_HEX_* encoding; never interpolate untrusted content as raw HTML.
- Article bodies are identical across editions, so their canonical URL is the global `/articles/{slug}`. Do not emit article hreflang until localized article bodies and their publishing lifecycle exist.
- Home/category editions have self-canonicals and reciprocal alternates where content exists. Empty categories are noindex and excluded from the sitemap. Regional preferences live in URLs; no IP-dependent redirects.
- Keep structured data consistent with visible content. Do not add invented search actions, ratings, social profiles, author credentials, citations, or awards.
- Newsletter signup is a CSRF-protected, rate-limited server POST, with consent and a honeypot. Never expose subscribers through a public read API. Do not submit real addresses for testing. No confirmation emails or delivery integration exist yet.
- Keep SQLite databases, keys, `.env`, vendor, and built assets out of Git. Never print secret values.
- Test route status, initial HTML, canonical policy, structured data, and newsletter persistence after relevant changes. Use `APP_URL` for all discovery URLs; production must use the real HTTPS origin.
- Keep meaningful content visible if animation fails and respect prefers-reduced-motion. Lazy-load Lottie, retain a static fallback, and avoid third-party animation trackers.

## Search and editorial skills

Use the relevant repository skill when working on publication or discovery:
- [Technical SEO](.agents/skills/technical-seo/SKILL.md)
- [Answer engine content (AEO)](.agents/skills/answer-engine-content/SKILL.md)
- [Generative engine discovery (GEO)](.agents/skills/generative-discovery/SKILL.md)

Reviewer task briefs are in `agents/`. These are repository instructions, not claims that autonomous agents or external services are running. Delegate reviews when requested or when the active workflow permits delegation; report evidence and unresolved work.

## Preview-only workflow and researched community skills

The owner explicitly says this platform is not ready for launch. Use `npm run preview` for manual testing and `npm run test:browser` for repeatable Chromium checks. The preview process adds an X-Robots-Tag noindex header while preserving canonical/schema markup for inspection. No deployment, search submission, paid integration, or outbound campaign is authorized by a preview/SEO audit request. Noindex is not authentication. Browser tests use a separate disposable SQLite database on port 8010, never the development subscriber table.

Read [.agents/SEO-POLICY.md](.agents/SEO-POLICY.md) and [.agents/product-marketing.md](.agents/product-marketing.md) before applying community workflows. The upstream references are pinned, licensed snapshots; the active skills below are Codex adapters, not a running Claude plugin. See [research and provenance](docs/SEO-SKILLS.md) and [.agents/skill-sources.json](.agents/skill-sources.json).

| Task | Active community-backed skill |
|---|---|
| Pre-release SEO diagnosis | [.agents/skills/laravel-seo-audit/SKILL.md](.agents/skills/laravel-seo-audit/SKILL.md) |
| Editorial priorities and topic clusters | [.agents/skills/content-strategy/SKILL.md](.agents/skills/content-strategy/SKILL.md) |
| Navigation, internal links, migration URLs | [.agents/skills/site-architecture/SKILL.md](.agents/skills/site-architecture/SKILL.md) |
| AEO and generative discovery | [.agents/skills/ai-seo/SKILL.md](.agents/skills/ai-seo/SKILL.md) |
| Structured data | [.agents/skills/schema-review/SKILL.md](.agents/skills/schema-review/SKILL.md) |
| Regions and hreflang | [.agents/skills/international-seo/SKILL.md](.agents/skills/international-seo/SKILL.md) |
| XML discovery | [.agents/skills/sitemap-review/SKILL.md](.agents/skills/sitemap-review/SKILL.md) |
| Article evidence and ranking completeness | [.agents/skills/article-quality/SKILL.md](.agents/skills/article-quality/SKILL.md) |
| Editorial images and performance | [.agents/skills/image-seo/SKILL.md](.agents/skills/image-seo/SKILL.md) |

Keep original project-specific skills as Laravel safeguards. Use the focused community-backed skill for the current task rather than loading every upstream reference. Do not infer a ranking benefit from an experimental format or treat upstream time-sensitive claims as verified primary research.

For owner-requested keyword/competitor research use [.agents/skills/openseo-research/SKILL.md](.agents/skills/openseo-research/SKILL.md). Discover actual tools first; Open SEO was not exposed at the last check. Never substitute invented metrics or call unresearched drafts “high-ranking.” Business model: ads + affiliate revenue; priority audience: global English-speaking readers interested in Africa.

## Official OpenSEO public skills

The twelve public OpenSEO workflows are now present under `.agents/skills/` from `every-app/open-seo/plugins/openseo/skills/`. They are unchanged; revision, license, and hashes are in `.agents/openseo-installation.json`. The previous local audit adapter is preserved as `laravel-seo-audit`; `seo-audit` is now the official OpenSEO workflow. Read [OpenSEO setup status](docs/OPENSEO-SETUP.md) before assuming tools are connected.

Installation is partial: public skills and project MCP configuration are staged, but managed global plugin settings are read-only, the endpoint is blocked by current egress, OAuth is untested, and this session exposes no OpenSEO tools. A next chat must discover the actual skills/tools and verify only free `whoami`/`list_projects` reads before research. Do not run SEO workflows or paid research just to test installation. Avoid duplicate manual/plugin copies once a native plugin becomes available; remove only hash-matching manual OpenSEO copies after successful native discovery, preserving other skills and integrations.

## Browser review environment

After the 2 November frontend 500 recovery, the public review server and scheduler serve a fixed source snapshot under `/workspace/review-tools/approved-review-20261102-3`. See the recovery section at the top of docs/PREVIEW.md. Do not edit the active snapshot or switch review back to the development checkout. Test/build a new snapshot with scripts/review-snapshot.py and validate it before promotion. Shared review storage preserves accounts and article decisions; schema changes still require compatibility checks and a backup.

The owner requested a shareable staging link for visual review. This authorizes the temporary review environment, not a production launch or search submission. `npm run review` starts a distinct review instance on port 8002 with ignored state under `storage/framework/review/`, its own key/cookie/database, APP_DEBUG=false, noindex/no-store, and a review banner. Set REVIEW_URL to the actual public HTTPS origin. Follow docs/PREVIEW.md for the retained proxy-aware Tunnelmole helper and restart steps; do not send an expired link without rechecking. Keep the review server and tunnel sessions alive. Do not modify or expose the development database when testing the public review. No emails are sent. Scope URL forcing to review mode only.

## Development-to-launch roadmap

Use docs/LAUNCH-PLAN.md as the current delivery task list. The owner wants the public platform and tested super-admin/author backend completed before bulk content production, then at least 160 unique accepted articles for launch. Existing JSON seeds, regional copies and drafts do not automatically count. Track evidence and update task status only when acceptance criteria pass. The current planning task does not authorize production release. Begin implementation with authentication/policies, super-admin/author roles and the database-backed article/revision CMS. Use docs/CONTENT-REGISTER.csv until the CMS supplies the content dashboard; no keyword metrics or article priorities may be invented in place of OpenSEO research.

## Staff and editorial implementation

Staff UI is at /staff/login and /staff. Use native Laravel authentication/policies/Blade per docs/ADR-001-STAFF-WORKSPACE.md. StaffRole has author and super_admin; never accept role/activation/ownership/publication fields through author mass assignment. Every article save creates a draft revision; the public pointer changes only after explicit approved publication. Author sessions can access their own drafts, while super admins review and publish. State transitions and team changes must be audited. Staff and account-setup pages always use noindex/no-store.

Owner account setup is invitation-only; one-time links are secret capability URLs in private ignored storage, not default passwords. Do not print their values or copy them into documentation/screenshots. `staff:invite-admin` saves a private link without creating a password. In the current review, outbound email is disabled, so the authenticated administrator can copy author invitation links. Use scripts/review-artisan.sh for deliberate operations on the isolated review database and the real REVIEW_URL; do not apply its invitations to a different database.

Current regression baseline: 42 PHP tests / 1,607 assertions; nine browser tests. Browser fixtures are test-only, must remain guarded to the disposable /tmp database and are never run by normal/review seed commands. Review/cache and browser cache/session stores are separate and persistent across their HTTP requests so throttling actually works. Two-factor authentication, mail delivery/inbox verification, media/rich editing, author profiles, autosave/restoration, scheduling and production database testing remain launch blockers; do not mark those roadmap tasks done.

## Current article and search management

The article inventory includes CMS records and a separate admin-only repository example list. SeedArticleImporter is idempotent and never overwrites existing CMS content. UI import requires explicit privacy acknowledgment: a CMS draft masks its public JSON example. Never bulk import into shared review just to populate a dashboard. Assignment is admin-only, creates a new draft revision, clears approval and preserves the published pointer/byline; reject stale revision IDs.

Sidebar navigation and article filters/search/sort/pagination are server-rendered. Search Console Pages CSV import is an operational, admin-only manual integration without Google API credentials. OpenSEO MCP verification uses only a server-side OPENSEO_API_KEY, fixed official HTTPS endpoint, free whoami/list_projects, protected password-confirmed/throttled action and sanitized errors. Native agent MCP availability is separate. Do not claim live OpenSEO verification or research results until an actual credential-backed check passes. Never collect keys in chat or planning fields. See docs/ADMIN-UX-HARDENING.md. Regression baseline: 51 PHP tests / 2,952 assertions and 11 browser tests.

## Automated CMS and GPT drafting update

The owner explicitly authorized importing all repository articles and automating future sync. All 13 are now private CMS drafts in review; any existing editorial record was preserved. Manual import UI is removed. This supersedes previous warnings against the now-authorized shared-review import, but does not authorize publication. content:sync on review startup/hourly schedule creates only new private records; content:optimize-onsite/daily schedule fills missing metadata only in new drafts, never published pointers. New revision metadata defaults are applied by a creating observer. Keep body/source/factual editing separate from deterministic metadata defaults.

See docs/EDITORIAL-AUTOMATION.md. Secure admin credential forms use encrypted casts, hidden values, password confirmation, CSRF, throttling and dontFlash secret fields. Environment credentials take precedence. Never print/decrypt real keys in tool output or screenshots. Native agent MCP availability is separate from the application's server-side client.

GPT endpoint is super-admin-only, creates private revisions via EditorialWorkflow, validates structured Responses API output, records provenance, rejects duplicate requests per source revision, and never publishes. Missing provider data remains null. No actual OpenSEO research/GPT expansion has run without configured credentials. Protect existing publication pointers and approval gates during bulk operations/restoration; stale batches must roll back completely. Current validation: 63 PHP tests / 3,058 assertions; 12 full-suite browser tests plus one focused credential browser test. Review scheduler runs in its own process; production cron/worker deployment is still required.
