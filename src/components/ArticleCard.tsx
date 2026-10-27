import {
  Plane,
  UtensilsCrossed,
  Music,
  TrendingUp,
  Cpu,
  PawPrint,
  Clock,
  ArrowRight,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { motion } from 'framer-motion';
import type { Article } from '@/types';
import type { RegionCode } from '@/types';
import { getRegion } from '@/data/regions';
import { getCategory } from '@/data/categories';
import { regionPath } from '@/utils/seo';

const ICON_MAP: Record<string, LucideIcon> = {
  Plane,
  UtensilsCrossed,
  Music,
  TrendingUp,
  Cpu,
  PawPrint,
};

interface ArticleCardProps {
  article: Article;
  region: RegionCode;
  onNavigate: (path: string) => void;
  featured?: boolean;
}

export function ArticleCard({ article, region, onNavigate, featured }: ArticleCardProps) {
  const r = getRegion(region);
  const category = getCategory(article.category);
  const Icon = category ? ICON_MAP[category.icon] ?? Plane : Plane;
  const href = `${regionPath(region)}/articles/${article.slug}`;

  if (featured) {
    return (
      <motion.article
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="group relative cursor-pointer overflow-hidden rounded-2xl bg-stone-900"
        onClick={() => onNavigate(href)}
      >
        <div className="relative h-[420px] overflow-hidden">
          <img
            src={article.image}
            alt={article.title}
            className="h-full w-full object-cover transition duration-700 group-hover:scale-105"
            loading="eager"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-stone-950 via-stone-950/50 to-transparent" />
        </div>
        <div className="absolute bottom-0 left-0 right-0 p-6 sm:p-8">
          <div className="mb-3 flex items-center gap-2">
            <span
              className="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold text-white"
              style={{ backgroundColor: category?.color }}
            >
              <Icon className="h-3 w-3" />
              {category?.name.split(' & ')[0]}
            </span>
            <span className="flex items-center gap-1 text-xs font-medium text-stone-300">
              <Clock className="h-3 w-3" />
              {article.readTime} min read
            </span>
          </div>
          <h2 className="text-2xl font-bold leading-tight text-white sm:text-3xl">
            {article.title}
          </h2>
          <p className="mt-2 line-clamp-2 max-w-2xl text-sm text-stone-300 sm:text-base">
            {article.excerpt}
          </p>
          <div className="mt-4 flex items-center gap-2 text-sm font-semibold text-amber-400">
            {r.localizedTerms.readMore}
            <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />
          </div>
        </div>
      </motion.article>
    );
  }

  return (
    <motion.article
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      transition={{ duration: 0.4 }}
      className="group flex cursor-pointer flex-col overflow-hidden rounded-xl border border-stone-200 bg-white transition hover:shadow-lg"
      onClick={() => onNavigate(href)}
    >
      <div className="relative h-48 overflow-hidden">
        <img
          src={article.image}
          alt={article.title}
          className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
          loading="lazy"
        />
        <span
          className="absolute left-3 top-3 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold text-white"
          style={{ backgroundColor: category?.color }}
        >
          <Icon className="h-3 w-3" />
          {category?.name.split(' & ')[0]}
        </span>
      </div>
      <div className="flex flex-1 flex-col p-5">
        <h3 className="text-lg font-bold leading-snug text-stone-900 group-hover:text-amber-700">
          {article.title}
        </h3>
        <p className="mt-2 line-clamp-2 flex-1 text-sm text-stone-600">{article.excerpt}</p>
        <div className="mt-4 flex items-center justify-between text-xs text-stone-500">
          <span className="font-medium text-stone-700">{article.author}</span>
          <span className="flex items-center gap-1">
            <Clock className="h-3 w-3" />
            {article.readTime} min
          </span>
        </div>
      </div>
    </motion.article>
  );
}
