<?php

return [
    // Preview responses must not enter search indexes. This does not provide authentication.
    'preview' => (bool) env('APP_PREVIEW', false),
    // Keep the public site fetchable by readers/LLM agents without allowing search indexing.
    // Fail closed outside local/test environments; only enable after custom-domain launch approval.
    'indexing_enabled' => (bool) env('SEO_INDEXING_ENABLED', in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)),
    // Country editions currently list the same articles as the global pages. Keep their
    // home, category, rankings and strategy pages out of search indexes and the sitemap
    // until an edition carries distinct content; article URLs are unaffected.
    'regional_editions_indexable' => (bool) env('SEO_REGIONAL_EDITIONS_INDEXABLE', false),
];
