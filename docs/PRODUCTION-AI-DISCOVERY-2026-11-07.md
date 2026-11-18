# African Ranked — production runtime with indexing deferred

Owner decision: 7 November 2026. Railway's existing **production** environment is the live operating environment, not a temporary review environment. Its current `*.up.railway.app` domain remains the public HTTPS origin until the owner supplies a custom domain, expected the following week.

## Runtime and indexing are independent

For all three Laravel services (web, worker, scheduler), use:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_PREVIEW=false
APP_REVIEW=false
SEO_INDEXING_ENABLED=false
APP_URL=https://african-ranked-web-app-production.up.railway.app
```

- The public site, public article pages, XML sitemap, RSS feed and `/llms.txt` can be fetched by readers, LLM assistants and compliant crawlers.
- `SEO_INDEXING_ENABLED=false` adds `X-Robots-Tag: noindex, follow, noarchive` and an equivalent public HTML robots meta tag, **without suppressing canonical URLs or server-side JSON-LD**.
- `robots.txt` permits public crawling so well-behaved search engines can observe and honor `noindex`. It explicitly disallows staff and newsletter paths. **Robots rules are not access controls**; staff routes retain Laravel authentication/policy checks, noindex/nofollow and no-store.
- The prior `APP_PREVIEW` and `APP_REVIEW` mode is reserved for a genuine temporary review environment and continues to disallow all crawlers.
- Production mode enables normal HTTPS security handling and trusted-host checks. Do not regenerate `APP_KEY`, alter the database or migrate editorial records to enact this configuration.
- Preserve the current `SESSION_COOKIE` on the active deployment to avoid unnecessarily invalidating the owner's sessions; a future cookie rename is a separate planned change.

## AI assistant access

- OpenAI `ChatGPT-User` and `OAI-SearchBot`, Anthropic `Claude-User` and `Claude-SearchBot`, Perplexity user agents and other compliant AI tools can access the same **public** article paths as other readers under the general allow rule.
- `/llms.txt` is dynamically generated from **published** CMS revisions (never drafts); it links to the editorial approach, published canonical article URLs, feed and sitemap.
- The general allow rule also permits model-development crawlers such as GPTBot and ClaudeBot. If the owner later wants **retrieval without training reuse**, add separate robots rules for those training-specific user agents; their purposes differ from user-initiated fetchers.
- The `llms.txt` format is voluntary and experimental; availability does not ensure search rankings, AI citations, training ingestion or immediate discovery.

## Custom-domain search release (later, explicit owner approval required)

1. Add and verify the custom domain in Railway, including TLS/HTTPS and allowed host handling; decide whether the Railway address will redirect to the canonical custom hostname.
2. Update `APP_URL` consistently on web, worker and scheduler to the exact approved HTTPS domain. Test redirects, canonical links, OG metadata, XML sitemap, RSS and `/llms.txt`; prevent duplicate canonical domains.
3. Validate real published content, editorial source/media rights, security/backup/restore, mobile rendering and analytics/Search Console ownership. Keep drafts and staff paths private.
4. **Only after explicit approval**, set `SEO_INDEXING_ENABLED=true` for the public web service, confirm the resulting robots meta tags and headers are indexable, and submit the new canonical sitemap. Do not set an automatic date or flip this variable early.
5. Confirm old-host redirects/canonicalization and monitor crawl coverage on the custom hostname.

This is a **production-runtime change, not a search indexing launch**.