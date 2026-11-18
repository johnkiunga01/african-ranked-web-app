# African Ranked — CMS hardening and frontend launch audit

**Audit date:** 7 November 2026. **Scope:** current GitHub main code, existing PHP/Playwright QA, authenticated staff dashboard text, public homepage text. **Change branch:** `fix/cms-discovery-hardening-20261107`. No content publishing or crawler-setting changes on deployed Railway origin in this review.

## Evidence and limits

- The authenticated staff dashboard returned **53 current CMS records, 26 published, 27 drafts, 0 awaiting review** when inspected. Draft count is a content-readiness gate, not necessarily a technical CMS defect.
- The public homepage was inspected via an existing browser tab's readable context. It exposes named category navigation, a functional-looking search form, featured and recent articles, credited Pexels imagery, RSS and sitemap links. Link targets and captions were read, but individual link clicks, source-media rights and responsive visual screenshots were **not** comprehensively tested from that browser mode.
- Repository tests previously covered canonical article URLs, regional templates, JSON-LD, FAQ parity, noindex preview, sitemap, 404s and staff article access. Passing those tests is **not** the same as independently validating live mobile UX or Core Web Vitals.
- Public preview remains noindex; do not enable indexing as a side effect of SEO changes. Current staff layout explicitly includes `<meta name="robots" content="noindex, nofollow, noarchive">`.

## Verified issues and fixes in this branch

| Workstream | Finding | Fix | Validation |
| --- | --- | --- | --- |
| CMS productivity | The topbar labelled search as covering article content, but `ArticleController::index` only searched current revision title and slug. The private draft body and introduction were excluded. | Search now also matches `currentRevision.excerpt` and `currentRevision.body_markdown`; author ownership scope is unchanged. Staff search labels now match behavior. | New feature test checks body and intro search, plus that unrelated author content remains invisible. |
| Technical SEO | Published-mode `robots.txt` previously allowed `/staff` by omission. Staff HTML already had noindex; explicit exclusion is still useful defense in depth. | Add `Disallow: /staff` to the **public-mode** robots file. Existing preview disallow-all behavior remains untouched. | New test checks production-mode exclusion and preview disallow-all. |

## Open UX/technical checks before launch

1. **Visual and responsive QA:** Capture actual screenshots at common mobile, tablet and desktop widths for homepage, rankings/search, category, single article, staff dashboard, editor, bulk actions, content readiness and author-account views. Confirm menu focus, table horizontal scrolling, touch targets, long titles, image aspect ratios and overlay positioning. Code-level responsive classes exist; screenshots are still needed.
2. **Editorial trust:** The homepage currently shows a generic byline `AR Team` for multiple featured/recent items. Confirm that each article has real, approved author and reviewer identities consistent with its source evidence before the public launch. Do not generate fictitious staff identities.
3. **Content language:** The homepage excerpts include broad words such as “definitive,” “optimal” and “ultimate” for some safari guides. Review against verified supporting claims and edit source articles through the regular editorial workflow rather than substituting unsupported promotional claims.
4. **Photograph provenance:** The homepage exposes Pexels credit links, which is helpful. Validate the original image asset, photo ID, photographer, rights and precise location caption for each relevant image. Do not assume a file name or HTML credit proves the subject.
5. **Editorial SEO:** Verify the 26 published staging revisions against the original workbook's 1,300-word, evidence, FAQ, dated-source, reviewer and media-rights requirements. Repository's `PublicationQuality` structural validation does not confirm that linked sources were actually opened.
6. **Sitemap and index gates:** Re-run crawler/DOM checks on the final deployed host after any merge; ensure only published canonical articles and pages are listed, stale/withdrawn content is excluded and staged noindex is retained. Domain migration must trigger a new canonical/sitemap/SSL review.
7. **Performance:** Measure live mobile LCP, INP and CLS using suitable lab and field data once available; no performance score is claimed from static inspection.
8. **Error and recovery paths:** Exercise invalid uploads, oversized images, stale draft revision submissions, failed external providers, expired sessions, zero-selected bulk actions, user role restrictions and rollback behavior in controlled staging test accounts. Do not modify live user data merely for a smoke test.

## Release gates

Before merge: Laravel Pint, PHP SQLite, PHP PostgreSQL, Chromium browser suite and launch readiness safeguards **all must be green on the same commit**. Then verify Railway web/worker/scheduler deployment and HTTP activity. No database schema changes or editorial revision writes are included in this branch.

**Publication and indexing are not part of this PR**. Article improvement remains in dedicated editorial PR #9 and the scheduled three-draft workflow; those private manuscripts must not be confused with published and approved content.
