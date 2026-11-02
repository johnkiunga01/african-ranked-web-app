<?php

return [
    'gemini' => ['key' => env('GEMINI_API_KEY'), 'model' => env('GEMINI_SEO_MODEL', 'gemini-3.5-flash-lite')],
    'openai' => ['key' => env('OPENAI_API_KEY'), 'article_model' => env('OPENAI_ARTICLE_MODEL', 'gpt-5-mini')],
    'openseo' => ['key' => env('OPENSEO_API_KEY'), 'project_id' => env('OPENSEO_PROJECT_ID', '61feee07-a792-4ac8-a675-bf4aa842c222'), 'location_code' => (int) env('OPENSEO_LOCATION_CODE', 2840)],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
