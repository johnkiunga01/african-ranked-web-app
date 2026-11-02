# SEO skills: research, selection, and use

Reviewed on 2026-10-27 for an **unreleased Laravel article platform**. This is a fit-based selection, not a claim that one repository is universally the best SEO source.

## Sources inspected

| Source | Pinned revision | Fit for this project | Limits |
|---|---|---|---|
| [Corey Haines / marketingskills](https://github.com/coreyhaines31/marketingskills/tree/17772c7a80b7b8c7a47af30f648374cd8bd8adc5) | `17772c7a80b7b8c7a47af30f648374cd8bd8adc5` | Broad audit workflow, editorial priorities, site structure, product context, AI-search strategy | Broad marketing scope includes campaigns and commercial integrations that are not needed for this preview. Some rules are heuristics or require interpretation for regional duplicates and server-rendered schema. |
| [AgriciDaniel / claude-seo](https://github.com/AgriciDaniel/claude-seo/tree/6be93f45064ffbadced572b5d5c95f55fd263af0) | `6be93f45064ffbadced572b5d5c95f55fd263af0` | Detailed technical, schema, international, image, sitemap, and content checklists with supporting references | Full plugin depends on Claude-specific orchestration and a Python runtime. Its time-sensitive 2026 claims are not automatically independently verified facts. |

Both inspected repositories are MIT-licensed. Their license notices are retained in `.agents/vendor/<source>/LICENSE`. Selected upstream files are copied unchanged, excluding evaluation fixtures. Exact source revisions and SHA-256 hashes for every vendored file are in [.agents/skill-sources.json](../.agents/skill-sources.json).

## What is installed

Five Corey Haines skill snapshots: `product-marketing`, `seo-audit`, `ai-seo`, `content-strategy`, and `site-architecture`.

Seven AgriciDaniel snapshots: `seo-technical`, `seo-content`, `seo-schema`, `seo-sitemap`, `seo-hreflang`, `seo-images`, and `seo-geo`, plus their supporting references and shared `seo/references` material.

Nine discoverable project-local Codex adapters map those sources to this Laravel repository:

| Request | Skill |
|---|---|
| Audit the preview's SEO | `laravel-seo-audit` |
| Plan article topics and clusters | `content-strategy` |
| Review taxonomy, links, and URL migration | `site-architecture` |
| Improve AEO/GEO discovery | `ai-seo` |
| Inspect Article/FAQ/Breadcrumb JSON-LD | `schema-review` |
| Review editions, canonicals, and hreflang | `international-seo` |
| Review XML sitemap discovery | `sitemap-review` |
| Check facts, full rankings, and author evidence | `article-quality` |
| Check image delivery, alt text, and rights | `image-seo` |

The existing Laravel-specific skills remain in place. [AGENTS.md](../AGENTS.md) routes future work to the relevant adapter and [SEO-POLICY.md](../.agents/SEO-POLICY.md) resolves compatibility issues. Reviewer briefs in `agents/` now point to those workflows. No background agents are running.

## Adaptations made deliberately

- **Codex compatibility:** no Claude plugin install, slash-command registration, Claude model selection, or `${CLAUDE_PLUGIN_ROOT}` runtime is claimed. Upstream helper scripts, third-party tools, MCP integrations, paid APIs, hooks, and global packages were not installed or executed. Equivalent local Laravel/Playwright checks are used where appropriate.
- **Schema detection:** raw HTTP fetches can detect this app's server-rendered JSON-LD. Inspect the rendered DOM too. Do not report missing schema just because a text-only fetch strips script tags.
- **JSON-LD graph support:** `@context` with `@graph` is valid without a root `@type`; inspect graph entities rather than incorrectly flagging this app's document.
- **Regional content:** the existing same-language article bodies canonicalize globally. Do not import an unconditional “every locale self-canonicalizes” rule from a generic checklist. Real translations and genuinely distinct regional pages require a different analysis.
- **Preview-only indexing:** preview noindex headers are intentional, while canonical and schema markup stay available for inspection. No search-engine submissions, deployments, ad campaigns, or external messages are part of using these skills.
- **Evidence standards:** fixed word/character counts are heuristics, not ranking requirements. llms.txt is experimental. Rich-result eligibility and schema validity are separate. Time-sensitive feature retirements, security claims, and vendor benchmarks require verification rather than repetition.

## Research limits

The repository sources and included references were inspected directly over authenticated HTTPS Git. A request to Google's primary documentation at `developers.google.com/search/docs/fundamentals/creating-helpful-content` was blocked by this environment's egress proxy (HTTP CONNECT 403). Consequently, the upstream claims about 2026 feature retirements, security advisories, and algorithm behavior are **not independently verified here**. No live SERP research, Search Console analysis, PageSpeed/CrUX measurement, competitor ranking comparison, paid keyword data, or AI citation tracking was performed.

Primary references to revisit when accessible:

- [Google: creating helpful content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content)
- [Google: localized page versions](https://developers.google.com/search/docs/specialty/international/localized-versions)
- [Google: consolidating duplicate URLs](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)
- [Google: structured data policies](https://developers.google.com/search/docs/appearance/structured-data/sd-policies)
- [Google: robots meta tags and HTTP headers](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag)

## How the skills were used now

The installed `laravel-seo-audit` and `schema-review` adapters were applied to local initial HTML, server headers, canonical/schema behavior, discovery endpoints, article completeness, and browser evidence. Results and open work are in [SEO-PREVIEW-REVIEW.md](SEO-PREVIEW-REVIEW.md). This is a local pre-release review, not a live search-performance audit or launch approval.

## Updating later

Fetch the intended source revision into an external research directory, compare changes, review new instructions/references and licenses, then replace only the selected snapshots and regenerate their hashes. Do not silently update from a moving default branch or run an upstream installer to refresh these project-local references. Preserve local adapter policies and the owner's current testing scope.
