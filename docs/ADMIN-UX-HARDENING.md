# Admin workflows and integration hardening

**Current update:** [Editorial automation and GPT drafting](EDITORIAL-AUTOMATION.md) supersedes earlier manual-import and environment-only credential guidance. All 13 review articles are now private CMS drafts; manual import controls have been removed.

Updated 2 November 2026. This is implemented Laravel application work; the whole platform still has outstanding launch gates.

## Evidence and design decisions

Product Design index, audit, user-context and get-context instructions informed this review. Saved-context preflight found no saved profile. No native Browser tools were exposed; installed Chromium/Playwright captured the real reference and disposable local application. A shared critical-overrides resource referenced by the plugin was unavailable. No claim of a complete plugin-prescribed audit or accessibility certification is made.

Reference inspected: https://sa.avidtemplates.com/ (Super Admin 3.2, Project Management). Only layout ideas were borrowed: persistent sidebar, grouped navigation, compact operational summaries, page tools and dense tables. No template source code, artwork, commercial assets or fabricated charts were copied. African Ranked's amber/stone design and Laravel authorization remain.

Captured flow:
1. Reference dashboard: clear persistent navigation and dense operational layout. Evidence: `artifacts/design/01-reference.png`.
2. Existing article inventory: misleading empty CMS state despite 13 repository examples. Evidence: `02-current-articles.png`. Existing editing was policy-controlled but examples were invisible, search/filter and assignment were missing.
3. Hardened inventory: distinct CMS and repository lists; search/category/status/source filters, stable sorting, query-preserving pagination and explicit edit/preview actions. Evidence: `03-articles-desktop.png`.
4. Mobile inventory: collapsible native navigation, labeled filters and a horizontally scrollable table contained within the page. Evidence: `04-articles-mobile.png`.
5. Integration workspace: actionable MCP verification and Search Console import rather than reported fake connection states. Evidence: `05-integrations-mobile.png`.

Accessibility work includes real table headers/captions, visible form labels, named row actions, focus indicators, skip navigation, native details controls and semantic status/error messages. Screenshots cannot establish screen-reader compatibility or WCAG compliance; formal assistive-technology auditing remains outstanding.

## Article operations

Super admins see every CMS article and unimported repository example. Authors see only owned CMS records. A repository example can be imported after explicit acknowledgment that it becomes private and disappears from public preview until reviewed publication. Import is idempotent, preserves the body/FAQs/regions, never overwrites existing CMS records, records provenance and audits creation. The authenticated administrator becomes its initial owner; this does not verify original claims or authorship.

Assignment is super-admin-only. It creates a new draft revision with the chosen active/verified staff member's byline and preserves the previous published revision. Approval/review metadata is not copied. Stale requests are rejected and changes are audited. No automatic mass import or publication was performed on the shared review.

## OpenSEO MCP

Official source: https://openseo.so/docs/mcp, inspected 2 November 2026. Documents hosted endpoint `https://app.openseo.so/mcp`, Streamable HTTP and `Authorization: Bearer` API keys for headless environments. This is separate from exposing native OpenSEO tools inside the Codex client.

Configure `OPENSEO_API_KEY` only in the server's protected environment or private `.env`, never a VITE variable, planning field, Git file or chat message. If Laravel config is cached, refresh it using the hosting deployment procedure. Do not generate a new application key; existing encrypted sessions and data depend on it.

Integrations → Verify MCP connection requires a recently confirmed super-admin password and rate limiting. The server initializes MCP, negotiates a supported protocol version/session, discovers tools, then invokes only free `whoami` and `list_projects` checks. Supports JSON and SSE responses, bounded response sizes, timeouts, fixed endpoint and no credential-bearing redirects. Provider failures are reduced to safe messages; raw results, account details, keys and session IDs are not persisted or displayed. Discovery count, last attempt and success/failure are stored. No keyword or competitor tool runs as a connection test.

Protocol behavior is covered with HTTP fixtures. No real key has been supplied to the server and an authenticated live check has not passed; this integration is not represented as live-verified. Research jobs, saved research reports, retries/queues and credit controls remain further implementation work.

## Search Console without API credentials

Google Search Console's normal web interface works without an API key. Automated in-app Google reporting requires authorization; this implementation instead provides direct Google Console access plus a working manual report-import path.

Export the English Performance → Search results → Pages CSV. Upload with its property (`sc-domain:example.com` or an HTTPS URL-prefix property) and reporting period. Accepted headers are exactly `Top pages,Clicks,Impressions,CTR,Position`. Max file size 2 MB; max 5,000 rows. Import validates numeric values, counts, HTTP(S) page URLs, duplicate rows, date range and known format. Uploaded file is never moved to public storage. Identical report content/property/period is deduplicated. The private database stores parsed data and uploader, and an audit event records new imports.

Analytics shows the latest uploaded report's property/date/source, exported page totals, computed CTR and top 20 pages. Export rows are not assumed to equal Google's property totals. Manual exports are owner-provided evidence, not independently verified or live-synced data. No submission, ownership verification, indexing request, analytics tracking or paid service activation occurs.

## Release boundaries

No default account, password reset, owner-session replacement or production launch was performed. Owner account and existing CMS records remain. Review DB was backed up before additive migrations. Outstanding launch gates include 2FA, mail delivery, media/rich editor operations, intended production-database/concurrency checks, durable infrastructure, operational monitoring and accepted editorial content. See LAUNCH-PLAN.md.

Validation at delivery: 51 PHP tests / 2,952 assertions and 11 browser tests passed. Build, formatter and diff whitespace checks passed. Browser tests cover the reviewed publishing cycle, CSRF expiry recovery, repository import, search/filter workflow, ownership isolation, assignment and mobile navigation/layout. MCP tests use simulated provider responses; no live authenticated provider verification is claimed.

## Captured walkthrough

1. Reference navigation — clear hierarchy and dense operational layout.

![Reference dashboard](../artifacts/design/01-reference.png)

2. Previous article list — blocked discovery of existing repository articles.

![Previous article inventory](../artifacts/design/02-current-articles.png)

3. New desktop inventory — working repository visibility, filters and management actions.

![New desktop article inventory](../artifacts/design/03-articles-desktop.png)

4. Mobile inventory — contained table scrolling, clear filters and native navigation; screen-reader audit remains outstanding.

![Mobile article inventory](../artifacts/design/04-articles-mobile.png)

5. Mobile integrations — actionable import and connection checks; real MCP authentication still awaits a privately configured key.

![Mobile integrations](../artifacts/design/05-integrations-mobile.png)
