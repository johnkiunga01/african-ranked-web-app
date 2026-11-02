<?php

return [
    'frontend_dist' => base_path(env('FRONTEND_DIST', '../dist')),
    // Files are supplied by the owner; unpublished pages return 404, never placeholder copy.
    'editorial_directory' => storage_path('app/editorial'),
];
