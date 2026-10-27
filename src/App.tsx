import { useState, useEffect, useCallback } from 'react';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { SeoHead } from '@/components/SeoHead';
import { HomePage } from '@/pages/HomePage';
import { ArticlePage } from '@/pages/ArticlePage';
import { CategoryPage } from '@/pages/CategoryPage';
import { StrategyPage } from '@/pages/StrategyPage';
import { NotFoundPage } from '@/pages/NotFoundPage';
import type { RegionCode, Breadcrumb } from '@/types';
import { getRegion, isRegionCode, SITE_URL } from '@/data/regions';
import { getArticle, getArticlesForRegion } from '@/data/articles';
import { getCategory } from '@/data/categories';
import { regionPath, regionUrl } from '@/utils/seo';
import {
  buildHomeMeta,
  buildArticleMeta,
  buildCategoryMeta,
  buildOrganizationSchema,
  buildWebSiteSchema,
  buildArticleSchema,
  buildFaqSchema,
  buildBreadcrumbSchema,
  buildItemListSchema,
} from '@/utils/seo';
import {
  detectRegion,
  getStoredRegion,
  setStoredRegion,
  getDetectedRegion,
  setDetectedRegion,
} from '@/utils/geo';

interface Route {
  region: RegionCode;
  type: 'home' | 'category' | 'article' | 'strategy' | 'notfound';
  param?: string;
}

function parsePath(pathname: string): Route {
  const segments = pathname.split('/').filter(Boolean);

  if (segments.length === 0) {
    return { region: 'global', type: 'home' };
  }

  const first = segments[0];

  if (isRegionCode(first)) {
    const region = first as RegionCode;
    const rest = segments.slice(1);

    if (rest.length === 0) return { region, type: 'home' };
    if (rest[0] === 'strategy') return { region, type: 'strategy' };
    if (rest[0] === 'categories' && rest[1])
      return { region, type: 'category', param: rest[1] };
    if (rest[0] === 'articles' && rest[1])
      return { region, type: 'article', param: rest[1] };

    return { region, type: 'notfound' };
  }

  // Non-region first segment: could be a bare category/article (global)
  if (first === 'strategy') return { region: 'global', type: 'strategy' };
  if (first === 'categories' && segments[1])
    return { region: 'global', type: 'category', param: segments[1] };
  if (first === 'articles' && segments[1])
    return { region: 'global', type: 'article', param: segments[1] };

  return { region: 'global', type: 'notfound' };
}

export default function App() {
  const [path, setPath] = useState(window.location.pathname);
  const [region, setRegion] = useState<RegionCode>('global');
  const [geoReady, setGeoReady] = useState(false);

  const route = parsePath(path);

  // Geo-detection on first load
  useEffect(() => {
    const stored = getStoredRegion();
    const detected = getDetectedRegion();

    if (stored) {
      setRegion(stored);
      setGeoReady(true);
      return;
    }

    if (detected) {
      setRegion(detected);
      setGeoReady(true);
      return;
    }

    // Run detection
    detectRegion().then((result) => {
      setDetectedRegion(result.region);
      // Only auto-navigate if user is on the root with no stored preference
      if (window.location.pathname === '/' && result.region !== 'global') {
        const newPath = regionPath(result.region) || '/';
        window.history.replaceState(null, '', newPath);
        setPath(newPath);
      }
      setRegion(result.region);
      setGeoReady(true);
    });
  }, []);

  // Sync region with URL route
  useEffect(() => {
    setRegion(route.region);
  }, [route.region]);

  const navigate = useCallback((newPath: string) => {
    window.history.pushState(null, '', newPath);
    setPath(newPath);
    window.scrollTo({ top: 0, behavior: 'instant' });
  }, []);

  // Handle browser back/forward
  useEffect(() => {
    function onPopState() {
      setPath(window.location.pathname);
    }
    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, []);

  function handleSelectRegion(newRegion: RegionCode) {
    setStoredRegion(newRegion);
    setRegion(newRegion);
    // Rebuild the path with the new region, preserving the current page type
    const currentRoute = parsePath(path);
    let newPath: string;
    if (currentRoute.type === 'article' && currentRoute.param) {
      newPath = `${regionPath(newRegion)}/articles/${currentRoute.param}`;
    } else if (currentRoute.type === 'category' && currentRoute.param) {
      newPath = `${regionPath(newRegion)}/categories/${currentRoute.param}`;
    } else if (currentRoute.type === 'strategy') {
      newPath = `${regionPath(newRegion)}/strategy`;
    } else {
      newPath = regionPath(newRegion) || '/';
    }
    window.history.pushState(null, '', newPath);
    setPath(newPath);
    window.scrollTo({ top: 0, behavior: 'instant' });
  }

  // Build SEO data based on route
  const r = getRegion(region);
  const seoPath = path === '/' ? '' : path;
  let meta = buildHomeMeta(r);
  let schemas: object[] = [buildOrganizationSchema(), buildWebSiteSchema()];

  let breadcrumbs: Breadcrumb[] = [
    { name: 'Home', url: regionPath(region) || '/' },
  ];

  let content: React.ReactNode = null;

  if (route.type === 'home') {
    meta = buildHomeMeta(r);
    const homeArticles = getArticlesForRegion(region);
    schemas = [
      buildOrganizationSchema(),
      buildWebSiteSchema(),
      buildItemListSchema(r, homeArticles),
    ];
    content = <HomePage region={region} onNavigate={navigate} />;
  } else if (route.type === 'article' && route.param) {
    const article = getArticle(route.param);
    if (article) {
      const category = getCategory(article.category);
      meta = buildArticleMeta(r, article);
      breadcrumbs = [
        { name: 'Home', url: regionPath(region) || '/' },
        { name: category?.name ?? 'Category', url: `${regionPath(region)}/categories/${article.category}` },
        { name: article.title, url: `${regionPath(region)}/articles/${article.slug}` },
      ];
      const faqSchema = buildFaqSchema(article);
      schemas = [
        buildOrganizationSchema(),
        buildArticleSchema(r, article),
        buildBreadcrumbSchema(r, breadcrumbs),
        ...(faqSchema ? [faqSchema] : []),
      ];
      content = (
        <ArticlePage
          region={region}
          article={article}
          onNavigate={navigate}
          breadcrumbs={breadcrumbs}
        />
      );
    } else {
      content = <NotFoundPage onNavigate={navigate} />;
    }
  } else if (route.type === 'category' && route.param) {
    const category = getCategory(route.param);
    if (category) {
      meta = buildCategoryMeta(r, route.param);
      breadcrumbs = [
        { name: 'Home', url: regionPath(region) || '/' },
        { name: category.name, url: `${regionPath(region)}/categories/${route.param}` },
      ];
      const categoryArticles = getArticlesForRegion(region).filter(
        (a) => a.category === route.param
      );
      schemas = [
        buildOrganizationSchema(),
        buildBreadcrumbSchema(r, breadcrumbs),
        buildItemListSchema(r, categoryArticles),
      ];
      content = (
        <CategoryPage
          region={region}
          categorySlug={route.param}
          onNavigate={navigate}
          breadcrumbs={breadcrumbs}
        />
      );
    } else {
      content = <NotFoundPage onNavigate={navigate} />;
    }
  } else if (route.type === 'strategy') {
    meta = {
      title: `SEO & Domain Authority Strategy | African Ranked ${r.name}`,
      description: `How African Ranked targets ${r.name} and builds global domain authority through geo-targeting, hreflang, schema markup, and regional content clusters.`,
      ogTitle: 'African Ranked SEO & Domain Authority Strategy',
      ogDescription:
        'A comprehensive geo-targeting and domain authority strategy for a global Africa-focused content platform.',
      ogUrl: regionUrl(region, '/strategy'),
      ogImage: `${SITE_URL}/og/strategy.png`,
      keywords: ['SEO strategy', 'geo-targeting', 'hreflang', 'domain authority', 'African Ranked'],
    };
    schemas = [buildOrganizationSchema(), buildWebSiteSchema()];
    content = <StrategyPage region={region} onNavigate={navigate} />;
  } else {
    content = <NotFoundPage onNavigate={navigate} />;
  }

  return (
    <div className="min-h-screen bg-stone-50 font-sans antialiased">
      <SeoHead region={region} path={seoPath} meta={meta} schemas={schemas} ogType={route.type === 'article' ? 'article' : 'website'} />
      <Header region={region} onNavigate={navigate} onSelectRegion={handleSelectRegion} />
      <main>{content}</main>
      <Footer region={region} onNavigate={navigate} />
    </div>
  );
}
