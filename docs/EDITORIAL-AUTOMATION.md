# Editorial management, SEO automation and GPT drafting

**Live connection verified — 2 November 2026:** the encrypted review credential completed MCP initialization, discovery of 81 tools, and successful free `whoami` / `list_projects` reads. The existing Africa Ranked project was found and its context read. This supersedes earlier statements that the review credential was missing or unverified. No paid keyword/competitor research was used as a connection test. The earlier failure could not be reproduced, so its root cause is unconfirmed. Diagnostics now distinguish authentication, access, rate limiting, transport, provider availability, missing tools and protocol errors without leaking upstream bodies or credentials.

Delivered 2 November 2026. Supersedes the earlier manual-import workflow. The platform remains in review; production release has not been performed.

## Current CMS state

All 13 repository articles are now CMS records in the isolated review database. The existing record was preserved and 12 new records were added as private drafts owned by the verified owner administrator. A repeat sync inserted zero records. All 13 are currently unpublished; the public reader home remains available, while their public article URLs are excluded until reviewed publication. Open an article's private preview from Staff → Articles to inspect it before publishing.

Automatic metadata backfill created one additional private revision for the pre-existing record. A second optimization pass changed nothing. No body was expanded, no factual claim was altered, and no keyword/competitor research was fabricated. `content/research/article-expansion-queue.json` contains local structural findings and explicitly unresearched expansion states.

## Automatic repository and onsite processing

- `content:sync`: adds only new slugs, preserves CMS edits/history/ownership/publication and creates private drafts. Optional `--owner` or `CMS_IMPORT_OWNER_EMAIL` must identify an active verified super admin; without it, newly discovered records remain unassigned until an admin assigns them.
- Review startup runs sync and onsite backfill after migrations. Production deployments should run these commands deliberately after migrations against the selected database. No HTTP GET performs editorial imports.
- Hourly scheduled repository sync and daily metadata backfill are registered in `routes/console.php`. No provider calls or paid research are scheduled.
- `content:optimize-onsite`: fills missing search metadata in new draft revisions, resets editorial approval, preserves body/source notes and never changes a published pointer. New revisions receive title/description fallbacks automatically through a model-creation observer. Keyword research and image descriptions are not invented.
- Canonicals, social metadata, matching FAQ/schema markup, feeds and sitemaps continue to derive from approved public revisions. Preview noindex remains intentional.

Review scheduler is running separately via `npm run review:scheduler` with the same isolated key/database/cache/session configuration. It is process-bound, like the temporary tunnel. Restart it alongside the review server after a workspace restart. For persistent production scheduling, configure the host to run `php artisan schedule:run` every minute with the correct environment; do not assume the development process is durable hosting.

## Advanced article controls

CMS and pending repository records now have independent pagination, 10/25/50 row sizes and query-preserving page links. Custom accessible pagination fixes the previous vendor-template visibility problem. Filters cover text, category, working status, assigned author and publication state, including live articles with a newer working revision. Sort by updated date, creation date and title in either direction. Author scope remains enforced server-side.

Selected CMS rows can be submitted for review or, for super admins, withdrawn in an explicitly confirmed batch of at most 50. Each item carries its saved revision ID. Policy checks and workflow validation apply to every item; a stale/invalid/unauthorized item rolls back the entire batch. Bulk publishing is intentionally unavailable.

The editor adds heading/list/link insertion, unsaved-change warnings, search-title/description overrides, target-query notes, descriptive image text and editable visible FAQs. Markdown links are supported as safe standalone blocks, not executable HTML. FAQs validate questions/answers and remain aligned with public schema. Blank search overrides use automatic title/introduction defaults. SEO fields affect public metadata only after approved publication.

Per-article SEO analysis uses saved revisions: effective search-preview metadata, word/headings/link/FAQ counts, missing essentials, heading hierarchy, title/description length heuristics, supported internal-link targets, research/author/review state and numbered top-N completeness warnings. It does not measure demand, live indexing, Core Web Vitals or ranking probability. Findings are shown in the inventory. It cannot validate factual accuracy or media rights.

Paginated revision history links to side-by-side field comparison. Restore copies historical body/metadata/FAQs into a new unapproved private revision with the current owner; published content and historical versions remain intact. Cross-article revision access and stale restoration requests are rejected.

## Secure credentials

Staff → Integrations → Secure provider credentials accepts OpenSEO and OpenAI keys directly over the review HTTPS connection. Recent password confirmation, admin-only policies, CSRF and throttling apply. Values use Laravel encrypted casts, are hidden from serialization, never redisplayed or placed in audit records, and are excluded from validation input flashing. Removing a credential clears the stored copy; an environment key still takes precedence. Keep the application encryption key stable and back it up securely. This is instance-specific storage, not automatic synchronization with production.

Environment alternatives remain `OPENSEO_API_KEY`, `OPENAI_API_KEY`, `OPENAI_ARTICLE_MODEL` (default `gpt-5-mini`). Saving a key performs no remote call. OpenSEO verification runs only documented free discovery/account/project reads. No usable provider credentials were configured at the last delivery check; authenticated live integration/research/generation has not been claimed.

## GPT editorial endpoint

POST `/staff/articles/{article}/curate`, through the editor's GPT editorial assistant, is super-admin-only, password-confirmed, CSRF-protected and rate-limited. Uses the official OpenAI Responses API at `https://api.openai.com/v1/responses` with a strict JSON schema, `store: false`, bounded output/response size, connection/read timeouts, a fixed destination and redirects disabled. `store: false` is not a zero-retention guarantee; provider data policies still apply.

The admin supplies a brief and explicitly confirms sending the saved draft/source notes and matching report data to OpenAI with usage charges. The request includes the actual saved target query and a matching imported Search Console page row if available. It does not pretend that OpenSEO research exists: absent structured research is null; actual OpenSEO observations can be recorded in source notes for use by the draft assistant. No web browsing or research call happens automatically.

The prompt prioritizes evidence, natural reader intent, direct answers, clear structure, source integrity and honest uncertainty. Generated titles must not promise unsupported rankings. Output is validated and saved through the same revision workflow. Images, ownership, canonical policy and published pointers cannot be supplied by the model. New drafts retain source notes plus AI provenance and require human review. This cannot guarantee that model output is factual or high ranking.

One provider request is allowed per saved source revision. Generation records record running/completed/failed status and the resulting revision ID. Failures do not retry automatically, reveal provider responses or overwrite the article. Concurrent editor changes reject the generated save. Provider billing may still apply after timeouts or stale-edit rejection. Save a new revision before intentionally retrying. Calls are bounded synchronous requests (up to 60 seconds); durable asynchronous generation, provider budget enforcement and worker operations remain further launch-hardening work.

Official reference inspected: https://platform.openai.com/docs/api-reference/responses/create. Protocol behavior is fixture-tested, not proof of a live account call. No real key was used or paid generation executed at delivery.

## Research and launch boundaries

OpenSEO research and evidence-led expansion are blocked on a usable credential/connection and actual tool schemas. Follow `.agents/skills/openseo-research/SKILL.md`: identify projects, inspect real available tools/costs, research query demand and competing pages, retain source/date/locale evidence, create article-specific briefs, then expand saved drafts and verify claims. Do not substitute GPT guesses for provider data. The current 13 drafts do not count toward the 160 accepted launch articles.

Remaining launch gates include super-admin 2FA/recovery, real email/inbox flows, production database/concurrency testing, media uploads/rights management, richer table/media editing, scheduling/queue durability, backup/restore monitoring, stable hosting, legal/privacy/consent and reviewed content acceptance. No production indexing or launch was enabled.

## Validation

63 PHP tests / 3,058 assertions passed. The full 12-test browser suite passed, plus a focused browser test of encrypted credential setup/removal (13 distinct browser checks). Tests cover metadata publication isolation, FAQs, link safety, pagination/filter scope, revision restore, atomic bulk rollback, GPT validation/failure/deduplication, encrypted secret storage/no flashing, idempotent sync and safe onsite backfill. Asset build, JavaScript syntax, Blade compilation, formatter and whitespace checks passed. Shared-review database backups preceded the authorized import; actual owner passwords and private keys were not used in test fixtures.


## Gemini verification — 2 November 2026

The saved review Gemini credential was detected without displaying its value. Model metadata access succeeded, but live generation with the previous `gemini-2.5-flash-lite` default returned Google's HTTP 404 notice that the model is no longer available to new users. The provider named `gemini-3.5-flash-lite` as its replacement. A minimal structured generation through the application client succeeded with the replacement; no article text was sent or editorial record modified during verification. The configured default now uses that model, with `GEMINI_SEO_MODEL` available as a server override.

Three targeted SEO automation tests passed (24 assertions), covering private revision creation, validated provider output, quota failures without fallback, and author access. The review snapshot was promoted after homepage/login checks, retaining its database, encryption key and sessions.

Use Articles → SEO tools → Improve with Gemini AI to create SEO metadata in a new draft. Publication remains explicit. Saving a key does not schedule background Gemini rewrites; the scheduled metadata defaults remain local and do not consume API quota. Live verification does not establish that Google's billing is disabled or guarantee free usage.
