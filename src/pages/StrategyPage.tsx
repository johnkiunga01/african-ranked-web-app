import { motion } from 'framer-motion';
import {
  Globe,
  Link2,
  Search,
  TrendingUp,
  MapPin,
  FileText,
  Users,
  Calendar,
  Target,
  Award,
} from 'lucide-react';
import type { RegionCode } from '@/types';
import { getRegion } from '@/data/regions';
import { CATEGORIES } from '@/data/categories';
import { Newsletter } from '@/components/Newsletter';

interface StrategyPageProps {
  region: RegionCode;
  onNavigate: (path: string) => void;
}

const TECHNICAL_ARCHITECTURE = [
  {
    icon: Globe,
    title: 'URL Structure: Subdirectory Model',
    description:
      'We use subdirectories (/us/, /uk/, /au/, /ca/, /ng/, /za/, /ke/) rather than subdomains. This consolidates all backlink authority to a single domain (africanranked.com), maximizing domain authority across every region while allowing per-region content targeting.',
    points: [
      'Pattern: africanranked.com/{region}/articles/{slug}',
      'Global default at root: africanranked.com/',
      'ccTLD independence avoided — one domain, one authority signal',
    ],
  },
  {
    icon: MapPin,
    title: 'Geo-Detection Pipeline',
    description:
      'On first visit, the user\'s IP is resolved via the ipapi.co geolocation API. The detected country code maps to the closest configured region. If no match, the user sees the Global edition. The choice is persisted in localStorage and can be overridden via the region selector.',
    points: [
      'Primary: IP geolocation (ipapi.co) — server-side recommendable via Laravel middleware',
      'Fallback: browser timezone heuristic (Intl API)',
      'Override: user-controlled region switcher in header',
      'Persistence: localStorage to avoid re-detection on every page',
    ],
  },
  {
    icon: Link2,
    title: 'Hreflang & Canonical Configuration',
    description:
      'Every page renders a full set of hreflang link tags — one per locale (en-US, en-GB, en-AU, en-CA, en-NG, en-ZA, en-KE, en) plus an x-default pointing to the global edition. Canonical tags always point to the current region\'s URL to prevent duplicate-content penalties.',
    points: [
      'hreflang: each region gets its locale code (e.g., en-NG for Nigeria)',
      'x-default: points to the root (global) edition',
      'canonical: self-referencing per region URL',
      'Implemented client-side via React helmet pattern; server-rendered by Laravel for crawlers',
    ],
  },
  {
    icon: FileText,
    title: 'Dynamic Meta Tags & Schema Markup',
    description:
      'Meta titles, descriptions, and Open Graph tags are generated per region with localized terminology and currency. Schema.org JSON-LD is injected dynamically: Organization + WebSite on all pages, Article + FAQPage + BreadcrumbList on article pages, and ItemList on category pages.',
    points: [
      'Article schema: includes author, datePublished, dateModified, wordCount',
      'FAQPage schema: +40% AI search engine visibility (Princeton GEO study, 2024)',
      'BreadcrumbList: aids Google rich results and AI citation extraction',
      'SpeakableSpecification: marks h1 and summary for voice search extraction',
    ],
  },
  {
    icon: Search,
    title: 'Sitemap & Robots.txt',
    description:
      'A multi-regional sitemap index references per-region sitemaps. Each regional sitemap includes all article and category URLs for that region. robots.txt allows all major search and AI bots (Googlebot, Bingbot, GPTBot, ClaudeBot, PerplexityBot) to ensure citation in AI-generated answers.',
    points: [
      'Sitemap index: /sitemap.xml linking to /sitemaps/{region}.xml',
      'AI bot allowlist: GPTBot, ClaudeBot, PerplexityBot, ChatGPT-User',
      'robots.txt: crawl-delay 0, sitemap reference included',
    ],
  },
];

const REGION_STRATEGIES = [
  {
    region: 'us',
    name: 'United States',
    keywordClusters: [
      'best African safari from USA',
      'African startups to invest in',
      'best African restaurants in America',
      'Afrobeats concerts in the US',
    ],
    contentCadence: '3 articles/week',
    partnerships: 'Travel + Leisure, Condé Nast Traveler, Blavity, The Root',
    backlinkFocus: 'US travel blogs, African diaspora publications, university African studies departments',
  },
  {
    region: 'uk',
    name: 'United Kingdom',
    keywordClusters: [
      'best Africa holidays from UK',
      'African heritage tours UK',
      'African food markets London',
      'African footballers Premier League',
    ],
    contentCadence: '3 articles/week',
    partnerships: 'BBC Africa, The Guardian Africa, NME Africa, Time Out London',
    backlinkFocus: 'UK travel publications, BBC/ITV references, African diaspora community sites',
  },
  {
    region: 'ng',
    name: 'Nigeria',
    keywordClusters: [
      'best jollof rice in Lagos',
      'top Nigerian startups 2026',
      'Afrobeats charts Nigeria',
      'best universities in Nigeria ranked',
    ],
    contentCadence: '5 articles/week',
    partnerships: 'Pulse.ng, Naija Food, BellaNaija, TechCabal, The Guardian Nigeria',
    backlinkFocus: 'Nigerian news sites, Nollywood blogs, Nigerian fintech publications',
  },
  {
    region: 'za',
    name: 'South Africa',
    keywordClusters: [
      'best wine estates South Africa',
      'top Cape Town restaurants ranked',
      'Amapiano artists ranked',
      'best safaris Kruger vs Sabi Sands',
    ],
    contentCadence: '4 articles/week',
    partnerships: 'News24, Times Live, Getaway Magazine, Food & Home, Zkhiphani',
    backlinkFocus: 'South African lifestyle magazines, wine tourism boards, SA music blogs',
  },
  {
    region: 'ke',
    name: 'Kenya',
    keywordClusters: [
      'best Maasai Mara safari packages',
      'top Nairobi restaurants ranked',
      'Kenyan tech startups ranked',
      'best Kenyan athletes of all time',
    ],
    contentCadence: '4 articles/week',
    partnerships: 'Nation Africa, Standard Media, Kaluhi\'s Kitchen, Mpasho',
    backlinkFocus: 'Kenyan news outlets, safari operator directories, East African tech blogs',
  },
  {
    region: 'au',
    name: 'Australia',
    keywordClusters: [
      'best African safaris for Australians',
      'African food in Sydney and Melbourne',
      'African wildlife photography tours',
      'best African surf destinations',
    ],
    contentCadence: '2 articles/week',
    partnerships: 'Traveller AU, Broadsheet, Concrete Playground, SBS Food',
    backlinkFocus: 'Australian travel media, food publications, diaspora community sites',
  },
];

const DOMAIN_AUTHORITY_PLAYS = [
  {
    icon: Users,
    title: 'Local Content Partnerships',
    description:
      'Partner with established regional publishers and influencers for co-authored rankings. Each partnership generates reciprocal backlinks from high-authority domains in the target region.',
    metric: 'Target: 15 partnerships per region in year 1',
  },
  {
    icon: Calendar,
    title: 'Regional Publishing Cadence',
    description:
      'African audience markets (NG, ZA, KE) get 4-5 articles/week to match high engagement. Diaspora markets (US, UK, AU, CA) get 2-3/week with higher production value and longer-form content.',
    metric: 'Target: 20 articles/week total across all regions',
  },
  {
    icon: Target,
    title: 'Content Clusters by Region',
    description:
      'Each region gets a content cluster: a pillar ranking page (e.g., "Best Safaris in Africa") supported by 8-12 supporting articles that interlink. Clusters build topical authority, which Google\'s E-E-A-T guidelines reward.',
    metric: 'Target: 6 content clusters per region',
  },
  {
    icon: Award,
    title: 'Digital PR & Data-Driven Content',
    description:
      'Publish original research (e.g., "African Startup Funding Report 2026") that journalists and AI engines cite. Data-driven content earns natural backlinks from .edu, .gov, and news domains.',
    metric: 'Target: 4 original research reports per year',
  },
];

export function StrategyPage({ region }: StrategyPageProps) {
  const r = getRegion(region);

  return (
    <div className="pb-16">
      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-stone-900 via-stone-800 to-amber-950">
        <div className="absolute -right-24 top-0 h-96 w-96 rounded-full bg-amber-600/15 blur-3xl" />
        <div className="relative mx-auto max-w-4xl px-4 py-16 sm:px-6 sm:py-24">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-500/10 px-4 py-1.5 text-sm font-medium text-amber-300">
              <TrendingUp className="h-4 w-4" />
              SEO & Domain Authority Strategy
            </div>
            <h1 className="text-3xl font-bold leading-tight text-white sm:text-5xl">
              How African Ranked targets every region and builds global domain authority
            </h1>
            <p className="mt-6 max-w-2xl text-lg leading-relaxed text-stone-300">
              A documented strategy for geo-targeting 8 regions, building topical authority, and
              earning citations from both traditional search engines and AI search engines.
              Currently viewing the {r.flag} {r.name} edition.
            </p>
          </motion.div>
        </div>
      </section>

      {/* Technical Architecture */}
      <section className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
        <h2 className="text-2xl font-bold text-stone-900 sm:text-3xl">Technical Architecture</h2>
        <p className="mt-2 text-stone-600">
          The infrastructure that powers geo-targeted content delivery and international SEO.
        </p>

        <div className="mt-8 space-y-6">
          {TECHNICAL_ARCHITECTURE.map((item, i) => {
            const Icon = item.icon;
            return (
              <motion.div
                key={item.title}
                initial={{ opacity: 0, y: 16 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.4, delay: i * 0.05 }}
                className="rounded-xl border border-stone-200 bg-white p-6"
              >
                <div className="flex items-start gap-4">
                  <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <Icon className="h-5 w-5" />
                  </div>
                  <div className="flex-1">
                    <h3 className="text-lg font-bold text-stone-900">{item.title}</h3>
                    <p className="mt-2 text-sm leading-relaxed text-stone-600">
                      {item.description}
                    </p>
                    <ul className="mt-3 space-y-1.5">
                      {item.points.map((pt, j) => (
                        <li key={j} className="flex gap-2 text-sm text-stone-700">
                          <span className="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-500" />
                          {pt}
                        </li>
                      ))}
                    </ul>
                  </div>
                </div>
              </motion.div>
            );
          })}
        </div>
      </section>

      {/* Region Targeting */}
      <section className="bg-stone-50 py-16">
        <div className="mx-auto max-w-5xl px-4 sm:px-6">
          <h2 className="text-2xl font-bold text-stone-900 sm:text-3xl">
            Region-Specific Targeting
          </h2>
          <p className="mt-2 text-stone-600">
            Each region has a tailored keyword strategy, content cadence, and backlink plan.
          </p>

          <div className="mt-8 grid gap-6 md:grid-cols-2">
            {REGION_STRATEGIES.map((rs, i) => {
              const regionData = getRegion(rs.region as RegionCode);
              return (
                <motion.div
                  key={rs.region}
                  initial={{ opacity: 0, y: 16 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: i * 0.05 }}
                  className="rounded-xl border border-stone-200 bg-white p-6"
                >
                  <div className="flex items-center gap-3">
                    <span className="text-2xl">{regionData.flag}</span>
                    <div>
                      <h3 className="text-lg font-bold text-stone-900">{rs.name}</h3>
                      <span className="text-xs text-stone-500">
                        /{rs.region}/ &bull; {regionData.currency}
                      </span>
                    </div>
                  </div>

                  <div className="mt-4 space-y-3 text-sm">
                    <div>
                      <div className="font-semibold text-stone-800">Keyword clusters</div>
                      <ul className="mt-1 space-y-0.5 text-stone-600">
                        {rs.keywordClusters.map((kw) => (
                          <li key={kw} className="text-xs">&ldquo;{kw}&rdquo;</li>
                        ))}
                      </ul>
                    </div>
                    <div>
                      <div className="font-semibold text-stone-800">Publishing cadence</div>
                      <div className="text-stone-600">{rs.contentCadence}</div>
                    </div>
                    <div>
                      <div className="font-semibold text-stone-800">Partnerships</div>
                      <div className="text-stone-600">{rs.partnerships}</div>
                    </div>
                    <div>
                      <div className="font-semibold text-stone-800">Backlink focus</div>
                      <div className="text-stone-600">{rs.backlinkFocus}</div>
                    </div>
                  </div>
                </motion.div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Domain Authority */}
      <section className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
        <h2 className="text-2xl font-bold text-stone-900 sm:text-3xl">
          Domain Authority Growth Plan
        </h2>
        <p className="mt-2 text-stone-600">
          Four pillars for growing authority across all target regions simultaneously.
        </p>

        <div className="mt-8 space-y-6">
          {DOMAIN_AUTHORITY_PLAYS.map((play, i) => {
            const Icon = play.icon;
            return (
              <motion.div
                key={play.title}
                initial={{ opacity: 0, y: 16 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.4, delay: i * 0.05 }}
                className="flex gap-5 rounded-xl bg-gradient-to-r from-stone-900 to-stone-800 p-6 text-white"
              >
                <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500 text-stone-950">
                  <Icon className="h-6 w-6" />
                </div>
                <div>
                  <h3 className="text-lg font-bold">{play.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-stone-300">
                    {play.description}
                  </p>
                  <div className="mt-3 inline-flex items-center gap-2 rounded-full bg-amber-500/20 px-3 py-1 text-xs font-semibold text-amber-300">
                    {play.metric}
                  </div>
                </div>
              </motion.div>
            );
          })}
        </div>
      </section>

      {/* GEO for AI Search */}
      <section className="bg-amber-950 py-16 text-amber-50">
        <div className="mx-auto max-w-4xl px-4 sm:px-6">
          <h2 className="text-2xl font-bold sm:text-3xl">GEO: Generative Engine Optimization</h2>
          <p className="mt-2 text-amber-200">
            Beyond Google — optimizing for AI search engines (ChatGPT, Perplexity, Claude, Gemini).
          </p>

          <div className="mt-8 grid gap-4 sm:grid-cols-2">
            {[
              { method: 'Cite Sources', boost: '+40%', detail: 'Every ranking cites its data source (Tourism Board, Partech, Spotify, etc.)' },
              { method: 'Statistics', boost: '+37%', detail: 'Specific numbers in every article (e.g., "$4.2B raised", "14B streams")' },
              { method: 'Expert Quotes', boost: '+30%', detail: 'Attributed quotations from recognized experts in each field' },
              { method: 'FAQPage Schema', boost: '+40%', detail: 'Structured FAQ section with JSON-LD on every article for AI citation' },
              { method: 'Authoritative Tone', boost: '+25%', detail: 'Confident, definitive language ("The best", "Ranked first")' },
              { method: 'Answer-First Format', boost: '+20%', detail: 'Direct answer at top, then supporting evidence below' },
            ].map((item) => (
              <div
                key={item.method}
                className="rounded-xl border border-amber-700/30 bg-amber-900/30 p-5"
              >
                <div className="flex items-center justify-between">
                  <span className="font-bold text-white">{item.method}</span>
                  <span className="text-sm font-bold text-amber-400">{item.boost}</span>
                </div>
                <p className="mt-2 text-sm text-amber-200">{item.detail}</p>
              </div>
            ))}
          </div>
          <p className="mt-6 text-xs text-amber-300">
            Based on the Princeton GEO study (Aggarwal et al., KDD 2024), validated on Perplexity.ai.
          </p>
        </div>
      </section>

      {/* Content clusters */}
      <section className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
        <h2 className="text-2xl font-bold text-stone-900 sm:text-3xl">Content Clusters</h2>
        <p className="mt-2 text-stone-600">
          Each category forms a topical authority cluster with interlinked articles.
        </p>

        <div className="mt-8 space-y-4">
          {CATEGORIES.map((cat) => (
            <div key={cat.slug} className="rounded-xl border border-stone-200 bg-white p-5">
              <div className="flex items-center gap-3">
                <div
                  className="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold text-white"
                  style={{ backgroundColor: cat.color }}
                >
                  {cat.name[0]}
                </div>
                <h3 className="font-bold text-stone-900">{cat.name}</h3>
              </div>
              <p className="mt-2 text-sm text-stone-600">{cat.description}</p>
              <div className="mt-3 flex flex-wrap gap-2">
                {['pillar page', '8-12 supporting articles', 'internal linking mesh', 'FAQ schema'].map(
                  (tag) => (
                    <span
                      key={tag}
                      className="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600"
                    >
                      {tag}
                    </span>
                  )
                )}
              </div>
            </div>
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 sm:px-6">
        <Newsletter region={r} />
      </section>
    </div>
  );
}
