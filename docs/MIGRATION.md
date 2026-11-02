# Migration decisions and acceptance checks

## Implemented

- Replaced the Vite React shell with Laravel 12 / Blade using the existing Git checkout.
- Retained all six article records, their bylines/dates/FAQs/body blocks, six category definitions, eight regional definitions, and existing URL patterns. JSON replaces TypeScript as the catalog format.
- Preserved the source design vocabulary and page hierarchy with Blade partials and Tailwind. Navigation now uses crawlable anchors; region selection uses the URL without third-party IP lookup.
- Replaced Framer Motion's React dependency with Motion's JavaScript API. Added an original, local Lottie envelope with lazy loading and reduced-motion/static fallbacks.
- Replaced public Supabase newsletter insertion with a Laravel POST and a private local table. Existing remote data is untouched; no live Supabase database migration or import is performed.
- Added initial-HTML metadata, social cards, escaped JSON-LD, real 404s, generated XML sitemaps/RSS, robots.txt, and experimental llms.txt. Discovery paths formerly served from static files are now Laravel routes.
- Removed fabricated social/profile/search-action URLs and placeholder footer links. Removed unverified subscriber-count promotion from the newsletter.
- Added repository SEO/AEO/GEO skills and reviewer task briefs. They are editable guidance; no recurring autonomous processes, external accounts, or paid services are provisioned.

## Canonical policy

Regional home/category pages provide localized edition context and article selection. They use self-canonicals and reciprocal alternates where the category has content. Empty categories are noindex. Regional article text is identical to its global counterpart, so it canonicalizes to the global article and has no hreflang. Sitemaps list canonical, indexable URLs only. `/global` aliases redirect to their global counterparts; malformed paths return 404.

## Deliberate limitations

The file-based article catalog is suitable for preserving and developing the existing site. An authenticated publishing CMS, new article generation, analytics, monetization, newsletter delivery/double opt-in/unsubscribe, legal/contact pages, and production hosting are separate work. No live service is changed by local development.

Inherited editorial text remains unverified. Top-N headings may not enumerate the promised number of items, statistics need primary-source checks, attributed quotes need verification, and author identities/image rights need confirmation. Do not present these records as verified reporting solely because the code migration passes tests.

## Validation

`PublishingTest` checks every edition, every article in every edition, every category in every edition, initial-HTML metadata, global canonical policy, FAQ JSON-LD parity, heading anchors, 404s, legacy redirects, XML discovery, and that sitemap URLs resolve to indexable pages. `NewsletterTest` checks validation, consent, normalized persistence, deduplication, the honeypot, throttling, and the absence of public subscriber endpoints. PHP tests use an in-memory database and fake addresses.

The frontend production build and JavaScript syntax check must pass. Format PHP using Pint. Browser checks should cover desktop/mobile layout, mobile navigation, edition changes, article sharing/fallback, Lottie lazy loading, reduced motion, no-JavaScript reading, and form validation. See the task handoff for checks actually executed; this document alone is not a test result.

## Verified in this cloud instance

- Locked installation was rerun successfully with the saved setup script without replacing the app key or database.
- Production Vite build, JavaScript syntax check, Composer validation, Pint, route caching, and Blade caching passed.
- PHP feature suite: 11 tests passed, 1,381 assertions.
- Chromium smoke checks passed for desktop/mobile navigation and overflow, regional links, article anchors/canonicals, Lottie, reduced motion, no-JavaScript article reading, and local newsletter signup. No JavaScript runtime errors were observed.
- Live HTTP requests returned 200 for the rendered homepage and 419 for a newsletter POST without CSRF. The browser test subscription was removed afterward.
- Exact data equality was checked against the original Git versions of all articles, categories, and regions.
- Remote Pexels photos were blocked by this running environment's network policy; local image fallbacks worked. The environment draft now includes the required photo/font domains and GitHub's archive API. Those draft additions require review/save and environment publication before they can be relied on. No external photo access, production performance score, deployment, or search ranking result is claimed.
