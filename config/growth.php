<?php

return [
    // Owner-selected first release: 19 verified posts; the master plan contains 100.
    'article_target' => (int) env('LAUNCH_ARTICLE_TARGET', 19),
    'planned_articles' => 100,
    'providers' => [
        'openseo' => ['name' => 'OpenSEO', 'area' => 'SEO research', 'field' => 'Project reference', 'help' => 'Keyword demand, competitor gaps and research briefs. Server-side MCP verification is available above; research jobs and result ingestion remain outstanding.'],
        'search_console' => ['name' => 'Google Search Console', 'area' => 'Search performance', 'field' => 'Property reference', 'help' => 'Manual Search Console access and Pages CSV imports are available above. Automatic syncing and URL inspection remain outstanding.'],
        'ga4' => ['name' => 'Google Analytics 4', 'area' => 'Audience analytics', 'field' => 'Measurement ID', 'help' => 'Plan article engagement, acquisition and conversion reports. Consent controls and the analytics connector must be implemented before collection.'],
        'adsense' => ['name' => 'Display advertising', 'area' => 'Revenue', 'field' => 'Publisher reference', 'help' => 'Plan a network application, consent, ad placement review and revenue reporting. Saving this record does not load ads.'],
        'affiliate' => ['name' => 'Affiliate partnerships', 'area' => 'Revenue', 'field' => 'Partner or programme name', 'help' => 'Evaluate safari, accommodation and travel partners. Approval, disclosures, tracked links and conversion imports are follow-up work.'],
        'newsletter' => ['name' => 'Email platform', 'area' => 'Audience retention', 'field' => 'Provider name', 'help' => 'Plan a permission-based newsletter. Delivery, double opt-in, unsubscribe and provider synchronization are not implemented.'],
    ],
];
