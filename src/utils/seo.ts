import type { Region, RegionCode, Article, Breadcrumb } from '@/types';
import { REGIONS, ALL_REGIONS, SITE_URL } from '@/data/regions';
import { getCategory } from '@/data/categories';

export function regionPath(region: RegionCode, path = ''): string {
  const base = region === 'global' ? '' : `/${region}`;
  return `${base}${path}`;
}

export function regionUrl(region: RegionCode, path = ''): string {
  return `${SITE_URL}${regionPath(region, path)}`;
}

export interface HreflangEntry {
  hreflang: string;
  href: string;
}

export function buildHreflangEntries(path = ''): HreflangEntry[] {
  const entries: HreflangEntry[] = ALL_REGIONS.map((region) => ({
    hreflang: region.locale,
    href: regionUrl(region.code, path),
  }));

  // Add x-default pointing to global
  entries.push({
    hreflang: 'x-default',
    href: regionUrl('global', path),
  });

  return entries;
}

export function buildCanonicalUrl(region: RegionCode, path = ''): string {
  return regionUrl(region, path);
}

export interface MetaTags {
  title: string;
  description: string;
  ogTitle: string;
  ogDescription: string;
  ogUrl: string;
  ogImage: string;
  keywords: string[];
}

export function buildHomeMeta(region: Region): MetaTags {
  const regionName = region.code === 'global' ? 'Worldwide' : region.name;
  return {
    title: `African Ranked — Best of Africa ${regionName} | Top Rankings & Lists`,
    description: `Discover the best of Africa, ranked. ${region.tagline} Top destinations, food, music, business, and more — curated for ${regionName.toLowerCase()} readers.`,
    ogTitle: `African Ranked — The Best of Africa, Ranked (${regionName})`,
    ogDescription: `Top-ranked African destinations, food, music, startups, and more, curated for ${regionName.toLowerCase()} readers. Updated weekly.`,
    ogUrl: regionUrl(region.code),
    ogImage: `${SITE_URL}/og/african-ranked-${region.code}.png`,
    keywords: [
      'best of Africa',
      'Africa rankings',
      'top African destinations',
      'African food ranked',
      'African startups',
      'Afrobeats rankings',
      region.code !== 'global' ? `best of Africa ${region.name}` : 'best of Africa worldwide',
    ],
  };
}

export function buildArticleMeta(region: Region, article: Article): MetaTags {
  const regionName = region.code === 'global' ? 'Worldwide' : region.name;
  return {
    title: `${article.title} | African Ranked ${regionName}`,
    description: article.excerpt,
    ogTitle: article.title,
    ogDescription: article.excerpt,
    ogUrl: regionUrl(region.code, `/articles/${article.slug}`),
    ogImage: article.image,
    keywords: [
      article.category,
      article.title.toLowerCase().split(' ').slice(0, 5).join(' '),
      'Africa rankings',
      'African Ranked',
    ],
  };
}

export function buildCategoryMeta(region: Region, categorySlug: string): MetaTags {
  const category = getCategory(categorySlug);
  const regionName = region.code === 'global' ? 'Worldwide' : region.name;
  const title = category
    ? `${category.name} — Best of Africa ${regionName} | African Ranked`
    : `African Ranked ${regionName}`;
  const description = category?.description ?? region.tagline;
  return {
    title,
    description,
    ogTitle: title,
    ogDescription: description,
    ogUrl: regionUrl(region.code, `/categories/${categorySlug}`),
    ogImage: `${SITE_URL}/og/category-${categorySlug}.png`,
    keywords: category ? [category.name, 'Africa rankings', region.name] : ['Africa rankings'],
  };
}

// --- JSON-LD Schema builders ---

export function buildOrganizationSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: 'African Ranked',
    alternateName: 'AfricanRanked',
    url: SITE_URL,
    logo: `${SITE_URL}/logo.png`,
    description:
      'A global directory of the best of and ranked-based articles for everything about Africa.',
    foundingDate: '2025',
    sameAs: [
      'https://twitter.com/africanranked',
      'https://instagram.com/africanranked',
      'https://linkedin.com/company/africanranked',
      'https://youtube.com/@africanranked',
    ],
  };
}

export function buildWebSiteSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'African Ranked',
    url: SITE_URL,
    description: 'The definitive guide to everything ranked about Africa.',
    inLanguage: 'en',
    potentialAction: {
      '@type': 'SearchAction',
      target: `${SITE_URL}/search?q={search_term_string}`,
      'query-input': 'required name=search_term_string',
    },
  };
}

export function buildArticleSchema(region: Region, article: Article) {
  const category = getCategory(article.category);
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: article.title,
    description: article.excerpt,
    image: article.image,
    datePublished: article.datePublished,
    dateModified: article.dateModified,
    inLanguage: region.locale,
    author: {
      '@type': 'Person',
      name: article.author,
      jobTitle: article.authorTitle,
      worksFor: {
        '@type': 'Organization',
        name: 'African Ranked',
      },
    },
    publisher: {
      '@type': 'Organization',
      name: 'African Ranked',
      logo: {
        '@type': 'ImageObject',
        url: `${SITE_URL}/logo.png`,
        width: 600,
        height: 60,
      },
    },
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': regionUrl(region.code, `/articles/${article.slug}`),
    },
    keywords: article.category,
    articleSection: category?.name ?? article.category,
    wordCount: article.body
      .map((b) => ('text' in b ? b.text.split(' ').length : 0))
      .reduce((a, b) => a + b, 0),
  };
}

export function buildFaqSchema(article: Article) {
  if (!article.faqs || article.faqs.length === 0) return null;
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: article.faqs.map((f) => ({
      '@type': 'Question',
      name: f.question,
      acceptedAnswer: {
        '@type': 'Answer',
        text: f.answer,
      },
    })),
  };
}

export function buildBreadcrumbSchema(region: Region, breadcrumbs: Breadcrumb[]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: breadcrumbs.map((bc, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: bc.name,
      item: bc.url.startsWith('http') ? bc.url : regionUrl(region.code, bc.url),
    })),
  };
}

export function buildItemListSchema(region: Region, articles: Article[]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    itemListElement: articles.map((article, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: article.title,
      url: regionUrl(region.code, `/articles/${article.slug}`),
    })),
  };
}

export { REGIONS, ALL_REGIONS, SITE_URL };
