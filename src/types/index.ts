export type RegionCode = 'us' | 'uk' | 'au' | 'ca' | 'ng' | 'za' | 'ke' | 'global';

export interface Region {
  code: RegionCode;
  name: string;
  countryCode: string;
  language: string;
  locale: string;
  currency: string;
  currencySymbol: string;
  flag: string;
  default: boolean;
  tagline: string;
  localizedTerms: {
    explore: string;
    trending: string;
    featured: string;
    readMore: string;
    subscribe: string;
    newsletterTitle: string;
    newsletterSubtitle: string;
  };
  popularCategories: string[];
}

export interface Category {
  slug: string;
  name: string;
  description: string;
  icon: string;
  color: string;
}

export interface Article {
  slug: string;
  title: string;
  excerpt: string;
  category: string;
  author: string;
  authorTitle: string;
  datePublished: string;
  dateModified: string;
  readTime: number;
  image: string;
  body: ArticleBlock[];
  faqs?: { question: string; answer: string }[];
  regions?: RegionCode[];
}

export type ArticleBlock =
  | { type: 'paragraph'; text: string }
  | { type: 'heading'; level: 2 | 3; text: string }
  | { type: 'list'; items: string[] }
  | { type: 'quote'; text: string; attribution: string };

export interface Breadcrumb {
  name: string;
  url: string;
}
