<?php

return [
    'additional_hosts' => array_values(array_filter(array_map('trim', explode(',', env('APP_TRUSTED_HOSTS', ''))))),
    // Unknown deployment names fail closed; local/review/testing can enroll without an immediate lockout.
    'require_admin_two_factor' => (bool) env('STAFF_REQUIRE_ADMIN_2FA', ! in_array(env('APP_ENV', 'production'), ['local', 'review', 'testing'], true)),
    'challenge_seconds' => 300,
    'setup_minutes' => 10,
];
