<?php

$measurementId = strtoupper(trim((string) env('GA4_MEASUREMENT_ID', '')));

return [
    // Explicit operator signoff and a valid GA4 ID are required for activation.
    'enabled' => (bool) env('GA4_ENABLED', false)
        && (bool) env('GA4_PRIVACY_NOTICE_APPROVED', false)
        && preg_match('/^G-[A-Z0-9]{5,20}$/D', $measurementId) === 1,
    'measurement_id' => $measurementId,
];
