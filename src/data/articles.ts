import type { Article } from '@/types';
import { WILDLIFE_ARTICLES } from '@/data/articles-wildlife';
import { WILDLIFE_GUIDE_ARTICLES } from '@/data/articles-wildlife-guides';

const PILLAR_ARTICLES: Article[] = [
  {
    slug: 'top-10-safari-destinations-africa-2026',
    title: 'Top 10 Safari Destinations in Africa for 2026',
    excerpt:
      'From the Serengeti\'s great migration to Botswana\'s Okavango Delta, here are ten of the best safari destinations, ranked by wildlife viewing, accessibility, and visitor experience.',
    category: 'wildlife',
    author: 'Amara Okonkwo',
    authorTitle: 'Senior Wildlife Correspondent',
    datePublished: '2026-09-15T08:00:00+00:00',
    dateModified: '2026-11-01T08:00:00+00:00',
    readTime: 10,
    image:
      'https://images.pexels.com/photos/158063/safari-elephants-africa-158063.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'au', 'ca', 'za', 'ke'],
    body: [
      {
        type: 'paragraph',
        text: 'Africa is home to some of the world\'s most rewarding safari experiences. The list below is an editorial ranking based on the reliability of wildlife sightings, ease of access, and the overall visitor experience. It is not a survey, so treat it as a starting point for planning rather than a definitive scorecard.',
      },
      { type: 'heading', level: 2, text: '1. Serengeti National Park, Tanzania' },
      {
        type: 'paragraph',
        text: 'The Serengeti is home to the Great Migration, the continent\'s largest overland wildlife movement, with well over a million wildebeest and hundreds of thousands of zebras following the rains each year. Predators are abundant, and the open plains make sightings easy.',
      },
      {
        type: 'list',
        items: [
          'Best time to visit: June to October for the dry season and river crossings',
          'Nearest major airport: Kilimanjaro International Airport, with road or charter connections',
          'Budget: ranges widely from camping safaris to luxury lodges',
        ],
      },
      { type: 'heading', level: 2, text: '2. Okavango Delta, Botswana' },
      {
        type: 'paragraph',
        text: 'The Okavango Delta is a UNESCO World Heritage Site, where seasonal floodwaters spread across the Kalahari and create islands, lagoons, and channels. Wildlife, including large elephant and buffalo herds, is drawn to the water. Mokoro canoe trips and walking safaris offer a quieter way to see it.',
      },
      { type: 'heading', level: 2, text: '3. Masai Mara, Kenya' },
      {
        type: 'paragraph',
        text: 'The Masai Mara is the northern extension of the Serengeti ecosystem. It is known for strong lion sightings and the Mara River crossings, and it is one of the most accessible destinations on this list from Nairobi.',
      },
      { type: 'heading', level: 2, text: '4. Kruger National Park, South Africa' },
      {
        type: 'paragraph',
        text: 'Kruger is one of Africa\'s largest national parks and supports a very wide range of mammals and birds. Its well-maintained road network and self-drive options make it one of the most accessible safari destinations on the continent, and it is a strong first choice for many visitors.',
      },
      { type: 'heading', level: 2, text: '5. Bwindi Impenetrable National Park, Uganda' },
      {
        type: 'paragraph',
        text: 'Bwindi is home to nearly half of the world\'s remaining mountain gorillas. Gorilla trekking here is a different kind of safari, with a steep, forested hike to a habituated family. Permits are limited and sell out, so book well ahead and confirm current fees with the park authority.',
      },
      {
        type: 'paragraph',
        text: 'Rounding out the top ten are Etosha National Park (Namibia), Chobe National Park (Botswana), South Luangwa (Zambia), Hwange (Zimbabwe), and Amboseli (Kenya). Each offers a distinct experience, from salt-pan game viewing to walking safaris.',
      },
    ],
    faqs: [
      {
        question: 'What is the best safari destination in Africa for first-timers?',
        answer:
          'Kruger National Park in South Africa is a popular first choice. It has well-maintained roads, self-drive options, and a wide range of accommodation. The Masai Mara in Kenya is another strong option for a first trip.',
      },
      {
        question: 'How much does an African safari cost?',
        answer:
          'Costs vary widely with the destination, season, and level of lodging. Budget camping safaris are the least expensive, while luxury lodges cost considerably more. Get current quotes from operators and check park entry fees before booking, since they change.',
      },
      {
        question: 'When is the best time to go on safari in Africa?',
        answer:
          'The dry season, roughly June to October in East and Southern Africa, is popular because vegetation is thinner and wildlife gathers around water. The Serengeti migration is commonly seen from July to October, while the Okavango Delta floods mainly from around May to August.',
      },
      {
        question: 'Which African country has the most wildlife?',
        answer:
          'There is no official ranking that settles this. Tanzania, Kenya, Botswana, and South Africa are all widely regarded for their wildlife, and each offers different species and landscapes.',
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
    dateModified: '2026-11-01T08:00:00+00:00',
    readTime: 9,
    image:
      'https://images.pexels.com/photos/1279330/pexels-photo-1279330.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'ng', 'za'],
    body: [
      {
        type: 'paragraph',
        text: 'African cuisine has gained wider international attention in recent years, with restaurants, cookbooks, and food media helping more people discover dishes from across the continent. This ranking is editorial: it weighs flavour, global recognition, and cultural importance rather than any single measured statistic.',
      },
      { type: 'heading', level: 2, text: '1. Nigerian Cuisine' },
      {
        type: 'paragraph',
        text: 'Jollof rice, a long-grain rice dish simmered in a tomato and pepper base, is one of the most recognised African dishes abroad. Its friendly rivalry with Ghanaian jollof has made it a familiar topic in food conversations across the internet.',
      },
      { type: 'heading', level: 2, text: '2. Ethiopian Cuisine' },
      {
        type: 'paragraph',
        text: 'Ethiopian food centres on injera, a sourdough flatbread made from teff flour, served with shared stews. Teff is naturally gluten-free, and the Orthodox fasting calendar has shaped a rich tradition of vegetarian and vegan dishes.',
      },
      { type: 'heading', level: 2, text: '3. South African Cuisine' },
      {
        type: 'paragraph',
        text: 'South Africa\'s cooking blends indigenous, Malay, Dutch, and Indian influences. Bobotie, bunny chow, and braai (barbecue) culture are central to the national table.',
      },
      { type: 'heading', level: 2, text: '4. Moroccan Cuisine' },
      {
        type: 'paragraph',
        text: 'Tagines, couscous, and mint tea anchor Moroccan cooking. Couscous was inscribed on UNESCO\'s Intangible Cultural Heritage list in 2020 as a shared North African tradition, and Moroccan spice blends such as ras el hanout are known for their complexity.',
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
          'Jollof rice is among the most widely recognised African dishes internationally, and it is especially popular in Nigeria and Ghana. Injera and bunny chow are also widely known outside the continent.',
      },
      {
        question: 'Which African country has the best food?',
        answer:
          'This is a matter of taste. Our ranking considers flavour complexity, global reach, and cultural significance, and Nigeria, Ethiopia, and South Africa each stand out for different reasons.',
      },
      {
        question: 'Is African cuisine spicy?',
        answer:
          'African cuisine varies widely. West African dishes such as Nigerian suya and Ghanaian shito are often spicy, while Ethiopian berbere offers complex heat. North African cooking leans on warming spices rather than chilli heat, and Southern African braai favours salt and smoke.',
      },
    ],
  },
  {
    slug: 'top-african-startups-2026',
    title: 'The Top African Startups to Watch in 2026',
    excerpt:
      'From fintech to agritech, these are some of the most innovative and fast-growing startups across the continent, selected for their impact, reach, and momentum.',
    category: 'business',
    author: 'Kwame Mensah',
    authorTitle: 'Business & Tech Correspondent',
    datePublished: '2026-10-01T07:00:00+00:00',
    dateModified: '2026-11-01T08:00:00+00:00',
    readTime: 10,
    image:
      'https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'ng', 'za', 'ke'],
    body: [
      {
        type: 'paragraph',
        text: 'Fintech has been the engine of much of Africa\'s startup growth, with payments, lending, and savings products reaching millions of people who previously had limited access to banking. Clean energy, logistics, healthtech, and agritech are also gaining ground. The companies below are selected editorially for their impact and momentum.',
      },
      { type: 'heading', level: 2, text: '1. Wave (Senegal): Mobile Money' },
      {
        type: 'paragraph',
        text: 'Wave offers low-cost mobile money transfers and payments in several West African countries, helping people send money and pay for everyday services without a traditional bank account.',
      },
      { type: 'heading', level: 2, text: '2. Moniepoint (Nigeria): Business Banking' },
      {
        type: 'paragraph',
        text: 'Moniepoint provides payment and banking tools for small merchants, a segment that is central to Nigeria\'s economy. Its focus on everyday business transactions has made it one of the country\'s best-known fintechs.',
      },
      { type: 'heading', level: 2, text: '3. M-KOPA (Kenya): Pay-As-You-Go Energy' },
      {
        type: 'paragraph',
        text: 'M-KOPA combines solar equipment with flexible, pay-as-you-go financing, allowing households to own power systems and appliances without a large upfront cost. It operates in several African markets.',
      },
      {
        type: 'paragraph',
        text: 'Other startups worth watching include Flutterwave (Nigeria), Andela (Nigeria and remote), Yellow Card (Pan-African), Gro Intelligence (Kenya), mPharma (Ghana), Hello Tractor (Nigeria), and Ampion (Pan-African).',
      },
    ],
    faqs: [
      {
        question: 'Which African country has the most startups?',
        answer:
          'Nigeria, Kenya, and South Africa are generally considered the continent\'s largest startup ecosystems. Counts depend on how a startup is defined, so there is no single official figure.',
      },
      {
        question: 'What is the biggest African startup?',
        answer:
          'Flutterwave, a payments company that operates across many African countries, is among the best-known and most highly valued African startups. Valuations change over time, so check recent funding reports for current figures.',
      },
      {
        question: 'Why is fintech so important in Africa?',
        answer:
          'Many people have limited access to traditional banks, so mobile payments, savings, and lending apps have reached large numbers of users quickly. Fintech is therefore a major focus for investors and founders.',
      },
    ],
  },
  {
    slug: 'best-beaches-africa-ranked',
    title: 'The Most Beautiful Beaches in Africa, Ranked',
    excerpt:
      'From Zanzibar\'s turquoise waters to Cape Town\'s dramatic coves, here are Africa\'s most stunning coastlines for every type of traveller.',
    category: 'travel',
    author: 'Zara Ahmed',
    authorTitle: 'Travel Editor',
    datePublished: '2026-07-10T08:30:00+00:00',
    dateModified: '2026-11-01T08:00:00+00:00',
    readTime: 8,
    image:
      'https://images.pexels.com/photos/3601425/pexels-photo-3601425.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'au', 'ca', 'za'],
    body: [
      {
        type: 'paragraph',
        text: 'Africa has a long and varied coastline, spanning the Mediterranean, Atlantic, and Indian Oceans. The beaches below are an editorial selection, ranked by scenery, water conditions, and the overall experience rather than by measured visitor data.',
      },
      { type: 'heading', level: 2, text: '1. Nungwi Beach, Zanzibar (Tanzania)' },
      {
        type: 'paragraph',
        text: 'Nungwi is known for its white sand and warm, clear Indian Ocean water. It is a strong choice for swimming, snorkelling, and relaxing, and it sits at the northern tip of Zanzibar.',
      },
      { type: 'heading', level: 2, text: '2. Camps Bay, Cape Town (South Africa)' },
      {
        type: 'paragraph',
        text: 'Framed by the Twelve Apostles mountain range, Camps Bay combines a palm-lined promenade with Atlantic waves. The water is cold, so it suits walkers and sunset-watchers more than swimmers, but the views are hard to beat.',
      },
      { type: 'heading', level: 2, text: '3. Anse Source d\'Argent, Seychelles' },
      {
        type: 'paragraph',
        text: 'Anse Source d\'Argent is famous for its granite boulders, pale sand, and shallow turquoise water. Access to the beach is through a managed entry point, so check current entry rules before you go.',
      },
      {
        type: 'paragraph',
        text: 'The list continues with Boulders Beach (penguins), Praia de Santa Maria (Cape Verde), Diani Beach (Kenya), Skeleton Coast (Namibia), Plage de Sidi Kaouki (Morocco), Bazaruto (Mozambique), Quirimbas (Mozambique), Clifton Beaches (Cape Town), and Tofo Beach (Mozambique).',
      },
    ],
    faqs: [
      {
        question: 'Which African country has the best beaches?',
        answer:
          'Tanzania (Zanzibar), the Seychelles, and Mozambique are among the most frequently recommended destinations for beach holidays. Our ranking favours Nungwi Beach in Zanzibar for its combination of sand and warm water.',
      },
      {
        question: 'Are African beaches safe for swimming?',
        answer:
          'Many Indian Ocean beaches, including those in Zanzibar, the Seychelles, and Mozambique, are popular for swimming. Atlantic beaches such as those near Cape Town are colder and can have strong currents, so swim only at patrolled beaches and check local warnings.',
      },
    ],
  },
  {
    slug: 'top-african-musicians-2026',
    title: 'The Most Influential African Musicians of 2026',
    excerpt:
      'Afrobeats, Amapiano, and Gengetone are reshaping global pop. Here are the African artists shaping the sound of 2026.',
    category: 'music',
    author: 'Tendai Moyo',
    authorTitle: 'Music & Culture Writer',
    datePublished: '2026-09-28T10:00:00+00:00',
    dateModified: '2026-11-01T08:00:00+00:00',
    readTime: 9,
    image:
      'https://images.pexels.com/photos/167636/pexels-photo-167636.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'ng', 'za', 'ke'],
    body: [
      {
        type: 'paragraph',
        text: 'African music has become a major force in global pop. Afrobeats from Nigeria and Ghana, Amapiano from South Africa, and Gengetone from Kenya have all found audiences far beyond their home countries. The artists below are an editorial selection based on their influence on the sound and reach of African music.',
      },
      { type: 'heading', level: 2, text: '1. Burna Boy (Nigeria)' },
      {
        type: 'paragraph',
        text: 'Burna Boy is one of the most recognised Afrobeats artists internationally, known for blending Afrobeats with dancehall, reggae, and hip-hop. His stadium-scale tours have helped carry the genre to new audiences.',
      },
      { type: 'heading', level: 2, text: '2. Tyla (South Africa)' },
      {
        type: 'paragraph',
        text: 'Tyla\'s global hit "Water" brought Amapiano-influenced pop to international charts, and her rise has made South African sounds a visible part of the global pop conversation.',
      },
      {
        type: 'paragraph',
        text: 'The wider list includes Wizkid, Tems, Rema, Asake, Davido, Ayra Starr, Black Coffee, Uncle Waffles, Diamond Platnumz, Sauti Sol, and emerging artists such as Shallipopi and Odumodublvck.',
      },
    ],
    faqs: [
      {
        question: 'Who is the most popular African musician in 2026?',
        answer:
          'Popularity depends on the measure used, such as streams, chart success, or tour attendance. Burna Boy, Wizkid, and Tyla are among the most widely cited names in global charts and international coverage.',
      },
      {
        question: 'What is the most popular music genre in Africa?',
        answer:
          'Afrobeats is one of the most widely listened-to African genres internationally. Amapiano is especially popular in South Africa and across Southern Africa, while Gengetone (Kenya) and Bongo Flava (Tanzania) are major forces in East Africa.',
      },
    ],
  },
  {
    slug: 'fastest-growing-tech-hubs-africa',
    title: 'Africa\'s Fastest-Growing Tech Hubs, Ranked',
    excerpt:
      'Lagos, Nairobi, and Cape Town lead, but smaller cities are surging. Here are the continent\'s top innovation ecosystems for 2026.',
    category: 'tech',
    author: 'Aisha Bello',
    authorTitle: 'Technology Correspondent',
    datePublished: '2026-06-18T07:30:00+00:00',
    dateModified: '2026-11-01T08:00:00+00:00',
    readTime: 8,
    image:
      'https://images.pexels.com/photos/3861969/pexels-photo-3861969.jpeg?auto=compress&cs=tinysrgb&w=1200',
    regions: ['global', 'us', 'uk', 'ng', 'za', 'ke'],
    body: [
      {
        type: 'paragraph',
        text: 'Africa\'s tech ecosystem is spreading beyond a handful of capitals. Hubs, accelerators, and co-working spaces now operate in many cities, and some smaller centres are attracting developers and investors. The ranking below is editorial and reflects ecosystem depth, talent, and momentum.',
      },
      { type: 'heading', level: 2, text: '1. Lagos, Nigeria: "Yabacon Valley"' },
      {
        type: 'paragraph',
        text: 'Lagos is one of the continent\'s largest startup hubs. The Yaba district, nicknamed Yabacon Valley, is home to many well-known companies, including Flutterwave, Andela, and Paystack.',
      },
      { type: 'heading', level: 2, text: '2. Nairobi, Kenya: "Silicon Savannah"' },
      {
        type: 'paragraph',
        text: 'Nairobi pioneered African mobile money through M-Pesa and remains a leading centre for fintech and agritech. Its nickname, Silicon Savannah, reflects its long-standing role in the region\'s tech scene.',
      },
      { type: 'heading', level: 2, text: '3. Cape Town, South Africa' },
      {
        type: 'paragraph',
        text: 'Cape Town has a strong base in healthtech and edtech, supported by universities and a growing community of founders. Its quality of life also attracts remote talent from across the continent.',
      },
      {
        type: 'paragraph',
        text: 'The wider list includes Cairo (Egypt), Accra (Ghana), Kigali (Rwanda), Dakar (Senegal), Dar es Salaam (Tanzania), Addis Ababa (Ethiopia), and Abidjan (Côte d\'Ivoire). Each offers distinct advantages, from regulatory sandboxes to bilingual talent pools.',
      },
    ],
    faqs: [
      {
        question: 'Which African city is the best for tech startups?',
        answer:
          'Lagos, Nairobi, and Cape Town are the most frequently cited tech hubs. Lagos has the largest startup scene in West Africa, Nairobi is a leader in fintech, and Cape Town is known for healthtech and edtech.',
      },
      {
        question: 'How many tech hubs are in Africa?',
        answer:
          'There is no single authoritative count, because hubs vary in size and definition. Most major economies have several, and the number continues to grow.',
      },
    ],
  },
];

export const ARTICLES: Article[] = [
  ...PILLAR_ARTICLES,
  ...WILDLIFE_ARTICLES,
  ...WILDLIFE_GUIDE_ARTICLES,
];

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
