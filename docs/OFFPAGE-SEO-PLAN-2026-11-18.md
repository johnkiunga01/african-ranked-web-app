# Off-page SEO plan — 18 November 2026

Status: proposal for the owner. Nothing here has been sent, submitted or purchased.
Outreach, paid placements and new accounts need the owner's go-ahead (see AGENTS.md).

## Starting point (verified 18 November 2026)

- 33 published articles in the sitemap. 19 are safari and wildlife guides; the rest cover
  tech and startups (3), culture and music (5), food (1), beaches (1), Morocco (1) and the
  economy (1).
- Technical foundations are in place: HTTPS/HSTS, canonicals, sitemap, RSS feed, `/llms.txt`,
  Article/FAQ/Breadcrumb/ItemList structured data and a publisher logo (added today).
- Country editions (`/us`, `/uk`, …) are `noindex, follow` by the 13 November owner decision,
  so all link equity should point at global URLs (`/articles/{slug}`, `/categories/{slug}`).
- No social or business profiles are declared in structured data. None should be added until
  the accounts actually exist.
- **Rankings and demand (Semrush, US, data date 17 Nov 2026):** 1 ranking keyword
  (`biggest economy of africa`, position 71, volume 720, KD 46), estimated traffic 0. Keyword
  demand and gaps are in [KEYWORD-RESEARCH-2026-11-18.md](KEYWORD-RESEARCH-2026-11-18.md).
- **Backlink gap (Semrush Backlink Gap, read 18 Nov 2026):** 27,592 referring domains link to at
  least one of safaribookings.com, go2africa.com, discoverafrica.com or africageographic.com and
  not to africanranked.com. The targets below come from that report. Referring domains for
  africanranked.com itself should still be checked in Search Console → Links. No numbers in this
  plan are invented.

## Principles

1. Earn links to pages that deserve them; never buy links, swap links at scale or use private
   blog networks. Those risk a Google manual action.
2. Concentrate on one topical cluster first. Safari is the strongest: it has the most pages and
   the most practical, citable content.
3. Every pitch must point to something a journalist or site owner can use: data, a clear answer
   or a resource their readers need.
4. Mark sponsored or affiliate links `rel="sponsored"` and user-submitted links `rel="ugc"`.

## Priority 1 — Make citable assets (weeks 1–3)

Pick the pages that already hold facts other sites cite, and make them easy to reference:

| Asset | Why it earns links | Work needed |
|---|---|---|
| `/articles/africa-safari-cost-by-destination` | Cost data is widely quoted by travel writers | Add a dated summary table, a source list and a "last checked" date |
| `/articles/gorilla-trekking-permit-cost-how-to-book` | Permit prices change and people look for a current figure | Confirm current prices from the official park authorities and show the check date |
| `/articles/best-time-to-visit-african-safari-by-month` | Month-by-month answers are reusable | Add an embeddable month table with a credit line |
| `/articles/great-migration-guide-best-months-viewing-spots` | Seasonal news hooks every year | Refresh before each season; pitch it 4–6 weeks ahead |
| `/articles/largest-economies-in-africa` | Business and education sites cite rankings | Cite IMF/World Bank figures with the edition used |

Each refresh must verify facts against primary sources (AGENTS.md editorial rules); no figures
are to be estimated.

## Priority 2 — Outreach channels (weeks 3–12)

1. **Journalist request platforms** (e.g. Qwoted, Featured, Source of Sources): answer
   travel and Africa questions with a short expert answer and a link to the relevant guide.
   Owner to create the account under info@africanranked.com.
2. **Resource and "useful links" pages:** safari operators, travel bloggers, university
   African-studies departments and tour-guide associations that list planning resources.
   Pitch the single most relevant guide, not the home page.
3. **Unlinked mentions:** search for "African Ranked" and article titles; ask sites that
   mention the brand without a link to add one.
4. **Broken-link replacement:** find dead links on safari-planning pages that pointed to
   retired guides covering the same topic, and offer the matching African Ranked page.
5. **Tourism boards and conservation bodies:** offer accurate coverage of parks and seasons;
   these links are rare but strong. Never imply an endorsement that was not given.
6. **Guest expertise:** a small number of bylined pieces on established travel publications,
   linking back to one supporting guide each. Quality over volume.

## Backlink targets (Semrush Backlink Gap, 18 November 2026)

Every domain below links to **all four** competitors (`Matches 4/5`) and to africanranked.com
**zero** times. AS = Semrush Authority Score; visits = Semrush monthly visits estimate. "Page type"
is a classification of the site, not a check of the individual linking page; confirm the exact
linking page in Semrush (expand the row) before pitching. The full list of 40, with link counts
per competitor, is in section 10 of the keyword research doc; all rows read are in
`docs/keyword-research-2026-11-18/backlink-gap.csv`.

### Tier A — digital PR and journalist requests (links come from reporting, not requests)

| Domain | AS | Visits | Route |
|---|---|---|---|
| nytimes.com | 100 | 294M | Expert answers via journalist platforms only |
| dailymail.co.uk | 95 | 12.7M | Wildlife/travel data stories |
| businessinsider.com | 85 | 42.4M | Safari cost data |
| cntraveler.com | 77 | 3.3M | Expert quotes |
| thetimes.com | 77 | 18.6M | Expert quotes |
| travelandleisure.com | 73 | 11M | Expert quotes |
| thesouthafrican.com | 68 | 4.1M | Kruger / South Africa safari stories |
| timeslive.co.za | 63 | 2.6M | South Africa travel stories |
| mongabay.com | 56 | 4.7M | Conservation data (rhinos, gorillas) |

### Tier B — travel media and resource pages (pitch a specific guide)

| Domain | AS | Visits | Pitch |
|---|---|---|---|
| sahistory.org.za | 58 | 727K | Resource page (history/culture guides) |
| thetravel.com | 51 | 1.6M | Ranked safari guides |
| bizcommunity.com | 47 | 450K | Press release on original cost/permit data |
| 2oceansvibe.com | 42 | 150K | Cape Town + safari |
| getaway.co.za | 36 | 146K | Guest expertise / data |
| africafreak.com | 35 | 18K | Safari planning resources |
| animalsaroundtheglobe.com | 32 | 335K | Wildlife where-to-see guides |
| roxannereid.co.za | 32 | 5.9K | Blogger outreach |
| safarisafricana.com | 31 | 8.2K | Safari planning resources |
| keeptheplanet.org | 27 | 3.9K | Resource lists |
| we3travel.com | 21 | 45.4K | Family safari guide |
| animalko.com | 20 | 10.1K | Wildlife guides |
| hauteblack.com | 17 | 2.8K | Guest feature |
| suchscience.net | 17 | 3.2K | Wildlife data citation |
| magelanci.com | 13 | 79.8K | Resource suggestion |
| suemtravels.com | 11 | 424 | Blogger outreach |
| travelafrica.guide | 7 | n/a | Resource suggestion |

### Tier C — operators and lodges (offer a guide their guests can use)

| Domain | AS | Visits | Pitch |
|---|---|---|---|
| krugergatehotel.com | 27 | 17.8K | Kruger safari / best-time guide |
| rexclarkeadventures.com | 24 | 1.4K | Planning resources |
| dumaexplorer.com | 19 | 3.8K | Planning resources |
| nativeafricatours.com | 18 | 899 | Planning resources |
| silverbackwildadventures.com | 12 | 3.6K | Gorilla permit guide |
| africawildsafaris.com | 11 | n/a | Planning resources |
| eddytours-safaris.com | 10 | 2.8K | Planning resources |
| grandesafari.com | 10 | n/a | Planning resources |
| kambucampers.com | 10 | 731 | Self-drive guide |
| meruslopestours.com | 8 | 3.8K | Planning resources |
| safarifind.com | 9 | 11.2K | Directory: read its listing terms first; expect `nofollow`/`ugc` |

Operators are also commercial partners in waiting. Any reciprocal or paid arrangement must be
disclosed and marked `rel="sponsored"`; do not trade links.

**Not targeted:** search engines, Wikipedia and its clones, UGC platforms (Medium, Substack,
Beehiiv, Steemit), SEO/spam directories, adult and unrelated sites, a hunting forum (off-brand),
and two foreign-language travel communities (lametayel.co.il, losviajeros.com) until there is
content in those languages.

## Priority 3 — Brand signals (ongoing)

- Create consistent profiles where readers already are (e.g. Pinterest for safari imagery,
  LinkedIn for the business/tech coverage, YouTube if video is planned). Once an account
  exists and is actively maintained, add its URL to the Organization `sameAs` list.
- Keep the About page, editorial approach and contact details current; they support trust
  signals that reviewers and journalists check.
- Use the RSS feed and newsletter (once its privacy gates pass) to get repeat readers.

## Measurement

Review monthly in Search Console and the `/staff` SEO page:

- Referring domains and the pages they link to (Search Console → Links).
- Impressions, clicks and average position for the asset pages above.
- Queries in positions 11–20 (highlighted on the SEO page) as the next pages to support with
  links and internal links.
- Progress against the target tables above: which domains were pitched, and which now link.

Record each outreach attempt (site, page pitched, date, outcome) in a shared sheet so
follow-ups are not duplicated.

## Decisions needed from the owner

1. Approve the safari cluster as the first focus.
2. Approve creating accounts on one or two journalist request platforms.
3. Confirm which social profiles to create and who will maintain them.
4. Set a monthly time budget for outreach (no paid link placements).
5. Approve Tier B and C outreach from the target tables (personalised pitches drafted for
   review first; nothing sent without approval).
