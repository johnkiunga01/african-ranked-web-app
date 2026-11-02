<?php

return [
    // Preview responses must not enter search indexes. This does not provide authentication.
    'preview' => (bool) env('APP_PREVIEW', false),
];
