# African Ranked product marketing context

Document version: 1.3 (first authenticated editorial workflow implemented)
Updated: 2026-10-27

## Product and current stage

African Ranked is an Africa-focused article/rankings platform migrated from a Bolt-generated React site to Laravel 12 and Blade. The owner likes the original design direction. A shareable development review has been established. The owner now wants a gated development-to-launch roadmap: complete and test the client-facing platform and super-admin/author backend before scaling to more than 150 full SEO-ready articles. The planning target is 160 unique accepted articles; this is not authorization to launch now.

## Audience and subject areas

The existing site targets readers interested in African travel/destinations, food, music/culture, business/startups, technology, and wildlife/safaris. It offers Global, US, UK, Australia, Canada, Nigeria, South Africa, and Kenya editions. The first researched batch should prioritize global English-speaking readers, as confirmed by the owner. Country-specific editions remain available, but equal investment in every edition is not the initial priority. Category definitions are inferred from the repository, not validated audience research. The 13 inherited preview articles require editorial verification.

## Goal and business model

The owner confirmed display advertising plus affiliate revenue, targeting global English-speaking readers interested in Africa. Organic discovery, useful answers, and a preserved reading experience support that model. Specific ad/affiliate networks, commercial partnerships, commission terms, revenue targets, budget, keyword demand, and competitor benchmarks are not yet established. Do not invent them.

## Positioning and voice

Current proposition: “The best of Africa, ranked.” Preserve the amber/orange/stone design, readable editorial hierarchy, rounded cards, and Plus Jakarta Sans typography. Use clear, helpful explanations and explicit factual limitations; avoid fabricated authority and inflated subscriber numbers.

## Available evidence and conversions

Thirteen preview articles, six categories, eight editions; server-rendered article bodies/metadata; local database newsletter request storage. A first database-backed staff/CMS workflow now supports invitations, roles, drafts/revisions, review and manual publishing. Newsletter delivery, double opt-in/unsubscribe, two-factor authentication, richer media/editorial operations, analytics, Search Console, live rankings, traffic history, conversion rates and citation monitoring are not configured or fully evidenced. Local test signups are not delivered email subscriptions.

## Boundaries for this stage

A temporary staging review is authorized. Production launch, search-engine submissions, new paid services and outbound campaigns still need the appropriate later authorization. Preserve existing article slugs and the current canonical policy. Preview responses are intentionally noindex. Claims, quotations, named authors, image rights, ranking completeness, and methodology need verification before publication.

## Changelog

- 1.3 — 2026-10-27: Implemented the first invitation-only author/super-admin workspace and reviewed publication workflow. Owner provisioning is private and awaiting password setup. Forty-two PHP tests and nine browser tests passed; production/advanced editorial tasks remain open.

- 1.2 — 2026-10-27: Owner requested a launch task list prioritizing client-facing completeness, tested super-admin/author workflows, and 150+ complete SEO-ready articles. Roadmap targets 160 accepted articles with staged review gates.

- 1.1 — 2026-10-27: Owner confirmed display ads plus affiliate revenue and global English-speaking readers; requested Open SEO for keyword/competitor research. Open SEO is not yet exposed in the current conversation tools.

- 1.0 — 2026-10-27: Drafted from the repository and the user's preview/testing instructions; unknown commercial and research details remain explicitly unknown.

## OpenSEO connection status

The owner tagged `plugin://openseo@openai-curated-remote`. No callable OpenSEO tools or OpenSEO skill appeared in this Codex session after rechecking. Do not ask for API keys or invent metrics. Resume read-only keyword/competitor research when the host exposes that connection to Codex.

- 2026-11-02: Synced upstream main `59ffdad`: seven new wildlife articles and six revised examples; 13 preview records, zero accepted launch articles. No CMS publication states changed.
