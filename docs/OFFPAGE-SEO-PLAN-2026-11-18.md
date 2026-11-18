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
- **Not yet known:** referring domains, current rankings and query demand. Fill these in from
  Search Console (Links report and the SEO page in `/staff`) before choosing targets. No numbers
  in this plan are invented.

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

Record each outreach attempt (site, page pitched, date, outcome) in a shared sheet so
follow-ups are not duplicated.

## Decisions needed from the owner

1. Approve the safari cluster as the first focus.
2. Approve creating accounts on one or two journalist request platforms.
3. Confirm which social profiles to create and who will maintain them.
4. Set a monthly time budget for outreach (no paid link placements).
