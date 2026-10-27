import { Compass, Twitter, Instagram, Youtube, Linkedin } from 'lucide-react';
import type { RegionCode } from '@/types';
import { getRegion } from '@/data/regions';
import { CATEGORIES } from '@/data/categories';
import { regionPath } from '@/utils/seo';

interface FooterProps {
  region: RegionCode;
  onNavigate: (path: string) => void;
}

export function Footer({ region, onNavigate }: FooterProps) {
  const r = getRegion(region);
  const basePath = regionPath(region);

  return (
    <footer className="bg-stone-950 text-stone-400">
      <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div className="grid grid-cols-2 gap-8 md:grid-cols-4 lg:grid-cols-5">
          {/* Brand */}
          <div className="col-span-2">
            <div className="flex items-center gap-2">
              <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-700 text-white">
                <span className="text-lg font-bold">A</span>
              </div>
              <span className="text-lg font-bold text-white">African Ranked</span>
            </div>
            <p className="mt-4 max-w-xs text-sm leading-relaxed">
              A global directory of the best of and ranked-based articles for everything about
              Africa. {r.tagline}
            </p>
            <div className="mt-5 flex gap-3">
              {[Twitter, Instagram, Youtube, Linkedin].map((Icon, i) => (
                <a
                  key={i}
                  href="#"
                  className="flex h-9 w-9 items-center justify-center rounded-full bg-white/5 transition hover:bg-amber-600 hover:text-white"
                  aria-label="Social link"
                >
                  <Icon className="h-4 w-4" />
                </a>
              ))}
            </div>
          </div>

          {/* Categories */}
          <div>
            <h4 className="text-sm font-semibold uppercase tracking-wider text-white">Categories</h4>
            <ul className="mt-4 space-y-2 text-sm">
              {CATEGORIES.slice(0, 5).map((cat) => (
                <li key={cat.slug}>
                  <button
                    onClick={() => onNavigate(`${basePath}/categories/${cat.slug}`)}
                    className="transition hover:text-amber-500"
                  >
                    {cat.name}
                  </button>
                </li>
              ))}
            </ul>
          </div>

          {/* Regions */}
          <div>
            <h4 className="text-sm font-semibold uppercase tracking-wider text-white">Regions</h4>
            <ul className="mt-4 space-y-2 text-sm">
              <li>
                <button onClick={() => onNavigate('/')} className="transition hover:text-amber-500">
                  Global
                </button>
              </li>
              {['us', 'uk', 'au', 'ca', 'ng', 'za', 'ke'].map((code) => (
                <li key={code}>
                  <button
                    onClick={() => onNavigate(`/${code}`)}
                    className="transition hover:text-amber-500"
                  >
                    {getRegion(code as RegionCode).name}
                  </button>
                </li>
              ))}
            </ul>
          </div>

          {/* Company */}
          <div>
            <h4 className="text-sm font-semibold uppercase tracking-wider text-white">Company</h4>
            <ul className="mt-4 space-y-2 text-sm">
              <li>
                <button
                  onClick={() => onNavigate(`${basePath}/strategy`)}
                  className="transition hover:text-amber-500"
                >
                  SEO Strategy
                </button>
              </li>
              <li>
                <a href="#" className="transition hover:text-amber-500">About Us</a>
              </li>
              <li>
                <a href="#" className="transition hover:text-amber-500">Contact</a>
              </li>
              <li>
                <a href="#" className="transition hover:text-amber-500">Privacy Policy</a>
              </li>
            </ul>
          </div>
        </div>

        <div className="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs sm:flex-row">
          <p>&copy; {new Date().getFullYear()} African Ranked. All rights reserved.</p>
          <div className="flex items-center gap-1.5">
            <Compass className="h-4 w-4 text-amber-500" />
            <span>Currently viewing: {r.flag} {r.name}</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
