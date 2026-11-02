# African Ranked: development-to-launch task list

Planning baseline: 2026-10-27. Business: display advertising plus affiliate revenue. Initial audience: global English-speaking readers interested in Africa.

**Launch target: at least 160 unique, complete, editorially approved, technically validated articles.** Regional copies, category pages, empty drafts, and incomplete inherited articles do not count. Commission approximately 180 researched briefs so weak or redundant ideas can be rejected without lowering the launch standard. This is a capacity target, not a researched keyword allocation or a promise of rankings/revenue.

This document plans a launch; it does not authorize a production release, paid services, search submissions, or campaigns now. Continue development and staging review. Final release requires the founder's approval of a concrete release candidate.

## Delivery update — 2 November 2026

This dated update supersedes older implementation/status counts below. It does not authorize indexing or public launch.

| Workstream | Current evidence | Next acceptance gate |
|---|---|---|
| Railway staging | Public URL responds, but serves the old React/Caddy build; staff/login and up are SPA fallbacks; indexing is currently allowed | Deploy the reviewed Laravel branch with PostgreSQL, noindex, worker and scheduler using [the Railway runbook](RAILWAY-DEPLOYMENT.md) |
| Laravel operations | Native roles, MFA, immutable article drafts/review/publication, WordPress-style list, pagination/search/quick edit, homepage featuring, pages and regions implemented | Founder exercises the flows on actual Railway, with an invited author and real secure sessions |
| Bulk production tools | Explicit bulk publish confirmation; durable OpenSEO→Gemini jobs with revision/access checks, rate/daily limits and per-article results; metadata editor and revised SEO workspace | Validate a small job batch on Railway; monitor worker failures and provider credits |
| Research and content | Actual OpenSEO briefs attached to 13 review articles; metrics for 11 candidate queries; SERPs for 13; 144 related safari ideas; national-market limits documented | Repair/expand the existing 13 with verified facts, methodology, sources and image rights; then commission researched briefs across the remaining categories |
| Client-facing completeness | Server-rendered reading/discovery, dynamic regional navigation, editable pages and homepage | Finish public search/navigation at 160-article scale, author profiles, trust/legal pages, richer media/editorial UX and performance checks |
| Revenue and measurement | Ads+affiliate strategy established; Search Console Pages CSV supported | Real analytics/consent, actual affiliate relationships/disclosures, suitable ad placements and measured reader experience |
| Operations and release | Docker startup checked with disposable PostgreSQL; local backend/browser regressions recorded in the deployment evidence | Actual Railway backups and restore rehearsal, persistence on redeploy, mail/inbox testing, monitoring, rollback and custom-domain acceptance |
| Launch inventory | 160 accepted unique complete articles remain the release target; inherited material and AI metadata do not qualify automatically | Founder accepts the concrete release candidate before indexing is enabled |

Execution order: (1) correct the staging deployment and establish persistence; (2) validate admin/author production workflows and recovery; (3) repair the first 13 researched articles as the editorial pilot; (4) scale researched briefs and verified drafts to at least 160 accepted articles; (5) complete trust, monetisation, measurement and performance checks; (6) rehearse restore/rollback, test the custom domain and obtain release acceptance. Infrastructure and product gaps can proceed alongside the pilot; bulk publishing must not substitute for acceptance.

## Historical planning baseline

| Area | Current evidence | Remaining work |
|---|---|---|
| Public website | Laravel/Blade pages, six category definitions, eight editions, article reading, Motion/Lottie, metadata and discovery routes | Complete client-facing flows, search, author pages, legal/trust pages, and larger-content navigation |
| Review environment | Temporary HTTPS review, isolated test database/key/cookie, noindex/no-store, debug off, visible review banner | Stable access, reproducible deployment, recovery and team review workflow |
| Publishing | Six inherited article records in JSON | Database-backed CMS, revisions, moderation, media, scheduling, and publishing guards |
| Accounts | Default Laravel User model and user table scaffold only | Actual login, invitations, recovery, roles, policies, super-admin and author interfaces |
| Content | Six inherited records with incomplete rankings and unverified claims/bylines | Research, full factual review, then qualification under the launch standard; none accepted into the launch count yet |
| OpenSEO | Official public skills staged; connection configuration prepared | Actual tool discovery, authentication where prompted, and successful free connection/project reads; research is not verified |
| Testing | Last recorded baseline: 14 PHP tests / 1,399 assertions; six browser tests from the earlier preview suite; public-link smoke checks passed | Role, CMS, publication, concurrency, production-database, recovery and release tests; existing tests do not prove these missing capabilities |

Owners below are workstream responsibilities, not claims that staff have already been hired: **Engineering**, **Editorial**, and **Founder**. Task statuses start open unless explicitly marked baseline complete. Update checkboxes only after acceptance evidence exists. Task dependencies are implementation dependencies, not a requirement to stop independent work.

## Delivery order and gates

| Milestone | Work | Required exit evidence |
|---|---|---|
| M1 | Super-admin and author foundation, CMS and publication workflow | Both role journeys pass browser and server-side authorization tests |
| M2 | Full reader experience and operational backend | An approved article moves from draft to the client-facing site without code edits; incomplete/private content stays private |
| M3 | Research-backed 10-article pilot | Ten distinct, fully reviewed articles meet the article acceptance checklist and can be managed through the CMS |
| M4 | Content expansion with continuous QA | Accepted totals progress through 25, 60, 100 and 130 without bypassing quality checks |
| M5 | 160 accepted articles and release candidate | Full crawl, permission matrix, browser suite, production-database checks and restore rehearsal pass |
| M6 | Founder-reviewed launch | Production configuration and rollback are ready; founder gives release approval, then launch and monitor |

**Critical path:** accounts/policies → CMS/revisions → safe publishing → 10-article pilot → measured editorial production → 160 accepted articles → release rehearsal → approval. OpenSEO connection/research, legal copy, hosting selection and brand/author evidence can proceed alongside backend work. OpenSEO access does not block implementing accounts or the CMS.

## Phase 0 — review and engineering foundations

- [x] **FND-01 · Engineering:** establish the Laravel public-site baseline and isolated temporary review. Evidence: existing application, tests, and `docs/PREVIEW.md`. This is not production infrastructure or a completed CMS.
- [ ] **FND-02 · Engineering:** make review deployment reproducible and access stable for the development cycle. Acceptance: documented start/restart/health checks, correct HTTPS assets/cookies, environment isolation, noindex, and staging access appropriate for author/admin testing. Do not assume the temporary tunnel is permanent hosting.
- [ ] **FND-03 · Engineering:** establish a recoverable Git baseline and CI. Acceptance: preserve current migration work, exclude secrets/databases/generated artifacts, and run build, PHP/browser tests and formatting in CI. Creating/pushing/merging remote changes follows the authorized repository workflow.
- [x] **FND-04 · Engineering + Founder:** record architecture choices. Default: preserve Laravel/Blade public pages; Laravel policies enforce permissions; evaluate a maintained Laravel admin toolkit such as Filament with a small compatibility/UX spike before adoption. Acceptance: chosen packages support the actual PHP/Laravel versions and remain testable without changing the public design.
- [ ] **FND-05 · Engineering + Founder:** choose the intended production database, file storage, queue, email and hosting services. Acceptance: documented choices, environment requirements, separate staging/production state and cost expectations; secure settings hold credentials. A decision record is not a purchase or deployment.

**Gate:** repeatable development/review workflow and agreed architecture. Keep all six inherited article URLs available while migrating their storage.

## Phase 1 — accounts, roles and access control

- [ ] **IAM-01 · Engineering · depends FND-04:** implement invite-only staff accounts, login/logout, email verification and password reset. Acceptance: no public staff registration, no self-selected privileged roles, no default production passwords; invitation/recovery mail is testable in a staging mailbox.
- [x] **IAM-02 · Engineering · depends IAM-01:** implement the super-admin/author permission model using server-side policies. Acceptance: direct requests, changed IDs, mass assignment and bulk actions cannot bypass roles; see the permission matrix below.
- [x] **IAM-03 · Engineering · depends IAM-02:** add controlled first-super-admin provisioning and user management. Acceptance: super-admin can invite/suspend authors, revoke sessions, and manage roles; an author cannot do so; the last active super-admin cannot be accidentally removed. Remove demo/test account seeding from production workflows.
- [ ] **IAM-04 · Engineering · depends IAM-01:** protect privileged sessions. Acceptance: required super-admin two-factor authentication/recovery, login/reset throttles, secure cookies, session expiration and reauthentication for sensitive changes; suspended users lose access.
- [ ] **IAM-05 · Engineering · depends IAM-02:** implement auditable account and editorial actions. Acceptance: actor, action, target and timestamp are recorded; passwords, tokens and unnecessary subscriber data are not logged; authors cannot edit the audit trail.
- [ ] **IAM-06 · Engineering · depends IAM-02:** build real author profiles and ownership. Acceptance: public display name, bio and optional verified links are separate from private account details; no fabricated credentials; authors can edit permitted profile fields only.
- [ ] **IAM-07 · Engineering · depends IAM-03, IAM-04, IAM-06:** test and demonstrate both roles. Acceptance: passing positive and negative policy/browser cases, including author-A versus author-B isolation, anonymous access, suspended accounts, forged role/status changes, invitation expiry and session revocation.

**Gate M1a:** founder can review functioning super-admin and author accounts through staging; permission tests pass. A hidden sidebar item is not authorization evidence.

### Minimum role matrix

| Action | Anonymous reader | Author | Super admin |
|---|---|---|---|
| Read published articles | Yes | Yes | Yes |
| Read a draft | No, except an explicitly issued expiring preview | Own/assigned permitted drafts | All drafts |
| Create an article | No | Own draft | Any article |
| Edit article text/media/SEO proposals | No | Own draft or new revision; never another author's record | All authorized records |
| Submit/request changes | No | Submit own work | Review, request changes, reassign |
| Approve, schedule, publish or unpublish | No | No | Yes, with validation and audit |
| Modify a published article | No | Propose a revision; live version remains unchanged | Approve and publish a new version |
| Manage categories, site SEO, ad/affiliate settings or users | No | No | Yes |
| Read/export subscriber data | No | No | Restricted, audited super-admin operation |
| Change ownership/roles/status by a forged request | No | No | Only explicitly authorized operations |

## Phase 2 — CMS and publishing backend

- [ ] **CMS-01 · Engineering · depends FND-05:** design database-backed articles, authors, categories, media, sources, revisions and publication records. Acceptance: unique slugs, referential integrity, relevant indexes and migration/rollback tests on the intended production database.
- [ ] **CMS-02 · Engineering · depends CMS-01:** migrate the six JSON records idempotently. Acceptance: slugs, bodies, dates, categories, regional availability and existing routes are retained; inherited articles enter an explicit review state, not automatic launch approval; repeat import does not duplicate records.
- [ ] **CMS-03 · Engineering · depends CMS-01, IAM-02:** provide a usable structured article editor. Acceptance: headings, paragraphs, complete ranked items, lists, tables, quotes, linked sources, FAQs and media/captions survive save/reload and server rendering; unsafe markup/URLs are rejected or sanitized.
- [ ] **CMS-04 · Engineering · depends CMS-03:** implement draft → in review → changes requested → approved → scheduled/published → archived transitions. Acceptance: only super-admin publishes; substantial post-approval edits invalidate approval; validation prevents skipped states and incomplete publication.
- [ ] **CMS-05 · Engineering · depends CMS-03:** add autosave, version history and conflict handling. Acceptance: refresh/recovery does not silently lose work; stale simultaneous edits produce a visible conflict; restoring a revision is auditable; author changes to published content never silently replace the live version.
- [ ] **CMS-06 · Engineering · depends CMS-04:** add authenticated and expiring review previews. Acceptance: draft URLs are not public/searchable or listed in feeds/sitemaps; expired/revoked previews fail; unpublished text does not leak through search, API responses, related cards or page caches.
- [ ] **CMS-07 · Engineering · depends CMS-01:** build media management. Acceptance: file-type/size/dimension checks, safe filenames, storage access controls, image variants, alt text, captions, source/license fields, and malicious-upload/ownership tests; SVG/HTML uploads cannot execute scripts.
- [ ] **CMS-08 · Engineering · depends CMS-04, FND-05:** implement scheduling, queues and publication cache invalidation. Acceptance: timestamps stored in UTC with an explicit editorial display/scheduling timezone (initially Africa/Nairobi); idempotent jobs publish once, recover from failure and update site/search/sitemap/feed consistently.
- [ ] **CMS-09 · Engineering · depends CMS-04:** add archive/unpublish, slug changes and redirects. Acceptance: existing links do not silently break; redirects avoid loops/chains; retractions and changes are audited; removing a record follows the chosen 404/410/redirect policy.
- [ ] **CMS-10 · Engineering · depends CMS-03:** add admin/author dashboards and filtering. Acceptance: authors see their assignments, status and review feedback; super-admin sees ownership, publication queue, missing-source/SEO flags, article counts and bulk actions with per-record authorization.
- [ ] **CMS-11 · Engineering · depends CMS-02 through CMS-10:** add publishing regression and failure tests. Acceptance: failed jobs, missing media, invalid source data, editing conflicts and cache refresh cannot expose a draft or corrupt the live article; migrations and a six-record import pass on the production database engine.

**Gate M1b:** demonstrate author drafts/submits, super-admin requests changes/approves/publishes, reader sees the correct article, and a later author edit remains a private revision. No code edit is needed to publish.

## Phase 3 — complete the client-facing platform

- [ ] **WEB-01 · Engineering · depends CMS-02, CMS-04:** connect all public pages to published CMS data. Acceptance: homepage, categories, article pages and eight edition paths retain the design/routes; drafts and future-scheduled articles never appear anonymously.
- [ ] **WEB-02 · Engineering · depends WEB-01:** implement useful search, category filtering and pagination for 160+ records. Acceptance: readers can find content by title/topic; keyboard/mobile work; empty/no-result states are useful; pagination preserves filters and crawlable links; search/filter indexing policy is deliberate.
- [ ] **WEB-03 · Engineering · depends IAM-06, WEB-01:** build public author pages, bylines and editorial trust pages. Acceptance: article-author attribution is real and linked; About, Contact, Editorial/Ranking Methodology, Corrections and Affiliate Disclosure have working routes and approved copy; no placeholder links.
- [ ] **WEB-04 · Engineering + Editorial · depends WEB-01:** complete article-reading components. Acceptance: table of contents, comparison/ranking tables, source links, useful FAQs, related articles, share behavior, image captions and mobile table overflow work with real long content.
- [ ] **WEB-05 · Engineering + Founder:** complete newsletter delivery or remove the signup offer from the release scope. Default is to ship a real flow: consent record, verified/double-opt-in process, confirmation, unsubscribe, suppression, provider failure handling and truthful messages. Acceptance: end-to-end staging mailbox tests; no false “email sent” states.
- [ ] **WEB-06 · Engineering + Founder:** implement privacy/cookie/terms pages and consent behavior matching the actual integrations. Acceptance: forms and tracking link to approved policies; nonessential tracking follows the selected legal/consent approach. Do not copy generic policies that misstate data use.
- [ ] **WEB-07 · Engineering:** audit and fix responsive/accessibility behavior. Acceptance: mobile/tablet/desktop checks, keyboard/focus, labels/error messages, contrast, reduced motion, image alternatives and accessible navigation on all primary page types.
- [ ] **WEB-08 · Engineering · depends WEB-01 through WEB-07:** run client-facing regression and founder walkthrough. Acceptance: every visible action works or is intentionally removed; no console errors, missing images, dead links, broken forms or draft leaks across the agreed browser/device matrix.

**Gate M2:** founder reviews the complete reader site, author workspace and super-admin workspace in the browser before bulk article production.

## Phase 4 — SEO research, schema and monetization foundations

Research and planning may run alongside Phases 1–3; production publishing waits for the CMS gates.

- [ ] **SEO-01 · Engineering + Founder:** finish OpenSEO connection/discovery and verify free account/project reads plus skill availability. Acceptance: actual calls succeed in the working agent, the correct project/domain is identified, and existing project context is reused. Configuration files alone do not satisfy this task.
- [ ] **SEO-02 · Editorial · depends SEO-01:** record the research market and competitor baseline. Acceptance: global English-speaking audience translated into explicit country/language samples where tools require a geography; SERP competitors, dates and data sources recorded. Do not multiply a US keyword volume into invented global demand.
- [ ] **SEO-03 · Editorial · depends SEO-02:** research and cluster candidate queries. Acceptance: intent, available metrics/units, questions, SERP evidence, seasonality, business fit and existing/new target URL recorded; missing metrics remain unknown; avoid buying duplicate research already saved in project history.
- [ ] **SEO-04 · Editorial · depends SEO-03:** select approximately 180 briefs and a keyword-to-URL/internal-link map. Acceptance: one clear purpose and added value per page, no cannibalizing duplicates, evidence feasible to obtain, balanced reader value and relevant commercial intent. Reserve briefs are not accepted articles.
- [ ] **SEO-05 · Engineering · depends CMS-03, WEB-01:** make SEO metadata and publication validation CMS-driven. Acceptance: truthful title/description/social cards, semantic headings, correct dates, safe JSON-LD, canonical/hreflang policy, redirect behavior and sitemap/feed updates pass initial-HTML and rendered tests. Maintain global canonicals for unchanged regional article bodies.
- [ ] **SEO-06 · Engineering + Editorial · depends SEO-04, SEO-05:** implement internal links and discovery at launch scale. Acceptance: no orphan accepted articles, appropriate hubs/related links, sitemap includes only canonical indexable published URLs, pagination works, and drafts/review URLs stay excluded.
- [ ] **REV-01 · Founder + Editorial:** choose actual affiliate programs and disclosure rules. Acceptance: real account/program terms, suitable products/services, approved link handling, visible disclosures and appropriate sponsored-link attributes; no invented commissions, endorsements, prices or firsthand testing.
- [ ] **REV-02 · Engineering + Founder · depends WEB-06:** implement reserved ad slots and analytics/consent events. Acceptance: no disruptive overlays or avoidable layout shift; view, engagement, newsletter and affiliate-click events validated without duplicate firing or leaking personal data. Production networks/credentials are added only when approved; ad approval and revenue are not guaranteed.

**Research tooling:** use OpenSEO `seo-project-setup`, `keyword-research`, `competitive-landscape`, `competitor-analysis`, `keyword-clustering` and `seo-report` as relevant; use the Corey Haines content-strategy/site-architecture and AEO/GEO adapters for prioritization and presentation. Use the Laravel-specific SEO/schema/international/image skills for implementation checks. Paid research follows the agreed credit/budget boundary; installation checks stay free. Do not auto-publish generated text.

## Phase 5 — article pilot and scale to 160

- [ ] **EDT-01 · Editorial + Founder:** agree the article acceptance standard below, real bylines/reviewers and ranking methodology. Acceptance: the CMS checklist encodes blocking requirements, responsibilities and review records; no arbitrary universal word count substitutes for completeness.
- [ ] **EDT-02 · Editorial · depends SEO-04, EDT-01, CMS-04:** complete a 10-article pilot. Acceptance: research → brief → draft → source verification → copy/SEO review → super-admin approval → CMS preview works end-to-end; measure actual approved-article throughput and rework before expanding.
- [ ] **EDT-03 · Editorial · depends EDT-02:** repair or replace the six inherited articles. Acceptance: each independently passes the new standard, including complete top-N explanations, sources, real attribution and image rights; each counts only once if accepted.
- [ ] **EDT-04 · Engineering + Editorial · depends EDT-02:** add a content register and production dashboard. Acceptance: unique IDs, target URLs, clusters, assignments, source/review status, reviewer/date, revision and publication status; the accepted counter excludes rejected/duplicate/regional-copy/draft records.
- [ ] **EDT-05 · Editorial · depends EDT-02, EDT-04:** reach **25 cumulative accepted articles**. Acceptance: first expansion maintains the pilot standard; reject overlap rather than inflate count.
- [ ] **EDT-06 · Editorial · depends EDT-05:** reach **60 cumulative accepted articles**. Acceptance: cluster coverage, meaningful internal links and source/quality rechecks; fix recurring issues before the next batch.
- [ ] **EDT-07 · Editorial · depends EDT-06:** reach **100 cumulative accepted articles**. Acceptance: authorship, disclosure, media and metadata records complete; representative browser/crawl checks pass.
- [ ] **EDT-08 · Editorial · depends EDT-07:** reach **130 cumulative accepted articles**. Acceptance: no duplicate intent/URL conflicts and no weakened verification thresholds to catch up with the target.
- [ ] **EDT-09 · Editorial · depends EDT-08:** reach **160 cumulative accepted articles**. Acceptance: all 160 individually meet the standard; more than 150 distinct canonical articles are approved for release, without counting regional copies or listing pages.
- [ ] **EDT-10 · Editorial + Engineering · depends EDT-09, SEO-06:** audit the complete corpus. Acceptance: 100% automated record/link/markup checks and 100% individual editorial acceptance records; no outstanding high-severity factual issue, incomplete ranking or unresolved author/media-rights record. Spot checks supplement, not replace, per-article review.

### Article acceptance checklist — required for every counted article

- [ ] A documented target query/intent, research geography/date/source, cluster, target URL and reason this article adds value over competing pages.
- [ ] A complete brief and full article that answers the query; every promised top-N entry is actually ranked and explained. No padded length target, mass paraphrase, or artificial regional duplication.
- [ ] Material factual claims, dates, statistics, prices and attributed quotes verified against recorded sources; source URLs and verification dates retained. Unsupported claims are removed or explicitly limited.
- [ ] Real accountable author and reviewer, truthful expertise/experience claims, clear ranking/comparison criteria, and commercial disclosures where applicable.
- [ ] Licensed/approved images with source/rights records, useful alt text, captions and appropriate delivery/dimensions. No broken or misleading stock-image choices.
- [ ] Helpful headings, direct answer/opening, contextual comparisons and FAQs where useful; valid links to relevant hub/supporting articles and appropriate external sources.
- [ ] Unique accurate title/description/slug/social preview; initial-HTML article text, canonical policy and structured data match visible content. Structured data does not promise rich results or citations.
- [ ] Affiliate links, if present, are real, relevant, properly attributed/disclosed and tested; ads do not obscure reading. No invented products, affiliate relationships or first-hand reviews.
- [ ] Originality, proofreading, mobile/accessibility and link checks complete; factual or safety-sensitive travel/finance/health claims receive appropriate qualified review rather than generic AI approval.
- [ ] CMS ownership, source checklist, reviewer/date and approved revision recorded. Super-admin approves the exact revision intended for release; later edits cannot silently bypass acceptance.

Use [CONTENT-REGISTER.csv](CONTENT-REGISTER.csv) as the initial planning register until the CMS dashboard supersedes it. The six existing rows start as `needs_verification`, not accepted. Exact topic/category quotas are deliberately not assigned before keyword and competitor research; preserve the six-category navigation but concentrate clusters where evidence supports them.

## Phase 6 — release candidate and operational readiness

- [ ] **REL-01 · Engineering · depends IAM-07, CMS-11, WEB-08:** pass the release permission/workflow suite. Acceptance: super-admin and multiple authors tested end-to-end, including forbidden direct requests, draft leakage, suspension/recovery, revisions and failed scheduling jobs.
- [ ] **REL-02 · Engineering · depends FND-05:** prepare production deployment and environment separation. Acceptance: HTTPS/domain, debug off, correct public origin, secure secret handling, least-privilege access, private storage/DB, queues/scheduler, persistent sessions and health endpoints on the intended infrastructure; no development server is used as production hosting.
- [ ] **REL-03 · Engineering · depends REL-02:** prove backup, restore and rollback. Acceptance: restore a database/media backup into a separate environment, verify published article/media counts, rehearse rollback including migration compatibility, and document recovery ownership.
- [ ] **REL-04 · Engineering · depends EDT-09, REL-02:** test realistic performance and reliability. Acceptance: public listing/search/article pages and admin views with 160+ articles and concurrent staff; investigate query amplification, image payloads, cache behavior, queue failures, rate limits and error handling. Record measured lab performance; do not invent prelaunch field Core Web Vitals.
- [ ] **REL-05 · Engineering + Editorial · depends EDT-10, SEO-05:** crawl the full release candidate. Acceptance: internal links/statuses, canonical URLs, metadata, rendered content, author pages, structured data, images, sitemap coverage and draft exclusion validated; fix unintended index/noindex/redirect errors.
- [ ] **REL-06 · Founder + Editorial · depends WEB-03, WEB-05, WEB-06, REV-01, REV-02:** approve legal, trust, advertising/affiliate and newsletter behavior. Acceptance: copy and integrations describe reality; incomplete offers are not visible; consent/unsubscribe and actual partner rules are respected.
- [ ] **REL-07 · Engineering · depends REL-02:** configure monitoring and measurable outcomes. Acceptance: uptime/error/queue alerts, source-safe logs, analytics event checks and runbooks; establish a baseline after release, not fabricated traffic/revenue targets from keyword volumes.
- [ ] **REL-08 · Founder · depends REL-01 through REL-07, EDT-09:** review the concrete release candidate and give final go/no-go. Acceptance: 160 accepted unique articles, no unresolved critical/high launch blocker, browser signoff on reader/admin/author journeys, a rollback plan and named operational responsibility.

**Gate M5:** the candidate is release-ready only when engineering, editorial and founder evidence agree. Passing the old public-page test suite alone is not enough.

## Phase 7 — approved launch and early operation

- [ ] **GO-01 · Engineering · depends REL-08:** deploy the approved build and content release. Acceptance: correct production origin, review-only banner/gates removed intentionally, production indexing enabled only on the real site, private admin/drafts remain protected, migrations/queues/caches healthy, rollback available.
- [ ] **GO-02 · Engineering + Founder · depends GO-01:** verify live journeys and submit discovery through authorized accounts. Acceptance: owner-verified Search Console/Bing access and sitemap submission; newsletter/affiliate/analytics smoke checks; no assumption that submission guarantees indexing.
- [ ] **GO-03 · Engineering + Editorial · depends GO-01:** monitor the first 24–72 hours. Acceptance: review errors, alerts, broken links/images, forms, queue failures, accidental indexing controls and staff access; fix regressions before new features.
- [ ] **GO-04 · Editorial + Founder · depends GO-02:** review 7/14/30-day observations and set the maintenance cadence. Acceptance: actual impressions/clicks, crawl/indexing observations, engagement, affiliate events, verified revenue where available and update priorities; measure outcomes without treating early traffic or search positions as guaranteed.

## First implementation batch

Start **FND-04, IAM-01, IAM-02, IAM-03 and CMS-01**: decide compatible back-office tooling, implement invite-only authentication and policies, create the real super-admin/author roles, and design the article/revision data model. Then deliver a browser-reviewable author → super-admin → public-article demonstration before commissioning the full corpus.

## Capacity and decisions

Do not set a launch date from this checklist alone. After the 10-article pilot, estimate content duration from `(160 − accepted articles) ÷ demonstrated weekly accepted-article throughput`, accounting for research, sourcing, media, review and rework; engineering and operational gates may take longer. Founder choices needed at their relevant gates: hosting/database/providers and budget, accountable authors/reviewers, affiliate/ad programs, editorial/legal identity, production domain, and research spend. Missing choices should not block independent backend work now.

## First backend milestone delivered — 2026-10-27

See STAFF-WORKSPACE.md and ADR-001-STAFF-WORKSPACE.md for the actual implementation and limits. Native Laravel/Blade staff UI, invite-only onboarding, role/ownership policies, team changes/session revocation, database article/revision foundation and the reviewed publishing/withdrawal cycle are implemented. Validation: 42 PHP tests / 1,607 assertions and nine browser tests passed. Only the compatible-back-office decision, current role policies and controlled account-management tasks above are marked complete.

IAM-01 remains partial until real email/inbox flows are verified; IAM-04 remains open for two-factor authentication. CMS-01 is partial: article/revision/category records exist, but dedicated media/source models and intended production-database checks remain. CMS-04 is partial: reviewed manual publication works, scheduling/archive work remains. Broader IAM/CMS acceptance tasks stay open where their full criteria are not met. No inherited or new test article was added to the 160 accepted-article count. The owner's review administrator invitation is prepared but requires the owner to choose the password.

## Growth management foundation — 2026-11-02

Implemented super-admin growth overview, CMS readiness queue, first-party newsletter request counts and versioned/audited integration planning for OpenSEO, Search Console, GA4, display ads, affiliates and email. These planning records do not fulfill live-connector, tracking, revenue-reporting or content-acceptance gates. Next integration work requires provider authentication, consent/event design, ingestion and verification. Expired-login recovery is browser tested without weakening CSRF. Evidence: STAFF-WORKSPACE.md.

## Article operations and search integration delivery — 2026-11-02

Delivered article inventory with repository visibility/search/filters/sort/pagination, explicit private import and version-safe audited assignment. Delivered validated Search Console CSV reporting without an API key and a server-side OpenSEO MCP free connection-check path. The MCP account still requires a server credential/live verification; research/ingestion, production database concurrency, full editor/media features and remaining launch/security gates stay open. Refer to ADMIN-UX-HARDENING.md for actual behavior and limits. No bulk article acceptance or production release was performed.

## Automated editorial management — 2026-11-02

All 13 repository records now exist as private CMS drafts in review; the previous CMS record was preserved. Added automatic repository sync and metadata backfill, running isolated review scheduler, advanced filters/sort/page sizes and accessible pagination, atomic batch submit/withdraw, saved-revision SEO findings, dedicated metadata/FAQ/image-description editing, revision compare/restore, encrypted admin credential storage and a guarded GPT private-draft endpoint. See EDITORIAL-AUTOMATION.md for evidence and limitations.

63 PHP tests / 3,058 assertions and 13 distinct browser checks passed. Real OpenSEO research and GPT generation remain blocked on credentials; no article body was represented as researched/expanded and no draft was accepted toward the 160 goal. MFA, delivery, production database, durable infrastructure, richer media/table editing and other launch criteria remain open.
