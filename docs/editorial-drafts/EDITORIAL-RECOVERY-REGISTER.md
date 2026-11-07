# Draft article recovery — editorial source of truth

Prepared 7 November 2026. **Review workspace only: no CMS publication or change to existing published revisions.**

## Authoritative guide and scope

- The source workbook `africa-ranked-seo-content-plan.xlsx` in connected Google Drive is an Office workbook, not a native Google Sheet. Its Editorial Guide, Article Ideas and Structure Templates tabs were read without modification.
- The workbook requires a **40–60 word answer block**, a key-facts module with verification dates, **5+ cited sources**, **3+ supported FAQs**, named author/reviewer, last-verified date, relevant template outline, topic-specific primary keyword, and **at least 1,300 substantive body words excluding answer/facts/ranked-item text/FAQs/sources**.
- Rankings require distinct complete entries and an auditable methodology; high-risk fees, health, visas, safety and current financial data require human confirmation against opened primary sources.
- Use stable ID and slug, preserve previous CMS revisions, keep outbound citations and review image rights; link only to actual published sibling articles.
- Approved editorial statuses: Not started → Researching → Drafted by agent → In review → Approved → Published → Needs update. Drafted status does **not** mean launch-ready.
- The workbook prioritizes wave 1 (19 posts), wave 2 (47), wave 3 (34). The authenticated staff dashboard on 7 November 2026 confirms **53 total CMS records, 26 published, 27 drafts and 0 awaiting review**. It does not expose the complete 27-row draft inventory in the dashboard view. Record the exact current revisions from the article library or a read-only PostgreSQL query before any editorial write.
- The separate 36-populated-draft team workbook cited by AGENTS.md was not located in connected Drive search. Its draft content must be recovered and preserved before any bulk replacement.

## Wave-1 master article register — publication state requires CMS reconciliation

| ID | Target slug | Template | Risk | Editorial priority |
| --- | --- | --- | --- | --- |
| AR-001 | `top-10-safari-destinations-africa-2026` | T1 Ranking | Medium | Safari cluster |
| AR-002 | `best-safari-destinations-africa-first-timers` | T1 Ranking | Medium | Safari cluster |
| AR-003 | `best-budget-safari-destinations-africa` | T1 Ranking | High | Safari cluster |
| AR-004 | `best-family-safari-destinations-africa` | T1 Ranking | High | Safari cluster |
| AR-005 | `best-self-drive-safari-destinations-africa` | T1 Ranking | Medium | Safari cluster |
| AR-006 | `best-places-to-see-big-cats-africa` | T1 Ranking | Medium | Safari cluster |
| AR-007 | `africa-safari-cost-by-destination` | T2 Guide | High | Safari cluster |
| AR-008 | `serengeti-vs-masai-mara` | T3 Comparison | Medium | Safari cluster |
| AR-009 | `best-time-to-visit-masai-mara` | T2 Guide | Medium | Safari cluster |
| AR-010 | `best-time-to-visit-serengeti` | T2 Guide | Medium | Safari cluster |
| AR-011 | `best-time-to-visit-kruger` | T2 Guide | Medium | Safari cluster |
| AR-012 | `gorilla-trekking-uganda-rwanda-drc` | T3 Comparison | High | Safari cluster |
| AR-013 | `safari-packing-etiquette-first-timer-guide` | T2 Guide | Low | Safari cluster |
| AR-014 | `how-to-choose-an-african-safari` | T6 Pillar guide | Medium | Safari cluster |
| AR-015 | `largest-economies-in-africa` | T4 Data ranking | Medium | Non-safari anchor |
| AR-016 | `top-african-startup-ecosystems` | T4 Data ranking | High | Non-safari anchor |
| AR-017 | `african-countries-by-mobile-money-adoption` | T4 Data ranking | Medium | Non-safari anchor |
| AR-018 | `best-african-cuisines-for-first-time-food-travellers` | T1 Ranking | Medium | Non-safari anchor |
| AR-019 | `african-music-genres-explained` | T5 Explainer | Medium | Non-safari anchor |

All entries above are **master-plan targets, not an assertion of CMS draft status**. The prior publication handover suggests some have already been released in the noindex environment. Never recreate those records as competing articles.

## Prepared manuscripts in this recovery branch

| Slug | Research / manuscript | Body status | Publication status |
| --- | --- | --- | --- |
| `best-time-to-visit-etosha` | Long-form Namibia authority / NWR source-based article | Prepared; verify actual CMS body-word count | **Not published** |
| `best-time-to-visit-namibia` | Long-form official tourist-board / park-authority source-based article | Prepared; verify actual CMS body-word count | **Not published** |

These manuscripts are private editorial material. They must not be imported as if independently verified. No `reviewed_by`, `last_verified_at`, source-access or image-rights attestation has been written.

## Required next CMS intake

1. Export a **read-only** inventory of CMS article ID, slug, current revision ID, published revision ID, current status, word count, source count, FAQ count and editorial-evidence failures. No credentials or private article content in the output.
2. Match those records to the source workbook's stable slug and ID. Flag duplicate slugs, out-of-workbook drafts and articles already published in staging.
3. Recover the 36 prior team manuscript bodies before deciding which article needs rewriting.
4. Prioritize the unpublished wave-1 gaps first, then remaining CMS drafts; avoid editing already-published versions without a review need.
5. Save each completed manuscript as a **private revision**, with optimistic revision guards and original revision preserved.
6. Request independent source, facts, byline and media review; apply `PublicationQuality` after ensuring five reader-visible cited sources and the template-specific modules.
7. Publish only approved revisions; verify page, metadata, canonical/noindex, citations, internal links, image rights and FAQs afterward.
8. Update the authoritative workbook statuses from actual CMS publication results, not inferred repository documentation.

## Release acceptance

The launch is **blocked** until every article intended to appear in the public index is reviewed and published, and unapproved drafts are excluded from public navigation/sitemap/search. Public launch indexing should remain disabled until final acceptance. **A private CMS draft can remain in the backend without being displayed to readers**; deleting all drafts is not a prerequisite for a clean launch.

## Confirmed 7 November CMS drafts visible in staff dashboard

The dashboard explicitly identifies these as drafts (read-only authenticated browser context):

| CMS ID | Title | Current status |
| --- | --- | --- |
| 49 | Afrobeat vs Afrobeats: What Is the Difference? | Draft; manuscript prepared in this branch |
| 50 | Nollywood Explained and Africa's Biggest Film Industries | Draft; not revised |
| 51 | Traditional African Instruments: A Guide | Draft; not revised |
| 52 | African Fabrics and Prints: Kente, Ankara, Kitenge and More | Draft; not revised |
| 53 | African Cultural Festivals by Month | Draft; not revised |
| 37 | Victoria Falls: Zambia or Zimbabwe Side? | Draft; not revised |

The other 21 drafts must be enumerated from the authenticated article library or database, not guessed from the 100-topic workbook. The confirmed CMS draft #49 is now the first manuscript prepared for this pass; the other two manuscripts are **master-plan subjects with their live CMS status not yet individually reconciled**.

## Review checklist by prepared manuscript

| Subject | Official/relevant source research | Body prepared | 40–60 word verified answer module | Dated key-fact verification | Photo rights | CMS update |
| --- | --- | --- | --- | --- | --- | --- |
| Traditional African Instruments (#51) | UNESCO, Smithsonian and Met sources | Yes (2,700+ words before FAQs) | Pending reviewer | Pending reviewer | Pending | None |
| African Fabrics and Prints (#52) | Museum-sourced textile history and terminology | Yes (2,600+ words before FAQs) | Pending reviewer | Pending reviewer | Pending | None |
| Afrobeat vs Afrobeats (#49) | Yes, institutional and catalogue sources | Yes | Pending reviewer | Pending reviewer | Pending | None |
| Best Time to Visit Namibia | Yes, Visit Namibia and MEFT | Yes | Pending reviewer | Pending reviewer | Pending | None |
| Best Time to Visit Etosha | Yes, MEFT and NWR | Yes | Pending reviewer | Pending reviewer | Pending | None |

The manuscript source lists are not a substitute for human verification of individual claims. Do not infer a reviewer or verification date from file commit history.

## 7 November second-pass work

- Confirmed draft CMS #52 has a 2,600+-word substantive manuscript prepared in `african-fabrics-and-prints.md`, citing retrieved institutional sources from the Victoria and Albert Museum, The Met, British Museum, Smithsonian and UN Africa Renewal. This is a separate research manuscript, not a CMS edit.
- The sourced comparison distinguishes kente weaving from Ankara and kitenge printing, includes kanga as an important distinct format, and avoids unsupported claims of singular cultural origins.
- No photo was imported, and no new source-access date, legal image right, author or reviewer has been recorded in CMS.

- Confirmed CMS draft #51 now also has a 2,700+-word substantive manuscript (`traditional-african-instruments.md`), documenting mbira/sansi, kora, balafon and Yoruba dundun traditions from UNESCO, Smithsonian and Met sources. Archive media reuse is specifically prohibited until rights are cleared.
