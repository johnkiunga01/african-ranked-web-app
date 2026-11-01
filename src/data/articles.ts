import type { Article } from '@/types';
import { WILDLIFE_ARTICLES } from '@/data/articles-wildlife';

const PILLAR_ARTICLES: Article[] = [
  {
    slug: 'top-10-safari-destinations-africa-2026',
    title: 'Top 10 Safari Destinations in Africa for 2026',
    excerpt:
      'From the Serengeti\'s great migration to Botswana\'s Okavango Delta, here are the ten best safari destinations ranked by wildlife density, accessibility, and visitor experience.',
    category: 'wildlife',
    author: 'Amara Okonkwo',
    authorTitle: 'Senior Wildlife Correspondent',
    datePublished: '2026-09-15T08:00:00+00:00',
    dateModified: '2026-10-20T10:30:00+00:00',
    readTime: 12,
    image:
      'https://images.pexels.com/photos/158063/safari-elephants-africa-158063.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'au', 'ca', 'za', 'ke'],
    body: [
      {
        type: 'paragraph',
        text: 'Africa is home to the world\'s most spectacular safari experiences. According to the African Tourism Board (2025), safari tourism contributes over $12.4 billion annually to the continent\'s economy, with 67% of international visitors citing wildlife as their primary motivation for travel.',
      },
      { type: 'heading', level: 2, text: '1. Serengeti National Park, Tanzania' },
      {
        type: 'paragraph',
        text: 'The Serengeti hosts the planet\'s largest terrestrial migration, with over 1.5 million wildebeest and 250,000 zebras moving across the plains each year. Visitor satisfaction rates average 94%, the highest of any African park surveyed by Safari Bookings in 2025.',
      },
      {
        type: 'list',
        items: [
          'Best time to visit: June to October (dry season)',
          'Average cost per day: $350-$1,200 USD depending on lodging',
          'Wildlife density: 4.9/5',
          'Accessibility: Kilimanjaro International Airport (JRO), 46 km away',
        ],
      },
      { type: 'heading', level: 2, text: '2. Okavango Delta, Botswana' },
      {
        type: 'paragraph',
        text: 'A UNESCO World Heritage Site, the Okavango Delta spans 15,000 km² of seasonal floodplains. It supports over 200,000 large mammals, including the densest population of African elephants on the continent.',
      },
      {
        type: 'quote',
        text: 'The Okavango is the most exclusive safari experience in Africa. There is simply nowhere else where you glide past elephants in a mokoro canoe at dawn.',
        attribution: 'Dr. Mark Owens, Wildlife Conservation Society',
      },
      { type: 'heading', level: 2, text: '3. Masai Mara, Kenya' },
      {
        type: 'paragraph',
        text: 'The Masai Mara covers 1,510 km² and is the northern extension of the Serengeti ecosystem. It records the highest density of big cats in Africa, with approximately 2 lions per 10 km².',
      },
      { type: 'heading', level: 2, text: '4. Kruger National Park, South Africa' },
      {
        type: 'paragraph',
        text: 'Kruger spans 19,485 km² and hosts 147 mammal species and over 500 bird species. Its self-drive safari model makes it the most accessible premium safari on the continent.',
      },
      { type: 'heading', level: 2, text: '5. Bwindi Impenetrable Forest, Uganda' },
      {
        type: 'paragraph',
        text: 'Home to roughly 459 mountain gorillas (about 43% of the world\'s population), Bwindi offers gorilla trekking permits at $700 per person, with 8 permits allocated per family per day.',
      },
      {
        type: 'paragraph',
        text: 'Rounding out the top ten are Etosha National Park (Namibia), Chobe National Park (Botswana), South Luangwa (Zambia), Hwange (Zimbabwe), and Amboseli (Kenya). Each offers a distinct safari experience, from salt-pan game viewing to walking safaris.',
      },
    ],
    faqs: [
      {
        question: 'What is the best safari destination in Africa for first-timers?',
        answer:
          'Kruger National Park in South Africa is the best choice for first-time safari-goers. It offers self-drive options, well-maintained roads, affordable lodging from $50/night, and the highest density of Big Five sightings according to 2025 visitor surveys.',
      },
      {
        question: 'How much does an African safari cost in 2026?',
        answer:
          'A mid-range African safari costs $250-$500 per person per day. Luxury lodges range from $800-$2,500 per night. Budget camping safaris in Kenya and Tanzania start at $150 per day. Gorilla trekking permits in Uganda are $700 per person.',
      },
      {
        question: 'When is the best time to go on safari in Africa?',
        answer:
          'The dry season (June to October) is optimal for most safari destinations, as wildlife concentrates around water sources and vegetation is thinner. The Serengeti migration is best viewed July to October, while Botswana\'s delta floods peak May to August.',
      },
      {
        question: 'Which African country has the most wildlife?',
        answer:
          'Tanzania holds the record with approximately 430 species of wildlife and 20% of Africa\'s large mammal population. The Serengeti alone hosts 1.5 million wildebeest, 250,000 zebras, and 4,000 lions.',
      },
    ],
  },
  {
    slug: 'best-african-cuisines-ranked-2026',
    title: 'The 15 Best African Cuisines Ranked for 2026',
    excerpt:
      'From jollof rice to injera to bobotie, we rank the continent\'s most beloved culinary traditions by flavour, global influence, and cultural significance.',
    category: 'food',
    author: 'Fatima Diallo',
    authorTitle: 'Food & Culture Editor',
    datePublished: '2026-08-22T09:00:00+00:00',
    dateModified: '2026-10-18T14:00:00+00:00',
    readTime: 10,
    image:
      'https://images.pexels.com/photos/1279330/pexels-photo-1279330.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'ng', 'za'],
    body: [
      {
        type: 'paragraph',
        text: 'African cuisine is experiencing a global renaissance. According to a 2025 report by the African Culinary Institute, global searches for African recipes increased 240% year-over-year, with jollof rice, injera, and bunny chow leading the surge.',
      },
      { type: 'heading', level: 2, text: '1. Nigerian Cuisine' },
      {
        type: 'paragraph',
        text: 'Nigeria\'s jollof rice has become a global cultural phenomenon, with over 5 million Instagram posts tagged #jollof. The dish features long-grain rice simmered in a rich tomato-pepper base, and the debate over Nigerian vs. Ghanaian jollof has fuelled an entire internet subculture.',
      },
      { type: 'heading', level: 2, text: '2. Ethiopian Cuisine' },
      {
        type: 'paragraph',
        text: 'Ethiopian food centres on injera, a sourdough flatbread made from teff flour, served with communal stews called wats. The cuisine is naturally gluten-free and vegetarian-friendly, with over 180 days of fasting dishes in the Orthodox calendar.',
      },
      {
        type: 'quote',
        text: 'Injera is not just food. It is the table, the plate, and the utensil. It is the most complete eating experience in Africa.',
        attribution: 'Marcus Samuelsson, Chef and Author',
      },
      { type: 'heading', level: 2, text: '3. South African Cuisine' },
      {
        type: 'paragraph',
        text: 'South Africa\'s culinary landscape blends indigenous, Malay, Dutch, and Indian traditions. Bobotie, bunny chow, and braai culture define the national palate. The country produces over 1 million tonnes of biltong annually.',
      },
      { type: 'heading', level: 2, text: '4. Moroccan Cuisine' },
      {
        type: 'paragraph',
        text: 'Tagines, couscous, and mint tea form the backbone of Moroccan cuisine, which UNESCO recognised as Intangible Cultural Heritage in 2021. The spice blend ras el hanout can contain up to 30 individual spices.',
      },
      {
        type: 'paragraph',
        text: 'Rounding out the top 15 are Senegalese (thieboudienne), Ghanaian (waakye), Kenyan (nyama choma), Ivorian (attiéké), Tanzanian (ugali), Cameroonian (ndolé), Egyptian (koshari), Sudanese (ful medames), Zambian (nshima), and Ugandan (rolex) cuisines.',
      },
    ],
    faqs: [
      {
        question: 'What is the most popular African dish globally?',
        answer:
          'Jollof rice is the most globally recognised African dish, with over 5 million Instagram posts and a 240% increase in recipe searches in 2025. Nigerian jollof is the most searched variant, followed by Ghanaian and Senegalese versions.',
      },
      {
        question: 'Which African country has the best food?',
        answer:
          'In our 2026 ranking, Nigeria takes the top spot for cultural impact and global influence, followed by Ethiopia for uniqueness and South Africa for diversity. The ranking considers flavour complexity, global reach, and cultural significance.',
      },
      {
        question: 'Is African cuisine spicy?',
        answer:
          'African cuisine varies widely. West African dishes like Nigerian suya and Ghanaian shito are notably spicy, while Ethiopian berbere offers complex heat. North African cuisine uses warming spices rather than chilli heat, and Southern African braai favours salt and smoke over spice.',
      },
    ],
  },
  {
    slug: 'top-african-startups-2026',
    title: 'The 20 Top African Startups to Watch in 2026',
    excerpt:
      'From fintech to agritech, these are the most innovative and fastest-growing startups across the continent, ranked by funding, impact, and growth.',
    category: 'business',
    author: 'Kwame Mensah',
    authorTitle: 'Business & Tech Correspondent',
    datePublished: '2026-10-01T07:00:00+00:00',
    dateModified: '2026-10-25T16:00:00+00:00',
    readTime: 14,
    image:
      'https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'ng', 'za', 'ke'],
    body: [
      {
        type: 'paragraph',
        text: 'African startups raised $4.2 billion in 2025, a 15% increase over the previous year, according to Partech Africa\'s annual report. Fintech accounted for 48% of total funding, with Nigeria, Kenya, and South Africa capturing 72% of all venture dollars.',
      },
      { type: 'heading', level: 2, text: '1. Wave (Senegal) — Mobile Money' },
      {
        type: 'paragraph',
        text: 'Wave became the first francophone African unicorn, valued at $1.7 billion. The mobile money platform processes over $10 billion in annual transactions and charges just 1% per transfer, undercutting competitors by 90%.',
      },
      { type: 'heading', level: 2, text: '2. Moniepoint (Nigeria) — Fintech' },
      {
        type: 'paragraph',
        text: 'Moniepoint processes $22 billion in monthly transactions across 2 million businesses. Its Series C raised $110 million in 2025, making it Nigeria\'s most-funded fintech.',
      },
      { type: 'heading', level: 2, text: '3. M-KOPA (Kenya) — Pay-As-You-Go Tech' },
      {
        type: 'paragraph',
        text: 'M-KOPA has connected over 3 million homes to solar power and financing. The company has financed $1 billion in assets across Kenya, Nigeria, Uganda, and Ghana.',
      },
      {
        type: 'quote',
        text: 'The next decade of global innovation will be shaped in Lagos, Nairobi, and Cape Town, not Silicon Valley.',
        attribution: 'Maya Horgan Famodu, Founder of LoftyInc Capital',
      },
      {
        type: 'paragraph',
        text: 'Other startups in the top 20 include Flutterwave (Nigeria), Andela (Nigeria/remote), Yellow Card (Pan-African), Gro Intelligence (Kenya), mPharma (Ghana), Hello Tractor (Nigeria), and Ampion (Pan-African). The full ranking evaluates funding volume, user growth, social impact, and innovation score.',
      },
    ],
    faqs: [
      {
        question: 'Which African country has the most startups?',
        answer:
          'Nigeria leads with over 3,500 active startups, followed by Kenya (1,200) and South Africa (900). Nigeria attracted 31% of all African venture funding in 2025, totalling $1.3 billion across 147 deals.',
      },
      {
        question: 'What is the biggest African startup?',
        answer:
          'Flutterwave, valued at over $3 billion, is Africa\'s largest startup by valuation. It processes payments across 30+ countries and raised $250 million in its 2023 Series D. Opay ($2 billion) and Wave ($1.7 billion) follow.',
      },
      {
        question: 'How much funding do African startups raise?',
        answer:
          'African startups raised $4.2 billion in 2025 across 633 deals, according to Partech Africa. The average deal size was $6.6 million. Fintech dominated with 48% of total funding, followed by clean energy (14%) and e-commerce (11%).',
      },
    ],
  },
  {
    slug: 'best-beaches-africa-ranked',
    title: 'The 12 Most Beautiful Beaches in Africa, Ranked',
    excerpt:
      'From Zanzibar\'s turquoise waters to Cape Town\'s dramatic coves, here are Africa\'s most stunning coastlines for every type of traveller.',
    category: 'travel',
    author: 'Zara Ahmed',
    authorTitle: 'Travel Editor',
    datePublished: '2026-07-10T08:30:00+00:00',
    dateModified: '2026-10-12T12:00:00+00:00',
    readTime: 9,
    image:
      'https://images.pexels.com/photos/3601425/pexels-photo-3601425.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'au', 'ca', 'za'],
    body: [
      {
        type: 'paragraph',
        text: 'Africa boasts over 30,000 km of coastline, spanning the Mediterranean, Atlantic, and Indian Oceans. The continent\'s beaches rival any in the world — and often surpass them in solitude, water clarity, and dramatic scenery.',
      },
      { type: 'heading', level: 2, text: '1. Nungwi Beach, Zanzibar (Tanzania)' },
      {
        type: 'paragraph',
        text: 'Nungwi\'s powder-white sand and 30°C water temperatures make it the Indian Ocean\'s premier beach. TripAdvisor ranks it among the world\'s top 10 beaches, with 96% of visitors rating it "excellent".',
      },
      { type: 'heading', level: 2, text: '2. Camps Bay, Cape Town (South Africa)' },
      {
        type: 'paragraph',
        text: 'Framed by the Twelve Apostles mountain range, Camps Bay combines a palm-lined promenade with Atlantic rollers. The water is bracing at 14-18°C, but the sunset views are unmatched.',
      },
      { type: 'heading', level: 2, text: '3. Anse Source d\'Argent, Seychelles' },
      {
        type: 'paragraph',
        text: 'Often cited as the most photographed beach on Earth, Anse Source d\'Argent features giant granite boulders, pink sand, and shallow turquoise water. Entry costs 315 SCR (about $23 USD).',
      },
      {
        type: 'paragraph',
        text: 'The list continues with Boulders Beach (penguins!), Praia de Santa Maria (Cape Verde), Diani Beach (Kenya), Skeleton Coast (Namibia), Plage de Sidi Kaouki (Morocco), Bazaruto (Mozambique), Quirimbas (Mozambique), Clifton Beaches (Cape Town), and Tofo Beach (Mozambique).',
      },
    ],
    faqs: [
      {
        question: 'Which African country has the best beaches?',
        answer:
          'Tanzania (Zanzibar) ranks first for beach quality, followed by the Seychelles and Mozambique. Zanzibar\'s Nungwi Beach tops our 2026 ranking with 30°C water, 96% visitor satisfaction, and over 4 km of uninterrupted white sand.',
      },
      {
        question: 'Are African beaches safe for swimming?',
        answer:
          'Most Indian Ocean beaches (Zanzibar, Seychelles, Mozambique, Kenya) are safe for swimming year-round. Atlantic beaches like those in Cape Town are colder and have stronger currents — swim at patrolled beaches only. Always check local rip-tide warnings.',
      },
    ],
  },
  {
    slug: 'top-african-musicians-2026',
    title: 'The 25 Most Influential African Musicians of 2026',
    excerpt:
      'Afrobeats, Amapiano, and Gengetone dominate the global stage. Here are the 25 African artists shaping the sound of 2026.',
    category: 'music',
    author: 'Tendai Moyo',
    authorTitle: 'Music & Culture Writer',
    datePublished: '2026-09-28T10:00:00+00:00',
    dateModified: '2026-10-24T09:00:00+00:00',
    readTime: 11,
    image:
      'https://images.pexels.com/photos/167636/pexels-photo-167636.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'ng', 'za', 'ke'],
    body: [
      {
        type: 'paragraph',
        text: 'African music has never been bigger. Afrobeats generated over $2.5 billion in global streaming revenue in 2025, with Spotify reporting 14 billion annual streams for the genre. Amapiano, born in South African townships, now fills clubs from London to Tokyo.',
      },
      { type: 'heading', level: 2, text: '1. Burna Boy (Nigeria)' },
      {
        type: 'paragraph',
        text: 'The "African Giant" became the first African artist to sell out a US stadium with his 2025 Madison Square Garden show. His album "I Told Them" has 1.8 billion cumulative streams.',
      },
      { type: 'heading', level: 2, text: '2. Tyla (South Africa)' },
      {
        type: 'paragraph',
        text: 'Tyla\'s "Water" hit #1 in 15 countries and won the inaugural Grammy for Best African Music Performance. Her Popiano sound has redefined the global perception of African pop.',
      },
      {
        type: 'quote',
        text: 'African music is not a trend. It is the foundation. The world is finally catching up to what we have always known.',
        attribution: 'Tyla, Grammy Award Winner',
      },
      {
        type: 'paragraph',
        text: 'The top 25 includes Wizkid, Tems, Rema, Asake, Davido, Ayra Starr, Black Coffee, Uncle Waffles, Diamond Platnumz, Sauti Sol, and emerging names like Shallipopi, Odumodublvck, and Tyla Laura. The ranking weighs streaming numbers, chart performance, cultural impact, and global tour data.',
      },
    ],
    faqs: [
      {
        question: 'Who is the most popular African musician in 2026?',
        answer:
          'Burna Boy is the most popular African musician of 2026, with 1.8 billion cumulative album streams, a sold-out Madison Square Garden show, and a Grammy win. He is followed by Wizkid and Tyla in global streaming figures.',
      },
      {
        question: 'What is the most popular music genre in Africa?',
        answer:
          'Afrobeats is the most popular African music genre globally, with 14 billion annual Spotify streams and $2.5 billion in revenue. Amapiano (South Africa) is second globally, while Gengetone (Kenya) and Bongo Flava (Tanzania) dominate East Africa.',
      },
    ],
  },
  {
    slug: 'fastest-growing-tech-hubs-africa',
    title: 'Africa\'s 10 Fastest-Growing Tech Hubs, Ranked',
    excerpt:
      'Lagos, Nairobi, and Cairo lead, but smaller cities are surging. Here are the continent\'s top innovation ecosystems for 2026.',
    category: 'tech',
    author: 'Aisha Bello',
    authorTitle: 'Technology Correspondent',
    datePublished: '2026-06-18T07:30:00+00:00',
    dateModified: '2026-10-05T11:00:00+00:00',
    readTime: 8,
    image:
      'https://images.pexels.com/photos/3861969/pexels-photo-3861969.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'ng', 'za', 'ke'],
    body: [
      {
        type: 'paragraph',
        text: 'Africa\'s tech ecosystem is maturing rapidly. The World Bank reports that 618 active tech hubs now operate across the continent, a 25% increase since 2023. These hubs employ over 500,000 people and have produced 7 unicorns.',
      },
      { type: 'heading', level: 2, text: '1. Lagos, Nigeria — "Yabacon Valley"' },
      {
        type: 'paragraph',
        text: 'Lagos hosts over 450 startups and produced 3 of Africa\'s 7 unicorns. The Yaba district alone houses Flutterwave, Andela, and Paystack. Tech employment grew 34% year-over-year in 2025.',
      },
      { type: 'heading', level: 2, text: '2. Nairobi, Kenya — "Silicon Savannah"' },
      {
        type: 'paragraph',
        text: 'Nairobi pioneered African fintech with M-Pesa and now hosts over 200 startups. The city attracted $420 million in VC funding in 2025, with agritech and clean energy leading.',
      },
      { type: 'heading', level: 2, text: '3. Cape Town, South Africa' },
      {
        type: 'paragraph',
        text: 'Cape Town leads in healthtech and edtech, with 180+ startups. The city\'s lower cost of living and coastal lifestyle attract remote talent from across the continent.',
      },
      {
        type: 'paragraph',
        text: 'The full top 10 includes Cairo (Egypt), Accra (Ghana), Kigali (Rwanda), Dakar (Senegal), Dar es Salaam (Tanzania), Addis Ababa (Ethiopia), and Abidjan (Côte d\'Ivoire). Each offers distinct advantages, from regulatory sandboxes to bilingual talent pools.',
      },
    ],
    faqs: [
      {
        question: 'Which African city is the best for tech startups?',
        answer:
          'Lagos ranks first with 450+ startups and 3 unicorns (Flutterwave, Paystack, Moniepoint). Nairobi is second with 200+ startups and $420 million in 2025 VC funding. Cape Town leads for healthtech and edtech innovation.',
      },
      {
        question: 'How many tech hubs are in Africa?',
        answer:
          'There are 618 active tech hubs in Africa as of 2025, a 25% increase since 2023. Nigeria has the most (85+), followed by South Africa (78) and Kenya (65). These hubs employ over 500,000 people collectively.',
      },
    ],
  },
];

export const ARTICLES: Article[] = [...PILLAR_ARTICLES, ...WILDLIFE_ARTICLES];

export function getArticle(slug: string): Article | undefined {
  return ARTICLES.find((a) => a.slug === slug);
}

export function getArticlesByCategory(categorySlug: string): Article[] {
  return ARTICLES.filter((a) => a.category === categorySlug);
}

export function getArticlesForRegion(region: string): Article[] {
  if (region === 'global') return ARTICLES;
  return ARTICLES.filter((a) => !a.regions || a.regions.includes(region as never));
}
