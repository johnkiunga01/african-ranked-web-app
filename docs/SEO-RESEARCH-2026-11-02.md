# First live OpenSEO research baseline

On 2 November 2026 the application's authenticated OpenSEO MCP client retrieved keyword metrics for 13 candidate queries, top-10 Google SERPs for those queries, and 144 related ideas from the `african safari` seed. Project context and saved keywords were checked first to avoid duplicate research. The project had no saved keywords for this corpus. No credits or subscriptions were purchased. These research tools consume existing provider credits; the returned metadata did not report credits charged/remaining, so this report does not invent a cost total.

The market was explicitly **United States / English / desktop (DataForSEO location 2840)**, overriding the project's Kenya default. This is a benchmark for the global English audience, not a worldwide search-volume measurement. Search volume, difficulty and intent were returned for 11 candidates; Big Five parks and Great Migration timing had no reported metric rows. Unknown values remain blank. The keyword report's 0 difficulty values are provider estimates, not proof that ranking will be easy.

The normalized query-to-existing-URL map is [openseo-2026-11-02.csv](../content/research/openseo-2026-11-02.csv). Full responses remain in ignored private storage. All 13 existing review articles now have actual keyword/SERP briefs in the CMS; editorial bodies, approval and publication pointers were not changed by attaching them.

## What the evidence suggests

| Existing topic | Reported monthly searches | Reported KD | Editorial implication to validate |
|---|---:|---:|---|
| Best time for African safari | 1,600 | 0 | Improve the existing month-by-month article; add region/species/season trade-offs with sources rather than create a competing URL. |
| Best beaches Africa | 1,300 | 0 | Build a useful comparison of access, season, activity and practical limitations, with dated primary-source checks. |
| Best safari destinations Africa | 880 | 6 | Repair the promised top-ten completeness and explain ranking criteria before adding unsupported destinations. |
| Safari packing list | 480 | 0 | Provider intent is transactional; test a useful checklist and contextual affiliate placements only when real partnerships and disclosures exist. |
| Cheap safari Africa | 390 | 15 | Provider intent is commercial; research dated costs, inclusions and trade-offs. CPC does not predict affiliate earnings. |
| Gorilla trekking Africa | 320 | 24 | Verify official permits, restrictions and operator/source dates; do not invent availability or firsthand experience. |
| African startups | 70 | 5 | Validate startup status and selection criteria with dated primary sources. Expand only after factual evidence exists. |
| Where to see rhinos in Africa | 10 | 40 | Low reported exact-query demand and higher KD warrant secondary-query research before making this a volume priority. |

These are editorial hypotheses from the returned data, not ranking/traffic/revenue forecasts. Candidate `best african food` produced a US restaurant result, exposing possible intent ambiguity; research a more precise cuisine-comparison query before treating it as the final target. The musicians SERP mixes streaming/community/editorial content; the article must define what "influential" means and support that methodology.

## Competitive evidence limits

Some results identify direct domains, including SafariBookings, National Geographic, Discover Africa, andBeyond, TourRadar and Extraordinary Journeys for relevant queries. Many other URLs are opaque `google.com/goto` redirects. Those do not establish the actual competitor domain. Only provider titles/snippets/organic positions were collected; a full competitor-page, backlink or content-gap audit has not been completed. No competitor page text was copied into articles.

## Application workflow

Articles → select records → Research & optimise with Gemini → confirm → background progress. Missing briefs trigger OpenSEO keyword metrics and a top-10 SERP lookup. Recent briefs are reused for 30 days, with article-scope checks. Gemini receives the actual brief, its national market/date, nullable metrics, SERP observations and existing article text; the researched target query is preserved. Results are validated search metadata in a new draft. The full article body is not expanded by this metadata action.

GPT drafting now attaches the same stored research, source notes and matching Search Console export where available; a current brief is required. That separate endpoint requires an OpenAI key. It must not claim snippets verify article facts. Body expansion, source verification and editorial acceptance remain separate work.

A live structured-generation probe using the actual safari packing article and its OpenSEO brief passed with `gemini-3.5-flash-lite` (50-character search title and 145-character description). It changed no editorial revision or publication pointer. The previous generic error is now split into usable model/access/quota/response-limit messages, without upstream content or keys being exposed.
