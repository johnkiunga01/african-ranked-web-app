import { motion, AnimatePresence } from 'framer-motion';
import { Menu, X } from 'lucide-react';
import { useState } from 'react';
import type { RegionCode } from '@/types';
import { getRegion } from '@/data/regions';
import { CATEGORIES } from '@/data/categories';
import { regionPath } from '@/utils/seo';
import { RegionSelector } from './RegionSelector';

interface HeaderProps {
  region: RegionCode;
  onNavigate: (path: string) => void;
  onSelectRegion: (region: RegionCode) => void;
}

export function Header({ region, onNavigate, onSelectRegion }: HeaderProps) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const r = getRegion(region);
  const basePath = regionPath(region);

  function nav(path: string) {
    onNavigate(path);
    setMobileOpen(false);
  }

  return (
    <>
      <header className="fixed inset-x-0 top-0 z-40">
        {/* Announcement bar */}
        <div className="bg-amber-600 text-center py-1.5 text-xs font-medium text-amber-950">
          {r.tagline}
        </div>

        <div className="border-b border-stone-200 bg-white/90 backdrop-blur-md">
          <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
            {/* Logo */}
            <button onClick={() => nav(basePath || '/')} className="flex items-center gap-2">
              <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-700 text-white">
                <span className="text-lg font-bold">A</span>
              </div>
              <div className="text-left">
                <div className="text-base font-bold leading-tight text-stone-900">
                  African Ranked
                </div>
                <div className="text-[10px] font-medium uppercase tracking-wider text-stone-500">
                  {r.flag} {r.name}
                </div>
              </div>
            </button>

            {/* Desktop nav */}
            <nav className="hidden items-center gap-1 lg:flex">
              {CATEGORIES.slice(0, 5).map((cat) => (
                <button
                  key={cat.slug}
                  onClick={() => nav(`${basePath}/categories/${cat.slug}`)}
                  className="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                >
                  {cat.name.split(' & ')[0]}
                </button>
              ))}
              <button
                onClick={() => nav(`${basePath}/strategy`)}
                className="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
              >
                SEO Strategy
              </button>
            </nav>

            <div className="flex items-center gap-3">
              <RegionSelector current={region} onSelect={onSelectRegion} />
              <button
                className="lg:hidden"
                onClick={() => setMobileOpen((v) => !v)}
                aria-label="Toggle menu"
              >
                {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
              </button>
            </div>
          </div>
        </div>

        {/* Mobile menu */}
        <AnimatePresence>
          {mobileOpen && (
            <motion.nav
              initial={{ height: 0, opacity: 0 }}
              animate={{ height: 'auto', opacity: 1 }}
              exit={{ height: 0, opacity: 0 }}
              transition={{ duration: 0.2 }}
              className="overflow-hidden border-b border-stone-200 bg-white lg:hidden"
            >
              <div className="space-y-1 px-4 py-3">
                {CATEGORIES.map((cat) => (
                  <button
                    key={cat.slug}
                    onClick={() => nav(`${basePath}/categories/${cat.slug}`)}
                    className="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-stone-700 hover:bg-stone-100"
                  >
                    {cat.name}
                  </button>
                ))}
                <button
                  onClick={() => nav(`${basePath}/strategy`)}
                  className="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-stone-700 hover:bg-stone-100"
                >
                  SEO Strategy
                </button>
              </div>
            </motion.nav>
          )}
        </AnimatePresence>
      </header>
      <div className="h-24" />
    </>
  );
}
