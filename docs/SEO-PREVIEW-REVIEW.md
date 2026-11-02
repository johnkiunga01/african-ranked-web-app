# Local SEO preview review

Reviewed: 2026-10-27. Applied the project `laravel-seo-audit` and `schema-review` adapters using pinned Corey Haines/AgriciDaniel references, Laravel tests, initial HTML/XML, and Chromium. This is **preview-only**, not launch approval, live ranking research, or a performance score.

## Evidence and verified behavior

- Backend suite: 13 tests passed, 1,394 assertions; publishing tests exercise eight edition homes, every article in each edition, regional categories, canonical policy, visible FAQ/schema parity, XML discovery, 404s, and legacy redirects.
- Browser suite: six tests passed for desktop/mobile, edition selection, section anchors, article canonical, share fallback, Lottie, reduced motion, real CSRF, newsletter browser validation/deduplication, no-JavaScript reading, and discovery headers.
- `npm run preview` adds `X-Robots-Tag: noindex, nofollow, noarchive`; canonical and JSON-LD remain inspectable. Preview noindex is deliberate and overrides permissive page metadata. It is not authentication.
- JSON-LD is present in initial HTML and rendered DOM. The root `@graph` is valid JSON-LD structure; it does not need a duplicate root `@type`. This verifies syntax and local data parity, not external Google rich-result eligibility.
- Identical regional articles use global canonicals and no article hreflang; populated home/category editions use their existing self-canonical/alternate policy. No external search submission was made.

## Findings to address during continued development

| Priority | Finding and evidence | Recommended next work |
|---|---|---|
| High editorial | Top-N titles exceed the number of individually explained ranked sections; see table below. | Verify the complete ranking, criteria, and evidence, then fill genuine missing entries or accurately retitle. Do not invent entries to match a headline. |
| High editorial | Inherited statistics, attributed quotations, named bylines, and ranking methodology are not source-verified. `content/articles.json` carries no verified source record. | Create article-level primary-source checks, accountable authors/reviewers, and transparent ranking criteria. |
| Medium strategy | Global English-speaking readers and ads + affiliate revenue are now confirmed, but keyword demand and competitor data are not yet available. | Use Open SEO once exposed; map query intent to existing and proposed URLs before expanding. |
| Medium international | Many English edition pages share substantial content. Existing canonicals are coherent, but a live engine may still consolidate near-duplicate listing pages. | Establish a useful reason and search demand for each edition before expanding; avoid scaled doorway pages. |
| Medium images | Original Pexels URLs are blocked by the running environment; local fallbacks work. Image rights and actual photo descriptions are not verified. | Recheck delivery when allowed, review rights/alt text, then evaluate responsive sources and real LCP behavior. |
| Medium trust/conversion | Local signup stores requests but there is no delivery/double opt-in/unsubscribe integration, privacy/contact page, or verified publisher identity. | Design those flows during preview, before accepting real subscribers or adding tracking/affiliate integrations. |
| Research limit | Primary Google docs were blocked, and no GSC, SERP, CrUX, keyword, competitor, or AI citation measurements were available. | Revalidate time-sensitive claims with primary sources and obtain actual tool data. No numerical SEO-health, traffic, or revenue score is justified. |

### Ranking completeness evidence

These counts are numbered heading blocks, not a claim that every other named entity is absent. Some bodies mention additional names in a summary paragraph; that is different from a complete ranked explanation.

| Article slug | Headline promise | Individually numbered sections |
|---|---|---:|
| `top-10-safari-destinations-africa-2026` | Top 10 | 5 |
| `best-african-cuisines-ranked-2026` | 15 best | 4 |
| `top-african-startups-2026` | Top 20 | 3 |
| `best-beaches-africa-ranked` | 12 beaches | 3 |
| `top-african-musicians-2026` | 25 musicians | 2 |
| `fastest-growing-tech-hubs-africa` | 10 tech hubs | 3 |

## Business-led next research

The owner selected **display ads plus affiliate revenue** and **global English-speaking readers interested in Africa**. Begin with the existing content and candidate supporting guides; decide priorities from observed demand, intent, competitor evidence, source quality, and commercial fit. Do not promise high rankings or revenue because an article uses headings or schema.

The Open SEO connector was explicitly requested, but no tool or skill with that name or keyword/competitor capabilities was exposed in the available session catalog when checked. The owner confirmed it was added as a plugin and tagged `plugin://openseo@openai-curated-remote`. The tool and skill catalogs were rechecked after tagging: no OpenSEO functions were exposed. A plugin tag alone does not make a separate GPT’s tools callable in this Codex session. Keyword volumes, difficulty, CPCs, SERP competitors, and research-led article selections remain pending, not estimated.
