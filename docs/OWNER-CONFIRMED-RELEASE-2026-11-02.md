# Owner-confirmed CMS release — 2 November 2026

The owner confirmed that editorial verification occurred on **1 November 2026** and authorised adding Pexels featured and inline photographs to the existing 53 CMS drafts and publishing eligible revisions.

The one-time `editorial:release-owner-confirmed` Artisan command preserves the old revisions and calls the existing publisher. It does **not** change source access dates or bypass publication-quality checks. The deployment pre-command is temporary and must be restored after this run.

Review/staging remains noindex; this action does not authorise public search-engine indexing.

## Execution results

- **First CMS run (Railway deployment `4ccf9ca5`):** 53 drafts found; 53 revisions enriched with Pexels featured and body images; 1 published (article #22, Best Time to Visit Kruger National Park); 52 blocked; 0 processing errors.
- **Second CMS run (Railway deployment `0ca3ffcd`, commit `93eb825a0`):** 52 remaining drafts found; 13 revised articles enriched and published (CMS #1–13); 39 blocked; 0 processing errors.
- **Total confirmed published:** 14 of 53. **Still unpublished:** 39. These 39 fail mandatory publication quality, generally missing 1,300 words of substantive editorial body text and dated structured sources, key facts or FAQs.
- The owner-confirmed verification date of **2026-11-01** was applied as the last-verified date where missing, without changing the actual publication timestamp or recorded source-access dates.
- Full execution receipts are in the corresponding Railway deployment logs as `AR_EDITORIAL_RELEASE`; published pointers and previous revisions remain in the PostgreSQL CMS.
- The temporary Railway pre-deploy publication hook was **restored to its original commands** immediately after the releases, so subsequent deployments no longer attempt automatic article publication. Review indexing remains disabled.

The 39 blocked items need actual new article reporting, sources and FAQs; do not force them live by bypassing `PublicationQuality`. Do not infer that repository seed updates automatically mutate existing CMS revision bodies.
