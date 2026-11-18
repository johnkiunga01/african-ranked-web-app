# Revenue priorities after launch — 13 November 2026

## Evidence and limits

The live sitemap contained 33 article URLs when checked, including enough published safari destinations, seasons, comparisons, costs and preparation guides to build a useful navigation hub. A sitemap URL count is not a verification that every article is accurate or indexed.

OpenSEO returned a successful Search Console response for `sc-domain:africanranked.com`, grouped by page, 4–10 November 2026: **zero rows**. This narrow early window does not establish that the website has no visitors. OpenSEO measurement health returned `ga4_not_connected`; this means reporting is not connected to the OpenSEO project, not that the website tag is absent. No verified traffic, conversion or revenue baseline was available for this review. An earnings forecast would be speculation.

The saved 2 November OpenSEO baseline (US/English/desktop) suggests relevant intent around safari timing, destinations, packing and costs. These are historical national keyword estimates, not global demand or projected visits. Reuse that evidence rather than buying the same discovery again. See [research baseline](SEO-RESEARCH-2026-11-02.md).

## Implemented first step: Plan your safari

`/plan-safari` turns existing published guides into four decisions: choose a travel style; compare seasons; budget for the whole trip; prepare for the experience. The hub only links to matching published catalog records. Missing, private and withdrawn stories are omitted. It does not repeat whole article bodies, take bookings, imply live availability or advertise nonexistent partners. It has a canonical URL, sitemap entry, footer link and an entry point on Our Approach.

The business hypothesis is that a guided route to relevant articles will increase useful reading and create better places for future contextual commercial links. This has not yet been measured. Do not call it an ROI increase until costs and attributable revenue are recorded.

## Recommended sequence

| Priority | Next item | Why it fits | Dependency | Measure |
|---|---|---|---|---|
| 1 | Reliable measurement and a baseline | Spending on monetization before distinguishing readers, clicks and sales makes results hard to assess | Confirm GA4 reports receive events; connect reporting to OpenSEO; define consented events | Sessions, landing pages, planning-step clicks, article engagement; record reporting coverage limitations |
| 2 | Contextual affiliate placements in useful guides | Safari timing, packing and cost content already answers decisions adjacent to purchases | Owner selects actual programs; acceptance and current terms; tested links and clear disclosure | Qualified outbound clicks, confirmed bookings/sales, earned commission, content and operating costs |
| 3 | A concise Work with Us page | Gives real businesses a route to discuss relevant sponsorship without needing a marketplace | Working monitored inbox; approved offer; accurate audience data when available | Qualified enquiries, proposals, closed work, contribution after production costs |
| 4 | An optional packing checklist and newsletter follow-up | Can support repeat visits and useful product recommendations | Newsletter delivery issue resolved; approved privacy/consent; verified DOI and suppression | Confirmed opt-ins, engaged return visits, unsubscribes; attributable partner results |
| Later | A verified operator directory | Could support qualified leads, but also creates ongoing verification, dispute and moderation work | Real operator onboarding, permission, verification, listing terms, privacy, lead delivery and reporting | Valid operator profiles, qualified leads, response rate, partner retention and net contribution |

Do not charge for ranking positions, claim a partner is approved when it is not, publish invented testimonials or audience figures, or offer a booking service the application does not actually operate. A Work with Us page should describe available editorial formats and invite a discussion; publish pricing only when the commercial offer and delivery costs are established.

Start with a small number of relevant affiliate placements after the owner confirms actual partners. Display advertising can be evaluated later against real traffic and reading performance; adding ad density now is not evidence of an improved return.

## Measurement specification for the next implementation

Proposed events, not implemented revenue tracking: `planning_step_click`, `partner_outbound_click`, `commercial_enquiry` and `newsletter_confirmed`. Count an outbound click separately from a merchant-reported booking or sale. Avoid duplicate client/server events. Keep event parameters to stable article slug, partner ID and placement ID; do not send email addresses, enquiry messages or URL query strings to GA4. Respect analytics consent, and label any gaps caused by denied consent or blocked requests. A mailto click is not a delivered enquiry.

Affiliate contribution should be computed from actual earned commission minus attributable content, tools and operating costs. Until merchant results are available, report outbound clicks without inventing a conversion rate or treating clicks as revenue.

## Source checks

- [Google Search spam policies](https://developers.google.com/search/docs/essentials/spam-policies#thin-affiliation), retrieved 13 November 2026: affiliate pages need useful added value rather than copied merchant descriptions. The planning hub is editorial navigation, not a duplicate merchant catalog.
- [FTC disclosure guidance](https://www.ftc.gov/business-guidance/resources/disclosures-101-social-media-influencers), retrieved 13 November 2026: relevant material relationships should be disclosed clearly. This source does not establish all legal obligations in every audience jurisdiction.
- Viator and GetYourGuide partner-resource sites returned HTTP 403 in this executor. Program eligibility, rates, cookie windows and payout terms were not verified. No accounts were opened or purchases made.

## Media shipped in the same release

The renderer accepts an explicitly marked YouTube link, `[Video: Title](https://www.youtube.com/watch?v=VIDEO_ID)`, as a click-to-load player. It uses a fixed youtube-nocookie.com embed origin, no autoplay and no third-party thumbnail. Before a click the page makes no YouTube media request. No-JavaScript readers can use the direct external link. A reader loading an embed initiates a connection to YouTube; the privacy-enhanced host is not a promise of zero data processing.

Image Markdown can include a quoted caption, `![Descriptive alternative text](https://example.test/image.svg "Caption and attribution")`. Captions are escaped. Existing uploaded-media credits continue to take precedence where applicable. Never infer a licence from the fact that a URL loads.

Four original AI-assisted SVG infographics are included under `/editorial-art/{article-slug}/decision-guide.svg`: safari decision priorities, mobile-money measures, music listening questions, and gorilla-trip budgeting. They are explanatory diagrams, not documentary photographs or invented statistical charts. The gorilla price refers to Visit Rwanda's page checked 13 November 2026 and should be reconfirmed before booking.

Pexels photo and licence retrieval returned HTTP 403. No new Pexels/Unsplash photographs were described as verified. An official UWA page contained YouTube ID `3XZfbd8yugI`, but video metadata retrieval was blocked; it remains a candidate to review, not a verified editorial embed.

The current authenticated editorial integration permits only fresh unpublished drafts. It deliberately rejects published articles. Media additions through it must remain private and revision-safe; these changes do not broaden its publication permissions. Review original image subject, creator, source URL, licence and editorial relevance before placing externally sourced media in a published pillar.
