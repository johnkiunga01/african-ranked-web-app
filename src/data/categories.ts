import type { Category } from '@/types';

export const CATEGORIES: Category[] = [
  {
    slug: 'travel',
    name: 'Travel & Destinations',
    description: 'Ranked guides to Africa\'s most breathtaking destinations, from Victoria Falls to the Pyramids of Giza.',
    icon: 'Plane',
    color: '#0ea5e9',
  },
  {
    slug: 'food',
    name: 'Food & Cuisine',
    description: 'The best African dishes, restaurants, and culinary traditions, ranked by region and taste.',
    icon: 'UtensilsCrossed',
    color: '#f97316',
  },
  {
    slug: 'music',
    name: 'Music & Culture',
    description: 'Africa\'s top artists, albums, genres, and cultural movements, ranked and celebrated.',
    icon: 'Music',
    color: '#e11d48',
  },
  {
    slug: 'business',
    name: 'Business & Startups',
    description: 'Ranked lists of Africa\'s fastest-growing startups, investors, and business hubs.',
    icon: 'TrendingUp',
    color: '#16a34a',
  },
  {
    slug: 'tech',
    name: 'Tech & Innovation',
    description: 'Africa\'s top tech hubs, innovations, and digital breakthroughs, ranked.',
    icon: 'Cpu',
    color: '#2563eb',
  },
  {
    slug: 'wildlife',
    name: 'Wildlife & Safaris',
    description: 'The best safaris, wildlife reserves, and conservation stories across Africa.',
    icon: 'PawPrint',
    color: '#ca8a04',
  },
];

export function getCategory(slug: string): Category | undefined {
  return CATEGORIES.find((c) => c.slug === slug);
}
