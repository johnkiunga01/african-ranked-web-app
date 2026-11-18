# African Ranked homepage hero artwork — 9 November 2026

The site owner approved a generated East African savannah illustration to replace the previous Pexels homepage background.

## Provenance and claims

- Origin: commissioned AI-generated image created in this conversation, not a documentary photograph.
- Visual subject: stylized umbrella acacia, sunrise, open savannah, and distant snowcapped mountain.
- Do **not** label the mountain as a verified photograph of Kilimanjaro or imply a documented geographic capture.
- Visible footer disclosure: **Hero artwork created with AI for African Ranked**. Existing separate category photograph credits remain unchanged.
- HTML alt text describes the actual depicted scene while acknowledging it is AI-generated.

## Self-hosted assets and responsive delivery

- Desktop: `public/images/african-ranked-savannah-hero-desktop.webp` (1600×900, WebP, approximately 77 KB).
- Mobile: `public/images/african-ranked-savannah-hero-mobile.webp` (800×1000, WebP, approximately 60 KB), cropped to retain the subject on narrow screens.
- A server-rendered `picture` element serves the mobile variant at viewport widths up to 639px, falling back to the desktop rendition at larger widths. The primary image is requested eagerly at high priority for LCP.
- `app/Support/Seo.php` provides the desktop image as the homepage's Open Graph and Twitter preview. Metadata points to the configured canonical domain.
- Hero text is HTML, not baked into the image; the responsive gradient preserves high-contrast readability.
- The image is committed to this repository and served by the same website; there is no runtime dependency on a source photography or third-party shopping CDN.

## Scope

No article content, publication state, catalog/category photography, domain, DNS, analytics, search indexing setting, CMS workflow, or database schema was changed for this hero update.

Recheck desktop/mobile visual cropping and real-world LCP after the custom domain is configured.