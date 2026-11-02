<?php

return [

    'brand' => [
        'name' => 'Africa Ranked',
        'alternate_name' => 'African Ranked',
        'url' => env('APP_URL', 'https://africaranked.com'),
        // One canonical positioning statement, reused in the About page,
        // Organization schema, llms.txt and the AI agent system prompt.
        'positioning' => 'Africa Ranked is an independent reference site that ranks and compares the best of Africa, '
            .'including destinations, food, music, business and technology, using published methodology, '
            .'cited sources and dated updates. Every ranking shows its criteria, scores and last-verified date.',
    ],

    // Publishing gates enforced by App\Services\PublishingGate
    'gates' => [
        'min_words' => 1300,          // unique body text only (FAQ, key facts, sources excluded)
        'answer_block_min' => 40,
        'answer_block_max' => 60,
        'min_sources' => 3,
        'min_faq' => 3,
        'min_ranking_items' => 5,
        'min_item_blurb_words' => 60,
        'max_similarity' => 0.60,     // shingle Jaccard vs. recent same-category articles
        'placeholder_patterns' => [
            '/\[[^\]]*\]/',           // any [bracketed blank]
            '/\bTBD\b/i',
            '/lorem ipsum/i',
            '/\bXX+\b/',
            '/citation needed/i',
        ],
    ],

    // Days until a published article must be re-verified, by article type.
    'verification_days' => [
        'ranking' => 180,
        'guide' => 365,
        'sponsored' => 90,
        'affiliate' => 90,
    ],

    'editions' => ['global', 'us', 'uk', 'au', 'ca', 'ng', 'za', 'ke'],

    'categories' => [
        'travel' => 'Travel & Destinations',
        'food' => 'Food & Cuisine',
        'music' => 'Music & Culture',
        'business' => 'Business & Startups',
        'tech' => 'Tech & Innovation',
        'wildlife' => 'Wildlife & Safaris',
    ],

    // Search and retrieval bots are always allowed in production.
    // Training bots are a business decision: flip AI_TRAINING_ALLOWED in .env.
    'bots' => [
        'training' => ['GPTBot', 'ClaudeBot', 'Google-Extended', 'CCBot'],
        'allow_training' => (bool) env('AI_TRAINING_ALLOWED', true),
    ],

    'sitemap_page_size' => 40000,
];
