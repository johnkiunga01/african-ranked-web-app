import { useEffect } from 'react';
import type { RegionCode } from '@/types';
import {
  buildHreflangEntries,
  buildCanonicalUrl,
  type MetaTags,
} from '@/utils/seo';

interface SeoHeadProps {
  region: RegionCode;
  path: string;
  meta: MetaTags;
  schemas: object[];
}

function upsertMeta(attr: 'name' | 'property', key: string, content: string) {
  let el = document.head.querySelector<HTMLMetaElement>(`meta[${attr}="${key}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute(attr, key);
    document.head.appendChild(el);
  }
  el.setAttribute('content', content);
}

function upsertLink(rel: string, href: string, hreflang?: string) {
  const selector = hreflang
    ? `link[rel="${rel}"][hreflang="${hreflang}"]`
    : `link[rel="${rel}"]:not([hreflang])`;
  let el = document.head.querySelector<HTMLLinkElement>(selector);
  if (!el) {
    el = document.createElement('link');
    el.setAttribute('rel', rel);
    if (hreflang) el.setAttribute('hreflang', hreflang);
    document.head.appendChild(el);
  }
  el.setAttribute('href', href);
}

function upsertJsonLd(id: string, data: object) {
  let el = document.getElementById(id) as HTMLScriptElement | null;
  if (!el) {
    el = document.createElement('script');
    el.id = id;
    el.type = 'application/ld+json';
    document.head.appendChild(el);
  }
  el.textContent = JSON.stringify(data);
}

function removeJsonLd(id: string) {
  const el = document.getElementById(id);
  if (el) el.remove();
}

const JSONLD_IDS = [
  'ar-schema-organization',
  'ar-schema-website',
  'ar-schema-article',
  'ar-schema-faq',
  'ar-schema-breadcrumb',
  'ar-schema-itemlist',
];

export function SeoHead({ region, path, meta, schemas }: SeoHeadProps) {
  useEffect(() => {
    document.title = meta.title;

    upsertMeta('name', 'description', meta.description);
    upsertMeta('name', 'keywords', meta.keywords.join(', '));

    // Open Graph
    upsertMeta('property', 'og:title', meta.ogTitle);
    upsertMeta('property', 'og:description', meta.ogDescription);
    upsertMeta('property', 'og:url', meta.ogUrl);
    upsertMeta('property', 'og:image', meta.ogImage);
    upsertMeta('property', 'og:type', 'article');
    upsertMeta('property', 'og:site_name', 'African Ranked');
    upsertMeta('property', 'og:locale', region === 'global' ? 'en' : region);

    // Twitter Cards
    upsertMeta('name', 'twitter:card', 'summary_large_image');
    upsertMeta('name', 'twitter:title', meta.ogTitle);
    upsertMeta('name', 'twitter:description', meta.ogDescription);
    upsertMeta('name', 'twitter:image', meta.ogImage);
    upsertMeta('name', 'twitter:site', '@africanranked');

    // Canonical
    upsertLink('canonical', buildCanonicalUrl(region, path));

    // Hreflang — clean old ones first, then insert fresh
    document.head
      .querySelectorAll('link[rel="alternate"][hreflang]')
      .forEach((el) => el.remove());
    const hreflangs = buildHreflangEntries(path);
    hreflangs.forEach((entry) => {
      const link = document.createElement('link');
      link.setAttribute('rel', 'alternate');
      link.setAttribute('hreflang', entry.hreflang);
      link.setAttribute('href', entry.href);
      document.head.appendChild(link);
    });

    // Schemas: remove all existing, then add new ones
    JSONLD_IDS.forEach(removeJsonLd);
    const activeIds = new Set<string>();
    schemas.forEach((schema, i) => {
      const id = JSONLD_IDS[i];
      if (id) {
        upsertJsonLd(id, schema);
        activeIds.add(id);
      }
    });
    JSONLD_IDS.filter((id) => !activeIds.has(id)).forEach(removeJsonLd);

    // Cleanup on unmount
    return () => {
      JSONLD_IDS.forEach(removeJsonLd);
      document.head
        .querySelectorAll('link[rel="alternate"][hreflang]')
        .forEach((el) => el.remove());
    };
  }, [region, path, meta, schemas]);

  return null;
}
