# Keyword research — 18 November 2026

Status: research and proposals for the owner. Nothing has been published, edited or submitted
on africanranked.com as part of this work.

## 1. Method and data provenance

- **Tool:** Semrush, read in the browser on 18 November 2026 (7-day Pro trial started by the
  owner that afternoon; before that, a free account limited to 10 rows per table).
- **Database:** United States (`db=us`), desktop. Semrush's data date for domain reports was
  **17 November 2026**.
- **Rule:** every volume, KD, CPC, position and backlink figure below was read from a Semrush
  screen. Nothing is estimated. Where Semrush returned no figure, the cell says so.
- **Search Console** figures (labelled *GSC*) come from the owner's Search Console property
  `sc-domain:africanranked.com`, Performance report, data 11–15 November 2026 (the first five
  days of data). They are Google's own figures, not Semrush's.
- **What was read:**
  - Organic Research → Overview and Positions for africanranked.com.
  - Organic Research → Competitors for safaribookings.com (africanranked.com had no competitors
    in Semrush because it ranks for one keyword).
  - Keyword Gap (africanranked.com vs 4 competitors): Missing, Weak and Untapped tabs, filtered to
    KD 0–50, sorted by volume, first 100 rows of each.
  - Keyword Magic Tool: the first 100 rows (sorted by volume) of each seed, then kept rows with
    volume ≥ 100 and KD ≤ 40. "Questions" view read for `gorilla trekking` and `african safari`.
  - Backlink Gap (same 5 domains): "Best" view, first 100 rows sorted by Authority Score.
  - Keyword Overview → Bulk Analysis for the Search Console quick-win queries.
- **Not captured:** the Keyword Gap table shows competitor *positions*, not their ranking URLs;
  the best-ranking competitor column therefore gives the domain and position only. Backlink Gap
  shows referring domains, not the linking pages; the "page type" column in section 9 is my
  classification of the site, not a verified look at each linking page.
- **Raw data** (every row read) is in `docs/keyword-research-2026-11-18/`:
  `gap-missing-kd50.csv`, `gap-untapped-kd50.csv`, `keyword-magic.csv`, `backlink-gap.csv`,
  and `keyword-map-scored.csv` (every keyword with its mapping and score).

## 2. Baseline

Semrush, Organic Research, US, 17 Nov 2026:

| Metric | Value |
|---|---|
| Organic keywords | 1 |
| Organic traffic (Semrush estimate) | 0 |
| Traffic cost | $0 |

| Keyword | Position | Volume | KD | Intent | URL |
|---|---|---|---|---|---|
| biggest economy of africa | 71 | 720 | 46 | Informational | /articles/largest-economies-in-africa |

There are no Semrush keywords in positions 4–20, so Semrush shows **no quick wins yet**. The
site is new: Search Console data starts on 11 November 2026, and Semrush's US index has not
picked up the other 32 articles.

## 3. Competitors

Semrush lists no organic competitors for africanranked.com (one keyword is too few). I used
safaribookings.com's competitor report instead. Top 10 shown by Semrush (US):

| # | Domain | Competition level | Common keywords with safaribookings | Keywords | Traffic |
|---|---|---|---|---|---|
| 1 | go2africa.com | 31% | 2.4K | 21.5K | 50.8K |
| 2 | africanbudgetsafaris.com | 17% | 762 | 8.5K | 10.4K |
| 3 | yellowzebrasafaris.com | 16% | 919 | 9.8K | 8K |
| 4 | ellamckendrick.com | 14% | 540 | 4.1K | 7.1K |
| 5 | safari.com | 14% | 743 | 7.7K | 11.1K |
| 6 | discoverafrica.com | 14% | 975 | 15.3K | 20.2K |
| 7 | asiliaafrica.com | 13% | 670 | 5.5K | 19.2K |
| 8 | expertafrica.com | 13% | 790 | 10.7K | 16.4K |
| 9 | micato.com | 13% | 788 | 5.7K | 27.5K |
| 10 | andbeyond.com | 13% | 1.1K | 19.4K | 78.9K |

safaribookings.com itself: 19.8K keywords, 49.3K traffic.

**Chosen for the gap (4):**

| Domain | Keywords in gap report (US) | Why |
|---|---|---|
| safaribookings.com | 11K | Review-and-guide site with broad park and planning content; the closest model for an editorial safari publisher. |
| go2africa.com | 12.8K | Highest overlap with safaribookings (31%) and a large guide library. |
| discoverafrica.com | 11K | Guide-led site covering destinations and timing. |
| africageographic.com | 8.3K | The editorial wildlife magazine; represents the wildlife and conservation side. |

The candidate domain `africa-geographic.com` returned **0 keywords**; the magazine's real domain
is `africageographic.com`. timeout.com and lonelyplanet.com were not used: their Africa content
is a small slice of very large sites, so a root-domain gap would be dominated by unrelated
keywords. Pure operators (andbeyond, micato, asiliaafrica) were left out because their keyword
sets are tour sales pages rather than guides.

## 4. Quick wins (positions 4–20)

Semrush has none. Search Console (11–15 Nov) shows a handful of queries already near page one.
Semrush figures from Keyword Overview → Bulk Analysis (US).

| Keyword | Position (GSC) | Volume | KD | URL | Action |
|---|---|---|---|---|---|
| african cuisine | 10.8 (58 impressions) | not read¹ | not read¹ | /articles/best-african-cuisines-ranked-2026 | Revision published 18 Nov (new title, short answer, regional section). Watch CTR and position for 28 days before more changes. |
| best african cuisines | 7.0 | 40 | 35 | same | Covered by the same revision. |
| best african cuisines to explore | 7.5 | 0 | n/a | same | No action; tiny demand. |
| how big is nollywood | 11.0 (1 click) | 0 | n/a | /articles/nollywood-explained | Add a one-line direct answer near the top (size by output and revenue, sourced). Low demand. |
| top 5 meilleur artiste africain 2026 | 7.0 | n/a (French query) | n/a | /articles/top-african-musicians-2026 | French-language demand; outside the US database. No action now. |

¹ The bulk entry for "african cuisine" was merged with other text and returned no metrics;
related figures read in the Keyword Magic Tool: `african cuisine food` 6.6K / KD 38,
`african food` 27.1K / KD 39, `best african cuisine` 210 / KD 30 (bulk).

**Pages with demand but not yet ranking** (GSC position > 20, Semrush figures):
`best time to visit okavango delta` — GSC position 81; Semrush 320 / KD 13 / commercial →
/articles/best-time-to-visit-okavango-delta. `biggest economy of africa` — Semrush position 71;
720 / KD 46.

## 5. Keyword gap

Counts after the KD 0–50 filter: **Missing 184**, **Weak 0**, **Untapped 29.8K** (unfiltered:
Missing 214, Untapped 31.7K). Weak is empty because we rank for only one keyword.

### 5a. Missing — every competitor ranks, we don't (top 30 of 100 read, by volume)

| Keyword | Volume | KD | Intent | Best competitor (position) |
|---|---|---|---|---|
| south africa safari | 5.4K | 46 | I | go2africa #5 |
| kruger national park safari | 4.4K | 45 | C | safaribookings #2 |
| kenya safari | 3.6K | 37 | C | safaribookings #1 |
| kruger safari africa | 3.6K | 28 | C | africageographic #2 |
| masai mara safari | 3.6K | 43 | n/a | go2africa #5 |
| safari of kenya | 3.6K | 43 | C | safaribookings #1 |
| south african safari | 2.9K | 30 | C | safaribookings #3 |
| safaris kruger park south africa | 2.4K | 46 | C | africageographic #7 |
| african safari south | 1.9K | 44 | C | go2africa #1 |
| south africa safaris | 1.9K | 36 | C | safaribookings #17 |
| victoria falls zambia | 1.9K | 34 | I | africageographic #8 |
| victoria falls zambia zimbabwe | 1.6K | 43 | C | go2africa #3 |
| mara masai | 1.3K | 41 | I | africageographic #8 |
| south africa safari tour | 1.3K | 27 | C | safaribookings #5 |
| zambia safari | 1.3K | 27 | I | go2africa #4 |
| kruger safari | 1K | 49 | C | safaribookings #3 |
| masai mara kenya | 1K | 47 | IT | discoverafrica #10 |
| safaris in south africa | 1K | 34 | I | safaribookings #2 |
| african luxury safari | 880 | 38 | C | go2africa #13 |
| hwange national park zimbabwe | 880 | 28 | I | go2africa #7 |
| kwando | 880 | 47 | IN | safaribookings #5 |
| luxury african safaris | 880 | 42 | C | go2africa #15 |
| stone town in zanzibar | 720 | 36 | I | go2africa #14 |
| south africa safari trip | 590 | 29 | I | safaribookings #3 |
| south african safaris | 590 | 34 | I | safaribookings #2 |
| timbavati game reserve | 590 | 32 | C | go2africa #16 |
| trip to safari south africa | 590 | 44 | I | safaribookings #1 |
| kruger park game reserve | 480 | 41 | I | go2africa #13 |
| masaimara | 480 | 49 | IT | go2africa #3 |
| photo safaris | 480 | 30 | I | go2africa #5 |

All 100 rows: `docs/keyword-research-2026-11-18/gap-missing-kd50.csv`.

### 5b. Untapped — at least one competitor ranks (top 100 read)

The Untapped list is dominated by africageographic.com's animal-encyclopedia rankings
(`shoebill` 90.5K/45, `caracal` 74K/50, `serval` 74K/50, `impala` 60.5K/41, `wildebeest`
49.5K/42…). These are informational, ads-only queries where Wikipedia and wildlife sites
lead; they are **deprioritised** (see scoring). The commercially useful Untapped rows are:

| Keyword | Volume | KD | Intent | Best competitor (position) | Maps to |
|---|---|---|---|---|---|
| safari tours | 60.5K | 36 | C | safaribookings #1 | Safari hub (top-10 page) |
| zanzibar and | 40.5K | 14 | IT | discoverafrica #40 | New: Zanzibar guide² |
| mount kilimanjaro | 40.5K | 50 | IT | discoverafrica #8 | New: Kilimanjaro guide |
| afrika tanzania | 22.2K | 19 | IT | discoverafrica #8 | New: Tanzania safari guide² |
| africa and namibia | 22.2K | 30 | I | go2africa #13 | New: Namibia safari guide² |
| afrika kenya | 14.8K | 36 | IT | go2africa #8 | New: Kenya safari guide² |
| maasai mara | 12.1K | 40 | IT | go2africa #12 | New: Masai Mara guide |
| african safari safari | 9.9K | 50 | C | go2africa #1 | Safari hub |
| african safari packages | 8.1K | 41 | CI | go2africa #2 | Safari hub |
| tour safari africa | 8.1K | 41 | CI | go2africa #2 | Safari hub |
| ngorongoro crater | 6.6K | 47 | T | go2africa #8 | Great migration / Tanzania |
| africa safari | 6.6K | 45 | C | go2africa #2 | Safari hub |

² Semrush keyword strings such as "zanzibar and", "afrika kenya" and "africa and namibia" are
odd variants with large volumes. Treat them as signals that the country/place head term has
demand, not as exact phrases to put in titles. Use the clean commercial phrase (e.g. `kenya
safari` 3.6K/37) as the primary target.

All 100 rows: `docs/keyword-research-2026-11-18/gap-untapped-kd50.csv`.

## 6. Keyword Magic Tool (volume ≥ 100, KD ≤ 40)

| Seed | Keywords in Semrush | Kept | Highlights (volume / KD / intent) |
|---|---|---|---|
| safari | 660.2K | 2 relevant | Top 100 dominated by the Safari web browser and US zoo parks. Relevant: `safari tours` 60.5K/33/I, `tour safari africa` 8.1K/35/I. Re-ran as `african safari`. |
| african safari | 25.1K | 64 + 23 questions | `african safari trip` 2.9K/7/IC, `best safari in africa` 1.9K/36/C, `tipping in south africa safari` 1.9K/13/I, `african safari cost` 1.6K/20/I, `luxury african safari` 1.6K/37/C, `south africa safari packages` 1.0K/18/I, `best time for african safari` 720/14/C, `best african safari tour companies` 720/37/C (CPC $9.34), `travel insurance for african safari` 590/15/C (CPC $10.96), `african safari honeymoon` 590/1/I. Questions: `how much does an african safari cost` 720/22, `what to wear on african safari` 210/17, `are african safaris safe` 110/7. |
| gorilla trekking | 1.2K | 29 | Head term `gorilla trekking` 1.9K/41 (above the KD cut). `gorilla trekking rwanda` 1.6K/34, `uganda gorilla trekking` 1.0K/28, `gorilla trekking permit` 210/31, `luxury gorilla trekking rwanda` 260/20/C, `rwanda vs uganda gorilla trekking` 110/12/C. |
| best time to visit serengeti | 44 | 5 | `best time to visit serengeti` 480/22/C (+ national-park and Tanzania variants: 320, 260, 210, 140). |
| best time to visit masai mara | 53 | 7 | `best time to visit masai mara` 590/18/C (+ 320, 210, 170, 110×3). |
| best time to visit kruger | 53 | 9 | `best time to visit kruger national park` 320/19/C (+ 210, 210, 170, 140, 140, 110×3). |
| best time to visit okavango | 18 | 2 | `best time to visit okavango delta` 320/13/C, `…botswana okavango delta` 110/15. |
| best time to visit amboseli | 5 | 0 | Largest: 20/month. The head term `amboseli national park` (3,600, Keyword Gap top opportunities) is the better target. |
| best time to visit morocco | 144 | 8 | `best time to visit morocco` 4.4K/16/C, `best time of year to visit morocco` 480/23, `when is the best time…` 480/23, `best time to visit marrakech morocco` 320/26. |
| african food | 21.8K | 23 relevant | `african food` 27.1K/39/I, `west african food` 8.1K/39, `african cuisine food` 6.6K/38, `nigerian african food` 6.6K/30, `south african food` 5.4K/39/C, `african food recipes` 1.9K/27, `traditional african food` 1.0K/30, `best african food` 590/24/C, `best food in africa` 590/28/C. Excluded "near me", store, pet-food and B2B rows. |
| african music | 24.5K | 18 relevant | `african musical instruments` 2.4K/16, `african music kenya` 2.4K/34, `grammy award for best african music performance` 1.3K/38/C, `african music genres` 390/26, `top african music` 480/35. Excluded US "African American music" museum rows. |
| african startups | 1.2K | 0 | Nothing passes. `african startups` 70/22; `top ai startups africa trends` 720/KD n/a. Weak organic demand in the US. |
| largest economies in africa | 262 | 1 | `largest economy in africa` 590/38/I. `largest economies in africa` 210/49 fails the KD cut. |
| best beaches in africa | 204 | 7 | `best beaches in africa` 1.6K/20/C, `best beach in africa` 320/10, `best beaches in south africa` 320/22, `best beach resorts in south africa` 210/11 (CPC $2.94), `best beach resorts in africa` 140/5. |

All rows: `docs/keyword-research-2026-11-18/keyword-magic.csv`.

## 7. Analysis: mapping, clusters and priority

### Scoring

`priority = volume × intent fit ÷ max(KD, 10)`, then ×1.5 for the safari clusters and ×0.5 for
encyclopedia-type keywords. KD is floored at 10 so that KD 0–9 rows (e.g. `african safari
honeymoon` KD 1) don't dominate. **Intent fit** (my judgement of ads + affiliate value):

| Fit | Applies to |
|---|---|
| 1.0 | Commercial/transactional safari, tour, trek or resort queries (affiliate: tours, lodges, insurance) |
| 0.8 | Planning queries: cost, best time, packing, insurance, tipping, "safari" informational |
| 0.3 | General informational (ads only) |
| 0 | "near me", restaurants, stores, US attractions, hunting (off-brand) |

Every keyword with its mapping, fit and score: `keyword-map-scored.csv`.

### Clusters, pillars and page mapping (by total priority)

| Rank | Cluster | Pillar | Target page | Keywords | Combined volume | Lead keyword (vol / KD) |
|---|---|---|---|---|---|---|
| 1 | Safari hub | /articles/top-10-safari-destinations-africa-2026 | existing | 43 | 117,800 | safari tours 60.5K / 36 |
| 2 | Destination: South Africa | South Africa safari guide | **new** | 25 | 55,710 | south africa safari 5.4K / 46 |
| 3 | Beaches & islands | best-beaches-africa-ranked | **new**: Zanzibar guide | 4 | 65,920 | zanzibar and 40.5K / 14 |
| 4 | Destination: Kenya | Kenya safari guide | **new** | 15 | 64,910 | kenya safari 3.6K / 37 |
| 5 | Destination: Namibia | Namibia safari guide | **new** | 6 | 72,800 | africa and namibia 22.2K / 30 |
| 6 | Food | best-african-cuisines-ranked-2026 | existing | 21 | 78,220 | african food 27.1K / 39 |
| 7 | Destination: Tanzania | Tanzania safari guide | **new** | 2 | 25,100 | afrika tanzania 22.2K / 19 |
| 8 | Destination: South Africa | South Africa guide | **new**: Kruger safari guide | 10 | 14,500 | kruger national park safari 4.4K / 45 |
| 9 | Morocco | best-time-to-visit-morocco | existing | 8 | 6,580 | best time to visit morocco 4.4K / 16 |
| 10 | Destination: Kenya | Kenya guide | **new**: Masai Mara safari guide | 8 | 26,990 | maasai mara 12.1K / 40 |
| 11 | Safari cost & planning | africa-safari-cost-by-destination | existing | 11 | 5,260 | african safari cost 1.6K / 20 |
| 12 | Kilimanjaro | Tanzania guide | **new**: Climbing Kilimanjaro | 2 | 48,600 | mount kilimanjaro 40.5K / 50 |
| 13 | Safari styles | Safari hub | **new**: Luxury African safaris | 7 | 5,800 | luxury african safari 1.6K / 37 |
| 14 | Safari cost & planning | cost page | **new**: Safari tipping guide | 2 | 1,990 | tipping in south africa safari 1.9K / 13 |
| 15 | Gorilla trekking | best-gorilla-trekking-africa-ranked | **new**: Gorilla trekking in Rwanda | 8 | 4,060 | gorilla trekking rwanda 1.6K / 34 |
| 16 | Safari timing | best-time-to-visit-african-safari-by-month | existing | 7 | 1,510 | best time for african safari 720 / 14 |
| 17 | Beaches | best-beaches-africa-ranked | existing | 6 | 2,700 | best beaches in africa 1.6K / 20 |
| 18 | Safari timing | best-time-to-visit-masai-mara | existing | 7 | 1,620 | best time to visit masai mara 590 / 18 |
| 19 | Gorilla trekking | gorilla pillar | **new**: Gorilla trekking in Uganda (Bwindi) | 9 | 3,300 | uganda gorilla trekking 1.0K / 28 |
| 20 | Safari timing | best-time-to-visit-kruger | existing | 9 | 1,520 | best time to visit kruger national park 320 / 19 |

Further rows (photographic safaris, Victoria Falls, Zambia & Zimbabwe, Botswana, travel insurance,
honeymoon, walking safaris, music genres, instruments, economies) are in the CSV.

**Proposed cluster structure (safari first):**

- **Pillar: Best safaris in Africa** (`/articles/top-10-safari-destinations-africa-2026`) →
  country guides (South Africa, Kenya, Tanzania, Botswana, Namibia, Zambia & Zimbabwe) →
  park guides (Kruger, Masai Mara, Serengeti via the migration guide) → existing
  "best time to visit" pages under each park.
- **Pillar: Planning a safari** (`/articles/how-to-choose-an-african-safari`) → cost (existing),
  tipping (new), travel insurance (new), packing (existing), safety (new), styles (luxury,
  photographic, walking, honeymoon, safari-and-beach — new).
- **Pillar: Gorilla trekking** (`/articles/best-gorilla-trekking-africa-ranked`) → Rwanda (new),
  Uganda/Bwindi (new), permits (existing).
- **Food, Music, Morocco, Beaches, Economy:** maintain and refresh existing pages; no new pages
  until the safari cluster is built out (startups in particular show almost no US demand).

## 8. New-article briefs — top 15 opportunities

Figures are Semrush US (volume / KD / intent). Internal links point to existing published URLs.

**1. South Africa safari guide** — target `south africa safari` (5.4K / 46 / I). Secondary:
`south african safari` 2.9K/30, `south africa safaris` 1.9K/36, `safaris in south africa`
1.0K/34, `south africa safari packages` 1.0K/18, `best safari in south africa` 590/29,
`luxury safari south africa` 590/24, `south africa cape town and safari` 880/7. Intent:
commercial planning. Title: *South Africa Safari Guide: Best Parks, Costs and When to Go*. H2s:
Where to go (Kruger, Sabi Sand, Madikwe, Pilanesberg, Addo, KTP) · Private reserve vs national
park · Self-drive vs guided · Costs by style · Best time · Combining Cape Town and a safari ·
Malaria-free options · How to book. Links: best-time-to-visit-kruger, kruger-vs-masai-mara,
best-self-drive-safari-destinations-africa, africa-safari-cost-by-destination,
big-five-parks-ranked-where-to-see-all-five.

**2. Kenya safari guide** — target `kenya safari` (3.6K / 37 / C). Secondary: `safari of kenya`
3.6K/43, `african safari kenya` 880/39, `samburu national park safari` 140/24, `kenya laikipia`
110/15; head-term signals `afrika kenya` 14.8K/36, `nairobi kenya` 12.1K/44. Title: *Kenya
Safari Guide: Parks, Seasons and Sample Itineraries*. H2s: Masai Mara · Amboseli · Samburu ·
Laikipia conservancies · Tsavo · Best time by park · Costs and park fees (dated, official
sources) · Getting around · Sample 5-, 7- and 10-day routes. Links: best-time-to-visit-masai-mara,
best-time-to-visit-amboseli, serengeti-vs-masai-mara, great-migration-guide-best-months-viewing-spots.

**3. Kruger National Park safari guide** — target `kruger national park safari` (4.4K / 45 / C).
Secondary: `kruger safari africa` 3.6K/28, `safaris kruger park south africa` 2.4K/46,
`kruger safari` 1K/49, `kruger park game reserve` 480/41, `timbavati game reserve` 590/32.
Title: *Kruger Safari Guide: Rest Camps, Private Reserves and Self-Drive*. H2s: Kruger vs the
private reserves (Sabi Sand, Timbavati, Klaserie) · Self-drive routes · Rest camps · Day vs
overnight · Costs and conservation fees · Best time. Links: best-time-to-visit-kruger,
kruger-vs-masai-mara, best-self-drive-safari-destinations-africa.

**4. Masai Mara safari guide** — target `masai mara safari` (3.6K / 43). Secondary: `maasai mara`
12.1K/40/IT, `mara masai` 1.3K/41, `masai mara kenya` 1K/47, `masaimara` 480/49, `african safari
masai mara` 320/41. Title: *Masai Mara Safari Guide: Reserve, Conservancies and Migration*. H2s:
Reserve vs conservancies · Where to stay by area · Migration river crossings · Costs and fees ·
Getting there from Nairobi · Responsible visits. Links: best-time-to-visit-masai-mara,
serengeti-vs-masai-mara, kruger-vs-masai-mara, great-migration-guide-best-months-viewing-spots.

**5. African safari tipping guide** — target `tipping in south africa safari` (1.9K / 13 / I).
Secondary: `africa travel resource tipping on safari` 90/17. Title: *Safari Tipping Guide: How
Much to Tip Guides, Trackers and Lodge Staff*. H2s: Who to tip · Typical ranges by country (cite
operator and lodge published guidance, dated) · Currency and cash · Group vs private safaris ·
Tipping on gorilla treks. Links: africa-safari-cost-by-destination,
safari-packing-etiquette-first-timer-guide.

**6. Luxury African safaris** — target `luxury african safari` (1.6K / 37 / C). Secondary:
`luxury safari africa` 1.0K/34, `african luxury safari` 880/38, `luxury african safaris`
880/42, `luxury south africa safari` 590/31, `luxury gorilla trekking rwanda` 260/20. Title:
*Luxury African Safaris: Where to Go and What You Get for the Money*. H2s: What "luxury" means
on safari · Best regions (Okavango, Sabi Sand, Mara conservancies, Rwanda) · Fly-in vs drive ·
Price bands (dated) · Booking direct vs specialist. Links: africa-safari-cost-by-destination,
best-time-to-visit-okavango-delta, best-gorilla-trekking-africa-ranked.

**7. Gorilla trekking in Rwanda** — target `gorilla trekking rwanda` (1.6K / 34 / I). Secondary:
`rwanda gorilla trekking` 1.0K/39, `gorilla trekking in rwanda` 480/34, `gorilla trekking
volcanoes national park` 170/22, `mountain gorilla trekking rwanda` 170/10, `rwanda gorilla
trekking tours` 210/36. Title: *Gorilla Trekking in Rwanda: Permits, Volcanoes NP and What to
Expect*. H2s: Permit price and booking (Rwanda Development Board, dated) · The trek day ·
Fitness and age rules · Best time · Lodges · Combining with Nyungwe or Akagera. Links:
gorilla-trekking-permit-cost-how-to-book, best-gorilla-trekking-africa-ranked.

**8. Gorilla trekking in Uganda (Bwindi)** — target `uganda gorilla trekking` (1.0K / 28 / I).
Secondary: `gorilla trekking uganda` 1.0K/30, `gorilla trekking in uganda` 390/23, `bwindi
gorilla trekking` 170/26, `uganda gorilla trekking tours` 210/22/C, `rwanda vs uganda gorilla
trekking` 110/12/C. Title: *Gorilla Trekking in Uganda: Bwindi and Mgahinga Guide*. H2s: Bwindi
sectors · Permits (Uganda Wildlife Authority, dated) · Habituation experience · Getting there ·
Rwanda vs Uganda. Links: gorilla-trekking-permit-cost-how-to-book, best-gorilla-trekking-africa-ranked.

**9. Tanzania safari guide** — target `safari in africa tanzania` (2.9K / 37). Head-term signal:
`afrika tanzania` 22.2K/19. Secondary: `ngorongoro crater` 6.6K/47/T, `ngorongoro crater in
tanzania` 110/37, `great migration safari tanzania` 110/29. Title: *Tanzania Safari Guide:
Serengeti, Ngorongoro and the Southern Parks*. H2s: Northern circuit · Southern parks (Nyerere,
Ruaha) · Migration by month · Park fees (TANAPA, dated) · Adding Zanzibar. Links:
best-time-to-visit-serengeti, great-migration-guide-best-months-viewing-spots,
serengeti-vs-masai-mara.

**10. African photographic safaris** — target `african photo safari` (720 / 25 / I). Secondary:
`photo safaris` 480/30, `photographic safaris` 260/24, `african photographic safari` 140/20,
`photography tours africa` 140/4, `africa photography tours` 140/16. Title: *African Photo
Safaris: Best Destinations, Vehicles and Gear*. H2s: Best parks for photography · Specialist
vehicles and hides · Season and light · Gear and luggage limits · Ethics. Links:
best-places-to-see-big-cats-africa, safari-packing-etiquette-first-timer-guide.

**11. Victoria Falls: Zambia vs Zimbabwe** — target `victoria falls zambia` (1.9K / 34 / I).
Secondary: `victoria falls zambia zimbabwe` 1.6K/43/C, `victoria falls in zambia` 320/37,
`safari in victoria falls` 210/22, `victoria falls attractions` 140/17. Title: *Victoria Falls:
Zambia or Zimbabwe Side, Best Time and Safari Add-ons*. H2s: Which side · Water levels by month
· Visas (KAZA UniVisa status, dated) · Activities · Combining with Chobe or Hwange. Links:
best-time-to-visit-okavango-delta, top-10-safari-destinations-africa-2026.

**12. Travel insurance for an African safari** — target `travel insurance for african safari`
(590 / 15 / C, CPC $10.96 — the highest CPC read). Secondary: `what travel insurance should i
get for african safari trip` 320/15. Title: *Travel Insurance for an African Safari: What Cover
You Need*. H2s: Medical evacuation · Activity exclusions (walking, gorilla treks, Kilimanjaro) ·
Cancellation · Pre-existing conditions · How to compare. Affiliate disclosure required. Links:
africa-safari-cost-by-destination, how-to-choose-an-african-safari. *Financial-product content:
needs careful, factual wording and owner review.*

**13. Zanzibar guide** — target `stone town in zanzibar` (720 / 36 / I) with the head-term signal
`zanzibar and` 40.5K/14. Secondary: `zanzibar a tanzania` 14.8K/33, `stone town` 9.9K/39. Title:
*Zanzibar Travel Guide: Stone Town, Beaches and Safari-and-Beach Trips*. H2s: Stone Town · Best
beaches by coast · Best time · Combining with a Tanzania safari · Getting there. Links:
best-beaches-africa-ranked, best-time-to-visit-serengeti.

**14. Namibia safari guide** — head-term signals `africa and namibia` 22.2K/30, `republic of
namibia` 12.1K/35. Clean safari phrases were not in the rows read; check `namibia safari`
in Keyword Overview before writing. Title: *Namibia Travel Guide: Etosha, Sossusvlei and
Self-Drive Routes*. H2s: Etosha · Sossusvlei and the Namib · Damaraland · Self-drive safety ·
Best time. Links: best-self-drive-safari-destinations-africa, where-to-see-rhinos-in-africa.
(Unpublished Etosha and Namibia manuscripts exist on the PR #9 branch.)

**15. African safari honeymoon** — target `african safari honeymoon` (590 / 1 / I). Title:
*African Safari Honeymoon: Best Destinations and Safari-and-Beach Pairings*. H2s: Best regions ·
Safari + beach (Zanzibar, Mozambique, Seychelles) · Budget bands · Best time. Links:
best-beaches-africa-ranked, best-time-to-visit-african-safari-by-month. Related demand:
`african safari and beach vacation` 110/7, `beach and safari vacations` 140/7.

Not briefed despite volume: `mount kilimanjaro` (40.5K / 50 — KD at the ceiling and the SERP is
reference-heavy; `mountain climbing kilimanjaro` read 40.5K / 60 in the unfiltered gap) and the
wildlife encyclopedia terms.

## 9. Cannibalisation

No observed cannibalisation yet: Semrush ranks only one URL, and Search Console has five days of
data with no query split across two of our pages. The risks below are **intent overlaps between
existing pages**, to fix before they compete:

| Query group | Pages competing | Recommendation |
|---|---|---|
| `best safari in africa` (1.9K/36), `best african safari` (1.0K/25), `best place for safari in africa` (390/10) | top-10-safari-destinations-africa-2026; best-safari-destinations-africa-first-timers; how-to-choose-an-african-safari | Make the top-10 page the pillar for "best safari in Africa". Re-angle first-timers to "first safari" queries; re-angle how-to-choose to "how to plan a safari". Cross-link with distinct anchors. |
| `best time for african safari` (720/14), `best time for safari` (170/19) | best-time-to-visit-african-safari-by-month; great-migration-guide-best-months-viewing-spots | Keep "best time" on the month page; the migration guide should target migration terms only. |
| Gorilla trekking country queries | best-gorilla-trekking-africa-ranked; gorilla-trekking-permit-cost-how-to-book; proposed Rwanda and Uganda pages | Ranked page = pillar for `gorilla trekking` (1.9K/41); permit page owns `gorilla trekking permit` (210/31); country pages own country queries. |
| Startups and tech | top-african-startups-2026; top-african-startup-ecosystems; fastest-growing-tech-hubs-africa | "Ecosystems" and "tech hubs" serve the same intent. Merge into one page (301 the other) when next edited. Demand is low (section 6). |
| Masai Mara | best-time-to-visit-masai-mara; serengeti-vs-masai-mara; proposed Masai Mara guide | Keep timing, comparison and guide intents separate; guide links to both. |

## 10. Backlink targets

Semrush Backlink Gap: **27,592** referring domains link to at least one competitor and not to us.
Below: the top 40 relevant domains from the "Best" view (all link to all four competitors,
`Matches 4/5`, `links to africanranked = 0`), sorted by Authority Score, with the two
foreign-language communities listed last. Excluded: search
engines, Wikipedia/clones, UGC platforms (Medium, Substack, Beehiiv, Steemit), SEO/spam
directories, adult, unrelated categories and a hunting forum. "Page type" is my classification
of the site, not a check of each linking page.

| # | Domain | AS | Monthly visits | Links to SB / G2A / DA / AG | Likely page type | Approach |
|---|---|---|---|---|---|---|
| 1 | nytimes.com | 100 | 294M | 1 / 2 / 3 / 4 | News | Digital PR only (long shot) |
| 2 | dailymail.co.uk | 95 | 12.7M | 2 / 4 / 2 / 5 | News | Digital PR (wildlife/travel stories) |
| 3 | businessinsider.com | 85 | 42.4M | 3 / 17 / 3 / 2 | News / travel features | Data-led PR (safari cost data) |
| 4 | cntraveler.com | 77 | 3.3M | 1 / 27 / 4 / 3 | Travel magazine | Expert quotes via journalist requests |
| 5 | thetimes.com | 77 | 18.6M | 3 / 1 / 1 / 1 | News / travel | Digital PR |
| 6 | travelandleisure.com | 73 | 11M | 1 / 15 / 1 / 1 | Travel magazine | Expert quotes |
| 7 | thesouthafrican.com | 68 | 4.1M | 5 / 5 / 4 / 5 | News (SA) | Pitch Kruger/SA safari data stories |
| 8 | timeslive.co.za | 63 | 2.6M | 5 / 1 / 3 / 3 | News (SA) | Pitch SA travel stories |
| 9 | sahistory.org.za | 58 | 727K | 3 / 2 / 2 / 12 | Education / resource | Resource-page suggestion |
| 10 | mongabay.com | 56 | 4.7M | 1 / 11 / 1 / 18 | Conservation news | Cite-worthy conservation data (rhinos) |
| 11 | thetravel.com | 51 | 1.6M | 2 / 5 / 2 / 2 | Travel blog / listicles | Pitch ranked guides |
| 12 | bizcommunity.com | 47 | 450K | 1 / 2 / 18 / 14 | Business news (SA) | Press release on original data |
| 13 | 2oceansvibe.com | 42 | 150K | 1 / 2 / 4 / 14 | Lifestyle blog (SA) | Pitch Cape Town + safari |
| 14 | worldtravelawards.com | 40 | 193K | 1 / 10 / 3 / 8 | Awards / directory | Not applicable until eligible |
| 15 | getaway.co.za | 36 | 146K | 2 / 15 / 25 / 24 | Travel magazine (SA) | Guest expertise / data |
| 16 | africafreak.com | 35 | 18K | 10 / 1 / 4 / 15 | Safari blog / community | Resource suggestion |
| 17 | animalsaroundtheglobe.com | 32 | 335K | 3 / 3 / 4 / 1 | Wildlife travel blog | Resource / guest post |
| 18 | roxannereid.co.za | 32 | 5.9K | 2 / 4 / 4 / 2 | Travel blog (SA) | Blogger outreach |
| 19 | safarisafricana.com | 31 | 8.2K | 1 / 3 / 1 / 5 | Safari blog | Resource suggestion |
| 20 | keeptheplanet.org | 27 | 3.9K | 35 / 7 / 1 / 61 | Nonprofit / resource lists | Resource page |
| 21 | krugergatehotel.com | 27 | 17.8K | 1 / 2 / 1 / 1 | Operator / lodge | Kruger guide as guest resource |
| 22 | rexclarkeadventures.com | 24 | 1.4K | 7 / 6 / 3 / 1 | Operator blog | Resource suggestion |
| 23 | we3travel.com | 21 | 45.4K | 1 / 4 / 2 / 5 | Family travel blog | Family safari guide |
| 24 | animalko.com | 20 | 10.1K | 6 / 2 / 6 / 32 | Wildlife blog | Resource suggestion |
| 25 | dumaexplorer.com | 19 | 3.8K | 38 / 20 / 2 / 3 | Operator blog | Resource suggestion |
| 26 | nativeafricatours.com | 18 | 899 | 6 / 2 / 1 / 1 | Operator | Resource suggestion |
| 27 | hauteblack.com | 17 | 2.8K | 1 / 11 / 1 / 2 | Lifestyle blog | Guest feature |
| 28 | suchscience.net | 17 | 3.2K | 1 / 1 / 5 / 15 | Science blog | Wildlife data citation |
| 29 | magelanci.com | 13 | 79.8K | 51 / 1 / 1 / 5 | Travel site | Resource suggestion |
| 30 | silverbackwildadventures.com | 12 | 3.6K | 123 / 2 / 7 / 1 | Operator (gorilla) | Gorilla permit guide |
| 31 | africawildsafaris.com | 11 | n/a | 10 / 3 / 2 / 1 | Operator | Resource suggestion |
| 32 | suemtravels.com | 11 | 424 | 1 / 2 / 2 / 5 | Travel blog | Blogger outreach |
| 33 | eddytours-safaris.com | 10 | 2.8K | 18 / 1 / 1 / 9 | Operator | Resource suggestion |
| 34 | grandesafari.com | 10 | n/a | 2 / 24 / 4 / 9 | Operator | Resource suggestion |
| 35 | kambucampers.com | 10 | 731 | 14 / 4 / 1 / 1 | Operator (self-drive) | Self-drive guide |
| 36 | safarifind.com | 9 | 11.2K | 933 / 618 / 309 / 33 | Safari directory | Directory listing — check terms; `rel` attributes |
| 37 | meruslopestours.com | 8 | 3.8K | 44 / 3 / 7 / 10 | Operator | Resource suggestion |
| 38 | travelafrica.guide | 7 | n/a | 26 / 8 / 14 / 74 | Guide site | Resource suggestion |
| 39 | lametayel.co.il | 56 | 598K | 12 / 14 / 2 / 2 | Travel community (Hebrew) | Low fit (language) |
| 40 | losviajeros.com | 38 | 272K | 3 / 11 / 3 / 2 | Travel forum (Spanish) | Low fit (language; UGC) |

SB = safaribookings, G2A = go2africa, DA = discoverafrica, AG = africageographic. 100 rows
read; 88 saved to `backlink-gap.csv` (12 spam-pattern domains whose rows did not parse cleanly
were dropped).

**Reading this list honestly:** the high-authority domains (1–8) link to competitors through
journalism, not outreach requests; realistic routes are journalist-request platforms and
original data. The most winnable links are rows 9–38 (resource pages, travel blogs and
operators) using the citable assets in the off-page plan.

## 11. 90-day order of work

**Weeks 1–2 (to ~2 Dec)**
1. Re-angle the overlapping safari pages (section 9) — titles, H1s, intros and internal links
   only; no new pages yet.
2. Refresh the existing pages that already match measured demand: best-time-to-visit-okavango-delta
   (320/13), best-time-to-visit-masai-mara (590/18), best-time-to-visit-kruger (320/19),
   best-time-to-visit-serengeti (480/22), best-time-to-visit-morocco (4.4K/16),
   africa-safari-cost-by-destination (1.6K/20), best-beaches-africa-ranked (1.6K/20).
3. Owner decisions on the off-page plan (journalist platforms, outreach time budget).

**Weeks 3–6**
4. Publish briefs 1–4 (South Africa, Kenya, Kruger, Masai Mara) and 5 (tipping): one pillar
   country guide per week plus one planning page.
5. Build the citable cost and permit tables; start resource-page outreach (section 10,
   rows 9–38) pointing at existing guides.
6. 9 Dec: re-baseline Search Console (4 weeks of data) and re-check Semrush positions.

**Weeks 7–10**
7. Briefs 6–9 (Luxury, Rwanda, Uganda, Tanzania). Merge the startups/tech hubs pair.
8. Journalist-request answers weekly (rows 1–8 targets are only realistic this way).

**Weeks 11–13**
9. Briefs 10–15 (Photo safaris, Victoria Falls, Insurance, Zanzibar, Namibia, Honeymoon).
10. Monthly review: Search Console clicks by cluster, Semrush keyword count and positions,
    new referring domains. Re-run Keyword Gap and Backlink Gap only if a paid plan is active.

All publication still goes through the CMS review gate and the AGENTS.md editorial rules
(primary sources, dated prices, no invented figures).
