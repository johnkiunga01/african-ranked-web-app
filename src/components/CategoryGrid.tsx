import {
  Plane,
  UtensilsCrossed,
  Music,
  TrendingUp,
  Cpu,
  PawPrint,
  ArrowRight,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { motion } from 'framer-motion';
import type { RegionCode } from '@/types';
import { CATEGORIES } from '@/data/categories';
import { regionPath } from '@/utils/seo';

const ICON_MAP: Record<string, LucideIcon> = {
  Plane,
  UtensilsCrossed,
  Music,
  TrendingUp,
  Cpu,
  PawPrint,
};

interface CategoryGridProps {
  region: RegionCode;
  onNavigate: (path: string) => void;
}

export function CategoryGrid({ region, onNavigate }: CategoryGridProps) {
  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
      {CATEGORIES.map((cat, i) => {
        const Icon = ICON_MAP[cat.icon] ?? Plane;
        return (
          <motion.button
            key={cat.slug}
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.35, delay: i * 0.05 }}
            onClick={() => onNavigate(`${regionPath(region)}/categories/${cat.slug}`)}
            className="group flex flex-col items-center gap-3 rounded-xl border border-stone-200 bg-white p-5 text-center transition hover:border-amber-300 hover:shadow-md"
          >
            <div
              className="flex h-12 w-12 items-center justify-center rounded-xl text-white transition group-hover:scale-110"
              style={{ backgroundColor: cat.color }}
            >
              <Icon className="h-6 w-6" />
            </div>
            <span className="text-sm font-semibold text-stone-800">
              {cat.name.split(' & ')[0]}
            </span>
            <ArrowRight className="h-4 w-4 text-stone-400 transition group-hover:translate-x-1 group-hover:text-amber-600" />
          </motion.button>
        );
      })}
    </div>
  );
}
