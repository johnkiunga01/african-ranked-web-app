import { motion } from 'framer-motion';
import { Compass, TrendingUp } from 'lucide-react';
import type { RegionCode } from '@/types';
import { getRegion } from '@/data/regions';
import { getArticlesForRegion } from '@/data/articles';
import { ArticleCard } from '@/components/ArticleCard';
import { CategoryGrid } from '@/components/CategoryGrid';
import { Newsletter } from '@/components/Newsletter';
import { StaggerContainer, StaggerItem } from '@/components/Animation';

interface HomePageProps {
  region: RegionCode;
  onNavigate: (path: string) => void;
}

export function HomePage({ region, onNavigate }: HomePageProps) {
  const r = getRegion(region);
  const articles = getArticlesForRegion(region);
  const featured = articles[0];
  const rest = articles.slice(1);

  return (
    <div className="space-y-16 pb-16">
      {/* Hero */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-stone-900 via-stone-800 to-amber-950" />
        <div className="absolute -right-24 top-0 h-96 w-96 rounded-full bg-amber-600/15 blur-3xl" />
        <div className="absolute -left-24 bottom-0 h-96 w-96 rounded-full bg-orange-700/10 blur-3xl" />

        <div className="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="max-w-3xl"
          >
            <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-500/10 px-4 py-1.5 text-sm font-medium text-amber-300">
              <Compass className="h-4 w-4" />
              {r.flag} {r.name} edition
            </div>
            <h1 className="text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-6xl">
              The best of Africa,
              <span className="block bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                ranked and ranked again.
              </span>
            </h1>
            <p className="mt-6 max-w-2xl text-lg leading-relaxed text-stone-300">
              {r.tagline}. We rank the top destinations, cuisines, startups, music, and wildlife
              across all 54 African nations — updated weekly and tailored for readers in{' '}
              {r.name}.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <button
                onClick={() => {
                  const el = document.getElementById('featured');
                  el?.scrollIntoView({ behavior: 'smooth' });
                }}
                className="rounded-xl bg-amber-500 px-6 py-3 font-semibold text-stone-950 transition hover:bg-amber-400"
              >
                {r.localizedTerms.explore}
              </button>
              <button
                onClick={() => {
                  const el = document.getElementById('categories');
                  el?.scrollIntoView({ behavior: 'smooth' });
                }}
                className="rounded-xl border border-white/20 bg-white/5 px-6 py-3 font-semibold text-white backdrop-blur transition hover:bg-white/10"
              >
                Browse categories
              </button>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Categories */}
      <section id="categories" className="mx-auto max-w-7xl px-4 sm:px-6">
        <div className="mb-8 flex items-end justify-between">
          <div>
            <h2 className="text-2xl font-bold text-stone-900 sm:text-3xl">Explore by category</h2>
            <p className="mt-1 text-stone-600">Six ways to discover the best of Africa</p>
          </div>
        </div>
        <CategoryGrid region={region} onNavigate={onNavigate} />
      </section>

      {/* Featured article */}
      {featured && (
        <section id="featured" className="mx-auto max-w-7xl px-4 sm:px-6">
          <div className="mb-8 flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-amber-600" />
            <h2 className="text-2xl font-bold text-stone-900 sm:text-3xl">
              {r.localizedTerms.featured}
            </h2>
          </div>
          <ArticleCard article={featured} region={region} onNavigate={onNavigate} featured />
        </section>
      )}

      {/* Trending grid */}
      {rest.length > 0 && (
        <section className="mx-auto max-w-7xl px-4 sm:px-6">
          <div className="mb-8">
            <h2 className="text-2xl font-bold text-stone-900 sm:text-3xl">
              {r.localizedTerms.trending}
            </h2>
            <p className="mt-1 text-stone-600">More rankings from across the continent</p>
          </div>
          <StaggerContainer>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {rest.map((article) => (
                <StaggerItem key={article.slug}>
                  <ArticleCard article={article} region={region} onNavigate={onNavigate} />
                </StaggerItem>
              ))}
            </div>
          </StaggerContainer>
        </section>
      )}

      {/* Newsletter */}
      <section className="mx-auto max-w-7xl px-4 sm:px-6">
        <Newsletter region={r} />
      </section>
    </div>
  );
}
