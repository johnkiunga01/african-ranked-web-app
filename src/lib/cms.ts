import type { Article } from '@/types';

export interface CmsArticle {
  indexable: boolean;
  canonical: string;
  schema?: object;
  reviewer?: { name: string; url: string };
  publishedAt?: string;
  lastVerifiedAt?: string;
  bodyHtml?: string;
  answerBlock?: string;
  keyFacts?: { label: string; value: string; verified_on: string }[];
  items?: { position: number; name: string; scores: Record<string, number>; total: number; blurb: string }[];
  methodology?: string;
  sources?: { title: string; url: string; accessedAt?: string }[];
  corrections?: { summary: string; correctedAt: string }[];
  disclosure?: string;
}
interface Bootstrap {
  articles: Article[];
  page?: { html: string; title?: string } | null;
  edition: string;
}
let cached: Bootstrap | undefined;
export function cmsBootstrap(): Bootstrap | undefined {
  if (cached) return cached;
  if (typeof document === 'undefined') return undefined;
  const text = document.getElementById('ar-cms-data')?.textContent;
  if (!text) return undefined;
  cached = JSON.parse(text) as Bootstrap;
  return cached;
}
