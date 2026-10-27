import { motion } from 'framer-motion';
import { ChevronRight } from 'lucide-react';
import type { RegionCode, Breadcrumb } from '@/types';
import { getRegion } from '@/data/regions';
import { getCategory } from '@/data/categories';
import { getArticlesForRegion } from '@/data/articles';
import { ArticleCard } from '@/components/ArticleCard';
import { Newsletter } from '@/components/Newsletter';
import { StaggerContainer, StaggerItem } from '@/components/Animation';

interface CategoryPageProps {
  region: RegionCode;
  categorySlug: string;
  onNavigate: (path: string) => void;
  breadcrumbs: Breadcrumb[];
}

export function CategoryPage({
  region,
  categorySlug,
  onNavigate,
  breadcrumbs,
}: CategoryPageProps) {
  const r = getRegion(region);
  const category = getCategory(categorySlug);
  const articles = getArticlesForRegion(region).filter(
    (a) => a.category === categorySlug
  );

  if (!category) {
    return (
      <div className="mx-auto max-w-2xl px-4 py-20 text-center">
        <h1 className="text-2xl font-bold text-stone-900">Category not found</h1>
        <button
          onClick={() => onNavigate('/')}
          className="mt-4 text-amber-700 hover:text-amber-600"
        >
          Back home
        </button>
      </div>
    );
  }

  return (
    <div className="pb-16">
      {/* Breadcrumb */}
      <nav className="mx-auto max-w-7xl px-4 py-4 sm:px-6">
        <ol className="flex flex-wrap items-center gap-1.5 text-xs text-stone-500">
          {breadcrumbs.map((bc, i) => (
            <li key={bc.url} className="flex items-center gap-1.5">
              {i > 0 && <ChevronRight className="h-3 w-3" />}
              <button onClick={() => onNavigate(bc.url)} className="hover:text-amber-700">
                {bc.name}
              </button>
            </li>
          ))}
        </ol>
      </nav>

      {/* Header */}
      <section
        className="relative overflow-hidden"
        style={{ backgroundColor: category.color }}
      >
        <div className="absolute inset-0 bg-gradient-to-br from-black/40 to-black/10" />
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
          className="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20"
        >
          <h1 className="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
            {category.name}
          </h1>
          <p className="mt-4 max-w-2xl text-lg text-white/90">{category.description}</p>
          <div className="mt-6 flex items-center gap-3 text-sm text-white/80">
            <span>{r.flag} {r.name} edition</span>
            <span>&bull;</span>
            <span>{articles.length} rankings</span>
          </div>
        </motion.div>
      </section>

      {/* Articles */}
      <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        {articles.length > 0 ? (
          <StaggerContainer>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {articles.map((article) => (
                <StaggerItem key={article.slug}>
                  <ArticleCard article={article} region={region} onNavigate={onNavigate} />
                </StaggerItem>
              ))}
            </div>
          </StaggerContainer>
        ) : (
          <p className="py-20 text-center text-stone-500">
            No rankings in this category yet for {r.name}. Check back soon!
          </p>
        )}
      </section>

      <section className="mx-auto max-w-7xl px-4 sm:px-6">
        <Newsletter region={r} />
      </section>
    </div>
  );
}
