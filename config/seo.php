<?php

return [
    // Preview responses must not enter search indexes. This does not provide authentication.
    'preview' => (bool) env('APP_PREVIEW', false),
    // Keep the public site fetchable by readers/LLM agents without allowing search indexing.
    // Fail closed outside local/test environments; only enable after custom-domain launch approval.
    'indexing_enabled' => (bool) env('SEO_INDEXING_ENABLED', app()->environment(['local', 'testing'])),
];
