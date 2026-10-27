import { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Globe, ChevronDown, Check } from 'lucide-react';
import type { RegionCode } from '@/types';
import { REGION_LIST, getRegion } from '@/data/regions';

interface RegionSelectorProps {
  current: RegionCode;
  onSelect: (region: RegionCode) => void;
}

export function RegionSelector({ current, onSelect }: RegionSelectorProps) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);
  const currentRegion = getRegion(current);

  useEffect(() => {
    function handler(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen((v) => !v)}
        className="flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/20"
        aria-label={`Current region: ${currentRegion.name}. Click to change region.`}
      >
        <Globe className="h-4 w-4" />
        <span className="hidden sm:inline">{currentRegion.name}</span>
        <span className="text-base leading-none sm:hidden">{currentRegion.flag}</span>
        <ChevronDown className={`h-3 w-3 transition ${open ? 'rotate-180' : ''}`} />
      </button>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: -8, scale: 0.96 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -8, scale: 0.96 }}
            transition={{ duration: 0.15 }}
            className="absolute right-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-stone-200 bg-white py-2 shadow-xl"
          >
            <button
              onClick={() => {
                onSelect('global');
                setOpen(false);
              }}
              className="flex w-full items-center justify-between px-4 py-2.5 text-left text-sm hover:bg-stone-50"
            >
              <span className="flex items-center gap-2">
                <span className="text-base">🌍</span> Global
              </span>
              {current === 'global' && <Check className="h-4 w-4 text-amber-600" />}
            </button>
            <div className="my-1 border-t border-stone-100" />
            {REGION_LIST.map((region) => (
              <button
                key={region.code}
                onClick={() => {
                  onSelect(region.code);
                  setOpen(false);
                }}
                className="flex w-full items-center justify-between px-4 py-2.5 text-left text-sm hover:bg-stone-50"
              >
                <span className="flex items-center gap-2">
                  <span className="text-base">{region.flag}</span>
                  {region.name}
                </span>
                {current === region.code && <Check className="h-4 w-4 text-amber-600" />}
              </button>
            ))}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
