<?php

return [
    'repository_owner' => env('CMS_IMPORT_OWNER_EMAIL'),
    // Legacy JSON is review material, never an implicit production publication source.
    'legacy_preview' => (bool) env('EDITORIAL_LEGACY_PREVIEW', true),
    'invitation_hours' => 24,
    'display_timezone' => env('EDITORIAL_TIMEZONE', 'Africa/Nairobi'),
];
