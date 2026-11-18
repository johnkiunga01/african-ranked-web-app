# SEO revisions for published articles — 18 November 2026

**Editorial status:** Proposed revisions for staff to enter in the CMS as private draft
revisions, then review and publish through the normal approval gate. Nothing here has been
applied to the live site.
**Source:** `docs/KEYWORD-RESEARCH-2026-11-18.md` (Semrush US, data date 17 Nov 2026; Search
Console 11–15 Nov 2026). Keyword figures are volume / keyword difficulty (KD) as read there.
**Current state:** read from the live pages on 18 Nov 2026.
**Scope:** search titles, meta descriptions, H1s, standfirsts, internal links and structural
fixes. No new factual claims are introduced. Body changes marked *Body* restate what the page
already says; anything needing new facts is listed under "Needs verified facts" and must be
checked against primary sources first.

Limits: titles ≤ 60 characters, descriptions ≤ 155 characters (counts given).

---

## A. Fix first: defects found on live pages

| Page | Defect | Fix |
|---|---|---|
| `how-to-choose-an-african-safari` | One H2 ("What to compare before booking" onward) contains three whole sections run together, with literal `##` markers inside the heading. Markdown headings were saved without line breaks. | In the CMS body, put each `## Heading` on its own line followed by a blank line. Resulting H2s: "Evaluating evidence limitations and wildlife unpredictability", "Crafting concrete trip scenarios for diverse travelers", "Navigating ethical travel and conservation pressures". |
| `fastest-growing-tech-hubs-africa` | Meta description is a broken sentence: "…Public data about technology ecosystems can describe." | Replace (see section D). |
| `best-time-to-visit-kruger` | Two adjacent H2s: "The short answer" and "The Short Answer in Context". | Rename the second to "Dry winter vs green summer in Kruger". |
| 9 guides | The in-body related links are the same three pages on almost every guide (`best-time-to-visit-okavango-delta`, `gorilla-trekking-permit-cost-how-to-book`, `kruger-vs-masai-mara`), regardless of topic. | Replace with the topic-specific links listed per page below. |

## B. Safari overlap: give each page one job

Research section 9: three pages compete for `best safari in africa` (1.9K / 36),
`best african safari` (1.0K / 25) and `best place for safari in africa` (390 / 10).

### B1. Pillar — `/articles/top-10-safari-destinations-africa-2026`

Owns: best safari in Africa, best African safari, safari tours (60.5K / 36), africa safari
(6.6K / 45).

| Field | Current | Proposed |
|---|---|---|
| Title | 10 Best Safari Destinations in Africa (2026 Guide) | Best Safari in Africa: 10 Top Destinations Ranked (2026) **(56)** |
| Description | Ten African safari destinations compared by habitat… | The best safari destinations in Africa for 2026, ranked: Serengeti, Okavango, Masai Mara, Kruger and six more, with seasons, costs and park rules. **(146)** |
| H1 | Top 10 Safari Destinations in Africa for 2026 | The Best Safari Destinations in Africa, Ranked for 2026 |

Links: already strong (15 internal links). Add a link to `best-safari-destinations-africa-first-timers`
with the anchor "first safari" and to `how-to-choose-an-african-safari` with "plan a safari"
so anchors match the other pages' new jobs.

### B2. First safari — `/articles/best-safari-destinations-africa-first-timers`

Owns: first safari, first time safari, safari for beginners. Stops targeting "best safari in
Africa".

| Field | Current | Proposed |
|---|---|---|
| Title | Best African Safari for First-Timers: Planning & Destinations Guide (67, too long) | Your First African Safari: Where to Go and What to Expect **(57)** |
| Description | Discover the best African safari destinations for first-timers… (160, too long) | First safari? The easiest African parks to start with, how guiding works, what a safari day looks like and the mistakes first-timers make. **(138)** |
| H1 | Best Safari Destinations in Africa for First-Timers | Your First African Safari: Where to Go and What to Expect |

Links to add: `top-10-safari-destinations-africa-2026` ("best safari destinations in Africa"),
`how-to-choose-an-african-safari` ("plan your safari"), `africa-safari-cost-by-destination`
("what a safari costs"), `safari-packing-etiquette-first-timer-guide` ("what to pack"),
`best-family-safari-destinations-africa` ("safaris with children"). Remove the generic
three-link block.

### B3. Planning — `/articles/how-to-choose-an-african-safari`

Owns: how to plan a safari, choosing a safari, planning an African safari.

| Field | Current | Proposed |
|---|---|---|
| Title | How to Choose an African Safari: Ultimate Planning Guide | How to Plan an African Safari: A Step-by-Step Guide **(51)** |
| Description | Learn how to choose the right African safari based on travel goals… | Plan an African safari step by step: pick your goal, destination, season, budget and safari style, then compare operators before you book. **(138)** |
| H1 | How to Choose an African Safari: The Comprehensive Planning Framework | How to Plan an African Safari, Step by Step |

Fix the merged headings (section A). Links to add: `top-10-safari-destinations-africa-2026`,
`best-time-to-visit-african-safari-by-month` ("best time for a safari"),
`africa-safari-cost-by-destination`, `best-self-drive-safari-destinations-africa`
("self-drive safaris"), `safari-packing-etiquette-first-timer-guide`.

### B4. Timing — keep "best time" on the month page

`/articles/best-time-to-visit-african-safari-by-month` owns `best time for african safari`
(720 / 14) and `best time for safari` (170 / 19). The migration guide keeps migration terms
only.

| Field | Current | Proposed |
|---|---|---|
| Title | Best Time for an African Safari: 12-Month Guide | Best Time for an African Safari: Month-by-Month Guide **(53)** |
| Description | Compare January through December for African safaris… | The best time for an African safari, month by month: dry season, green season and shoulder months by region, with what each means for wildlife and cost. **(152)** |

Links to add: each "best time to visit" park page (Kruger, Masai Mara, Serengeti, Okavango,
Amboseli) from the "Region by region" section; `great-migration-guide-best-months-viewing-spots`
from the migration months.

`/articles/great-migration-guide-best-months-viewing-spots`: no title change. In the body,
link "best time for a safari in general" to the month page instead of repeating general
timing advice.

## C. Refresh pages that match measured demand

### C1. `/articles/best-time-to-visit-morocco` — `best time to visit morocco` 4.4K / 16

| Field | Current | Proposed |
|---|---|---|
| Title | Best Time to Visit Morocco by Region (36) | Best Time to Visit Morocco: Season-by-Season Guide **(50)** |
| Description | (146, fine) | The best time to visit Morocco for Marrakech, the coast, the Atlas and the Sahara: spring and autumn suit mixed trips, and how each season changes plans. **(153)** |

Secondary keywords to work into existing H2s where they read naturally: `best time of year to
visit morocco` (480 / 23), `best time to visit marrakech morocco` (320 / 26).
Links to add: `best-beaches-africa-ranked` already linked; add
`best-time-to-visit-african-safari-by-month` only if a safari-plus-Morocco itinerary is
discussed (otherwise none — avoid irrelevant links).

### C2. `/articles/africa-safari-cost-by-destination` — `african safari cost` 1.6K / 20

| Field | Current | Proposed |
|---|---|---|
| Title | How Much Does a Safari in Africa Cost? What Drives the Price | African Safari Cost: How Much a Safari Costs and Why **(52)** |
| Description | What drives the cost of an African safari… | How much an African safari costs: what drives the price by destination, season and style, the park fees and extras to budget for, and comparing quotes. **(151)** |
| H1 | How Much Does a Safari in Africa Cost? | How Much Does an African Safari Cost? |

Question to answer in the first section: `how much does an african safari cost` (720 / 22).
**Needs verified facts:** the off-page plan wants a dated summary table of official park and
conservation fees. Each fee must come from the authority's current tariff (SANParks, Kenya
Wildlife Service/Narok County, TANAPA, Botswana DWNP, RDB, UWA) with the date checked.

### C3. `/articles/best-beaches-africa-ranked` — `best beaches in africa` 1.6K / 20

| Field | Current | Proposed |
|---|---|---|
| Title | 7 Beautiful Beaches in Africa: Where to Go and Why | 7 Best Beaches in Africa: Where to Go and Why **(45)** |
| Description | Explore seven African beaches… | The best beaches in Africa, from Zanzibar and the Seychelles to Cape Town and Mauritius, compared for scenery, swimming, tides and access. **(138)** |
| H1 | Beautiful Beaches in Africa: Seven Coastal Experiences Compared | The Best Beaches in Africa: Seven Coastlines Compared |

Links to add: `best-time-to-visit-serengeti` from the Zanzibar section ("pair Zanzibar with a
Serengeti safari"), `top-10-safari-destinations-africa-2026` from the Kenya (Diani) section.

### C4. `/articles/best-time-to-visit-masai-mara` — 590 / 18

| Field | Current | Proposed |
|---|---|---|
| Title | Best Time to Visit Masai Mara: Season Guide & Migration | Best Time to Visit the Masai Mara: Month-by-Month Guide **(55)** |
| Description | Discover the best time to visit the Masai Mara… | The best time to visit the Masai Mara: dry season July–October, the green season and long rains, and how migration timing, crowds and cost change. **(146)** |

Check the description's month ranges against the page's own H2s before saving (they match the
current headings). Links to replace the generic block: `serengeti-vs-masai-mara`,
`great-migration-guide-best-months-viewing-spots`, `best-time-to-visit-serengeti`,
`best-time-to-visit-amboseli`, `kruger-vs-masai-mara`.

### C5. `/articles/best-time-to-visit-serengeti` — 480 / 22

| Field | Current | Proposed |
|---|---|---|
| Title | Best Time to Visit the Serengeti: Regional Safari Planning Guide (64) | Best Time to Visit the Serengeti: Region-by-Region Guide **(56)** |
| Description | Plan your trip with our guide… | The best time to visit the Serengeti depends on the region: calving in the south, river crossings in the north and year-round wildlife in the centre. **(149)** |

Links to replace the generic block: `great-migration-guide-best-months-viewing-spots`,
`serengeti-vs-masai-mara`, `best-time-to-visit-masai-mara`, `best-beaches-africa-ranked`
("add Zanzibar").

### C6. `/articles/best-time-to-visit-kruger` — `best time to visit kruger national park` 320 / 19

| Field | Current | Proposed |
|---|---|---|
| Title | Best Time to Visit Kruger National Park \| Seasonal Planning Guide (65) | Best Time to Visit Kruger National Park: Season Guide **(53)** |
| Description | Plan your trip with our guide… | The best time to visit Kruger National Park: dry winter for easier game viewing or green, rainy summer landscapes, and how to choose between them. **(146)** |

The live page does not discuss birding or newborn animals, so the description does not claim
them. Fix the duplicate heading (section A). Links to
replace the generic block: `kruger-vs-masai-mara`, `best-self-drive-safari-destinations-africa`,
`big-five-parks-ranked-where-to-see-all-five`, `affordable-safari-parks-ranked`.

### C7. `/articles/best-time-to-visit-okavango-delta` — 320 / 13

GSC position 81 for its exact query: the page is indexed but not yet relevant enough.

| Field | Current | Proposed |
|---|---|---|
| Title | Best Time to Visit the Okavango Delta: Ultimate Safari Guide | Best Time to Visit the Okavango Delta: Flood & Dry Seasons **(58)** |
| Description | Plan your Botswana adventure… | The best time to visit the Okavango Delta: May to October for the flood and dry-season game viewing, plus how early and late season compare in Botswana. **(152)** |

Secondary: `best time to visit botswana okavango delta` (110 / 15) — use "Botswana" in the
first paragraph. Links to replace the generic block: `top-10-safari-destinations-africa-2026`,
`africa-safari-cost-by-destination`, `best-time-to-visit-african-safari-by-month`,
`where-to-see-rhinos-in-africa` (only if the page mentions rhinos).

## D. Merge the overlapping startup pages

`top-african-startup-ecosystems` and `fastest-growing-tech-hubs-africa` serve the same intent
and demand is low (`african startups` 70 / 22). Recommended:

1. Keep `/articles/top-african-startup-ecosystems` (stronger, sourced comparison).
2. Move any unique, still-sourced material from the tech-hubs page (Cairo/Egypt, talent costs,
   infrastructure) into it as new sections.
3. Unpublish `fastest-growing-tech-hubs-africa` and add a 301 redirect to the ecosystems page
   (needs a code change in routes; request it once the merge is approved).
4. Until the merge, fix the broken description on the tech-hubs page:
   "Lagos, Nairobi and Cape Town compared as African tech hubs: talent, funding, infrastructure
   and why funding totals can mislead. Cairo is covered too." **(138)**

## E. Order of work

1. Section A defect fixes (no SEO judgement needed).
2. B1–B4 together, so the three safari pages change angle at the same time.
3. C1–C7, one page per day; note each publish date.
4. D once the owner approves the merge and redirect.
5. Re-check Search Console on 9 Dec 2026 (four weeks of data) before further changes; titles
   need time to settle.
