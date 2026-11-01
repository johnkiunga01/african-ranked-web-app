#!/usr/bin/env node
// Generates per-region sitemap XML files with hreflang alternates.
// Run: node scripts/generate-sitemaps.mjs

import { writeFileSync, mkdirSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT_DIR = join(__dirname, '..', 'public', 'sitemaps');

const SITE_URL = 'https://africanranked.com';

const REGIONS = [
  { code: 'global', locale: 'en', path: '' },
  { code: 'us', locale: 'en-US', path: '/us' },
  { code: 'uk', locale: 'en-GB', path: '/uk' },
  { code: 'au', locale: 'en-AU', path: '/au' },
  { code: 'ca', locale: 'en-CA', path: '/ca' },
  { code: 'ng', locale: 'en-NG', path: '/ng' },
  { code: 'za', locale: 'en-ZA', path: '/za' },
  { code: 'ke', locale: 'en-KE', path: '/ke' },
];

const CATEGORIES = [
  { slug: 'travel', lastmod: '2026-10-27' },
  { slug: 'food', lastmod: '2026-10-27' },
  { slug: 'music', lastmod: '2026-10-27' },
  { slug: 'business', lastmod: '2026-10-27' },
  { slug: 'tech', lastmod: '2026-10-27' },
  { slug: 'wildlife', lastmod: '2026-10-27' },
];

const ARTICLES = [
  { slug: 'top-10-safari-destinations-africa-2026', lastmod: '2026-10-20' },
  { slug: 'best-african-cuisines-ranked-2026', lastmod: '2026-10-18' },
  { slug: 'top-african-startups-2026', lastmod: '2026-10-25' },
  { slug: 'best-beaches-africa-ranked', lastmod: '2026-10-12' },
  { slug: 'top-african-musicians-2026', lastmod: '2026-10-24' },
  { slug: 'fastest-growing-tech-hubs-africa', lastmod: '2026-10-05' },
  { slug: 'big-five-parks-ranked-where-to-see-all-five', lastmod: '2026-11-01' },
  { slug: 'best-gorilla-trekking-africa-ranked', lastmod: '2026-11-01' },
  { slug: 'great-migration-guide-best-months-viewing-spots', lastmod: '2026-11-01' },
  { slug: 'affordable-safari-parks-ranked', lastmod: '2026-11-01' },
];

// pagePath is the page-specific path WITHOUT any region prefix
// (e.g., '', '/categories/travel', '/articles/slug')
function hreflangAlternates(pagePath) {
  const lines = REGIONS.map((r) => {
    const url = `${SITE_URL}${r.path}${pagePath}`;
    return `      <xhtml:link rel="alternate" hreflang="${r.locale}" href="${url}"/>`;
  });
  lines.push(
    `      <xhtml:link rel="alternate" hreflang="x-default" href="${SITE_URL}${pagePath}"/>`
  );
  return lines.join('\n');
}

function urlEntry(loc, lastmod, pagePath, priority) {
  const alternates = hreflangAlternates(pagePath);
  return `  <url>
    <loc>${loc}</loc>
    <lastmod>${lastmod}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>${priority}</priority>
${alternates}
  </url>`;
}

for (const region of REGIONS) {
  const entries = [];

  // Home page
  entries.push(urlEntry(
    `${SITE_URL}${region.path}`,
    '2026-10-27',
    '',
    '1.0'
  ));

  // Strategy page
  entries.push(urlEntry(
    `${SITE_URL}${region.path}/strategy`,
    '2026-10-27',
    '/strategy',
    '0.6'
  ));

  // Category pages
  for (const cat of CATEGORIES) {
    entries.push(urlEntry(
      `${SITE_URL}${region.path}/categories/${cat.slug}`,
      cat.lastmod,
      `/categories/${cat.slug}`,
      '0.8'
    ));
  }

  // Article pages
  for (const article of ARTICLES) {
    entries.push(urlEntry(
      `${SITE_URL}${region.path}/articles/${article.slug}`,
      article.lastmod,
      `/articles/${article.slug}`,
      '0.9'
    ));
  }

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
${entries.join('\n')}
</urlset>`;

  mkdirSync(OUT_DIR, { recursive: true });
  writeFileSync(join(OUT_DIR, `${region.code}.xml`), xml, 'utf-8');
  console.log(`Generated sitemaps/${region.code}.xml (${entries.length} URLs)`);
}

console.log('All sitemaps generated.');
