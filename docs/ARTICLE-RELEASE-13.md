# Requested SEO enhancement and publication of the first 13 articles

The owner explicitly authorised enhancing and publishing all 13 articles. No additional publication approval is needed for this requested batch, but factual/attribution/image checks must be real. Keep Railway staging noindex; this is not authorisation to launch indexing.

## Evidence collected

The public Railway sitemap returned zero article URLs during this check. That does not establish CMS draft state; private Railway records were not accessible. The table below describes repository seed content, not a fresh export of the current CMS. Preserve any newer owner edits in PostgreSQL.

| Existing slug | Approximate body words | Numbered headings | Required work |
|---|---:|---:|---|
| top-10-safari-destinations-africa-2026 | 366 | 5 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| best-african-cuisines-ranked-2026 | 223 | 4 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| top-african-startups-2026 | 175 | 3 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| best-beaches-africa-ranked | 190 | 3 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| top-african-musicians-2026 | 143 | 2 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| fastest-growing-tech-hubs-africa | 176 | 3 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| big-five-parks-ranked-where-to-see-all-five | 630 | 5 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| best-gorilla-trekking-africa-ranked | 497 | 4 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| great-migration-guide-best-months-viewing-spots | 424 | 0 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| affordable-safari-parks-ranked | 494 | 5 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| best-time-to-visit-african-safari-by-month | 286 | 0 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| where-to-see-rhinos-in-africa | 256 | 0 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |
| safari-packing-etiquette-first-timer-guide | 229 | 0 | Verify factual claims, replace unverified byline, document image permission, expand useful coverage and cite checked sources |

The safari article promises ten entries but contains five numbered headings; the cuisine article promises fifteen but contains four. Do not manufacture entries just to complete these promises. Research and substantiate the complete comparison or change the headline to match the supported coverage. Word counts diagnose thin inherited material; they are not universal SEO ranking targets.

## Execution in the desktop Codex project

1. Connect to the existing Railway web service and read its current article revisions, authors and publication pointers before editing. Do not replace these records from repository seeds blindly.
2. Reuse actual OpenSEO research in docs/SEO-RESEARCH-2026-11-02.md and content/research/openseo-2026-11-02.csv. Refresh specific missing/ambiguous queries where justified; do not call national metrics global demand. Inspect actual competitor pages before claiming content gaps.
3. Check primary factual sources and image permissions. Remove unsupported statistics, quotations, bylines and claims of firsthand experience. Rewrite dated or unsupported superlatives and rankings honestly.
4. Expand the bodies around reader intent, supported comparison criteria, practical limitations, direct answers, useful FAQs and relevant internal links. Use Gemini with the research and checked evidence; the existing metadata-only action does not expand full articles.
5. Save new draft revisions through the existing workflow, preserving old revisions and guarding against concurrent edits. Validate visible content/schema/canonicals and links, then publish those verified revisions using the owner's authorisation.
6. Verify all 13 canonical article URLs return 200 and appear in the sitemap/feed, with staging noindex retained. Report per-article changes and any genuine unresolved blockers.

## Editorial rewrite progress — 2 November 2026

**Repository content revised; CMS publication not yet performed.** The 13 seed articles in `content/articles.json` have been expanded and SEO fields updated on `main`. Editorial team attribution replaced the inherited unsubstantiated individual bylines. Pexels featured image URLs remain for existing records; the safari packing guide also contains a credited in-body photograph and a sourced Pexels hero image.

Structural readback of the repository content confirmed:
- 13 articles; all have at least 1,300 words of non-ranking narrative content, excluding named ranking entries and sources.
- At least five distinct HTTPS source links and at least three completed FAQ entries per article.
- Ranked entries have at least 60 words of description. The titles promising ten safari destinations and fifteen cuisines now have all numbered entries.
- Every article includes image alt text; revised SEO titles, descriptions and focus terms are in the repository. A regression test was added in `tests/Feature/Staff/RepositoryArticleReadinessTest.php`.
- Existing article slugs are preserved; the changes are not new URLs.

**Do not confuse deployment with publication.** Railway successfully deployed the rewritten repository content and subsequently the regression-test commit, but `RepositoryArticleSync` adds new slugs only. It intentionally preserves edits and publication pointers in PostgreSQL, so existing CMS drafts did not automatically receive these new bodies and zero live publications have been confirmed. There is no verified read/write pathway into the authenticated CMS from this chat. A separate workbook reportedly contains 36 further team drafts; that file is not part of the verified repository inventory.

**Required CMS handoff:** Obtain authenticated, current article inventory and revision IDs; reconcile each existing CMS revision against its enhanced repository version; save new revisions with concurrency checks, official-source evidence, exact image author/rights data and verified active authors; run the existing approval/publication flow; verify public article URLs, feed and sitemap. Retain review/staging noindex until separate launch authorisation. Do not overwrite newer owner edits or claim Pexels photo provenance for uninspected stock files. The regression test has been committed, but a local `php artisan test` execution was not available in this chat.

## Access blocker

Railway CLI HTTPS authentication works in the cloud workspace, but Railway SSH/SFTP is unreachable from its network. The owner has completed local Railway admin setup. Continue this batch from the desktop Codex project with working Railway access; do not request the owner's password or bypass authentication. A repository push alone does not update existing CMS revisions because content sync deliberately preserves owner edits.
