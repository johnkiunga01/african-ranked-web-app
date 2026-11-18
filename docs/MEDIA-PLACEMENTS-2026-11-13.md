# Pillar media placement queue — 13 November 2026

All four SVG assets are original AI-assisted explanatory diagrams. Their visible labels identify them as diagrams; they make no claim to depict real locations, photographed people or measured adoption rates. Captions are reader-visible, and each needs a descriptive alternative text. No new external photography licence is claimed.

| Article | Asset path | Suggested position | Accessible summary |
|---|---|---|---|
| Safari destinations pillar | `/editorial-art/top-10-safari-destinations-africa-2026/decision-guide.svg` | After the introduction to ranking criteria | Compare experience priorities, travel timing and practicalities before interpreting a destination's position. |
| African music genres hub | `/editorial-art/african-music-genres-explained/decision-guide.svg` | Beside the listening framework | Compare rhythm, vocal phrasing and instrumentation; this is a listening guide, not a map of genre origins. |
| Mobile money supporting guide | `/editorial-art/african-countries-by-mobile-money-adoption/decision-guide.svg` | At the definition of adoption metrics | Registered accounts, individual people and transactions answer different measurement questions. |
| Gorilla permit supporting guide | `/editorial-art/gorilla-trekking-permit-cost-how-to-book/decision-guide.svg` | After the permit-versus-whole-trip budget explanation | Rwanda's published permit rate is not the whole-trip price; also compare travel, accommodation, extras and booking conditions. |

The two fresh music/mobile-money drafts can receive private media revisions through the restricted integration. The public safari and gorilla articles require the normal authenticated CMS editing flow, which can create private revisions while preserving the published version. The current integration intentionally rejects published articles; it has not been widened to bypass this boundary.

External media candidates remain unverified: Pexels images and licence pages returned HTTP 403, and YouTube metadata was blocked. The UWA homepage itself links an embedded video with ID `3XZfbd8yugI`; inspect its title, creator, current contents and embedding availability before using it. A source-page embed is useful provenance, but is not evidence that the entire video was watched or verified. No arbitrary artist performance or stock clip was added just to increase media count.

To add a reviewed video in the CMS body, use `[Video: A descriptive title](https://www.youtube.com/watch?v=VIDEO_ID)` on its own line. Only a valid 11-character ID on an exact allowed YouTube host is accepted; other links remain ordinary text/links. The public article and private preview use the same click-to-load component. It sends no YouTube request before the reader activates it and includes a direct external link for readers without JavaScript.

Example infographic Markdown, with the current HTTPS origin:

```markdown
![Compare rhythm, vocal phrasing and instrumentation](https://africanranked.com/editorial-art/african-music-genres-explained/decision-guide.svg "Original African Ranked AI-assisted infographic: a listening framework, not a map of genre origins.")
```

Adding media does not verify the surrounding manuscript, satisfy all publication requirements, or approve publication. Preserve the author's substantive copy, fact dates, source records and existing FAQ answers when saving a media revision.
