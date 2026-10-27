import type { RegionCode } from '@/types';
import { REGIONS } from '@/data/regions';

const COUNTRY_TO_REGION: Record<string, RegionCode> = {
  US: 'us',
  GB: 'uk',
  AU: 'au',
  CA: 'ca',
  NG: 'ng',
  ZA: 'za',
  KE: 'ke',
};

interface GeoResult {
  region: RegionCode;
  country: string;
  countryCode: string;
  source: 'api' | 'fallback';
}

export async function detectRegion(): Promise<GeoResult> {
  try {
    const response = await fetch('https://ipapi.co/json/');
    if (response.ok) {
      const data = await response.json();
      const countryCode = data.country_code as string;
      if (countryCode && COUNTRY_TO_REGION[countryCode]) {
        return {
          region: COUNTRY_TO_REGION[countryCode],
          country: data.country_name as string,
          countryCode,
          source: 'api',
        };
      }
      if (countryCode) {
        return {
          region: 'global',
          country: data.country_name as string,
          countryCode,
          source: 'api',
        };
      }
    }
  } catch {
    // Fall through to fallback
  }

  // Fallback: check browser timezone for a best-guess region
  const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
  let region: RegionCode = 'global';

  if (tz) {
    if (tz.includes('Lagos')) region = 'ng';
    else if (tz.includes('Johannesburg')) region = 'za';
    else if (tz.includes('Nairobi')) region = 'ke';
    else if (tz.includes('America/')) region = 'us';
    else if (tz.includes('Europe/London')) region = 'uk';
    else if (tz.includes('Australia')) region = 'au';
  }

  return {
    region,
    country: REGIONS[region].name,
    countryCode: REGIONS[region].countryCode || '',
    source: 'fallback',
  };
}

const REGION_STORAGE_KEY = 'ar_selected_region';
const DETECTED_STORAGE_KEY = 'ar_detected_region';

export function getStoredRegion(): RegionCode | null {
  try {
    const stored = localStorage.getItem(REGION_STORAGE_KEY);
    if (stored && stored in REGIONS) return stored as RegionCode;
  } catch {
    // localStorage may be unavailable
  }
  return null;
}

export function setStoredRegion(region: RegionCode): void {
  try {
    localStorage.setItem(REGION_STORAGE_KEY, region);
  } catch {
    // Silently ignore
  }
}

export function getDetectedRegion(): RegionCode | null {
  try {
    const stored = localStorage.getItem(DETECTED_STORAGE_KEY);
    if (stored && stored in REGIONS) return stored as RegionCode;
  } catch {
    // localStorage may be unavailable
  }
  return null;
}

export function setDetectedRegion(region: RegionCode): void {
  try {
    localStorage.setItem(DETECTED_STORAGE_KEY, region);
  } catch {
    // Silently ignore
  }
}
