---
name: technical-seo
description: Implement or review African Ranked server-rendered metadata, canonicalization, crawlability, structured data, sitemaps, and performance.
---

# Technical SEO

1. Read AGENTS.md, `app/Support/Seo.php`, `app/Support/Catalog.php`, `app/Http/Controllers/DiscoveryController.php`, and relevant Blade views before making changes.
2. Inspect the actual initial HTML and HTTP status for a global home, regional home, populated category, empty category, global article, regional article, and unknown route. Do not treat a JavaScript-rendered DOM alone as evidence of crawlability.
3. Ensure one descriptive H1, descriptive title and description, real anchors, accurate dates, image dimensions, appropriate image priority, accessible navigation, and a valid canonical URL from APP_URL.
4. Identical regional articles canonicalize to the global article. Do not mix canonical-to-global article pages with self-referencing hreflang. Check home/category alternate reciprocity and omit empty categories from indexing. No user-agent cloaking or forced geolocation redirects.
5. Parse JSON-LD as JSON and XML endpoints as XML. Verify Article, BreadcrumbList, FAQPage, and ItemList data against visible content. Do not imply FAQ rich-result eligibility for this publisher. Only describe real features and identities.
6. Crawl sitemap locations: every URL must return 200, be indexable, and match its canonical. Missing URLs return 404; omit invented lastmod dates and redirecting URLs. Verify robots.txt and RSS use the configured production origin.
7. Run `php artisan test`, `npm run build`, `npm run check`, and `vendor/bin/pint --test` for relevant changes. Inspect browser console, mobile overflow, reduced motion, and network failures. Measure Core Web Vitals with real tools when available; never invent scores.
8. Report concrete findings with paths and validation evidence. Separate implementation status, editorial quality, Search Console observations, and ranking outcomes. No guarantees of indexing, rankings, traffic, or ROI.
