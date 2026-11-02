<?php

return [
    'repository_owner' => env('CMS_IMPORT_OWNER_EMAIL'),
    // Legacy JSON is review material, never an implicit production publication source.
    'legacy_preview' => (bool) env('EDITORIAL_LEGACY_PREVIEW', true),
    // Do not advertise email subscriptions until confirmed delivery and unsubscribe are supported.
    'newsletter_enabled' => (bool) env('NEWSLETTER_SIGNUP_ENABLED', false),
    'invitation_hours' => 24,
    'display_timezone' => env('EDITORIAL_TIMEZONE', 'Africa/Nairobi'),
    'admin_daily_ai_limit' => max(20, min(200, (int) env('EDITORIAL_ADMIN_DAILY_AI_LIMIT', 20))),
    'admin_daily_ai_limit_until' => env('EDITORIAL_ADMIN_DAILY_AI_LIMIT_UNTIL'),
];
