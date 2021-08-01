<?php

return [

    'SITE_NAME' => env('SITE_NAME', ''),

    'EMAIL_VERIFICATION_URL' => env('EMAIL_VERIFICATION_URL', ''),
    'PUBLISHER_PANEL_URL' => env('PUBLISHER_PANEL_URL', ''),
    'IMPORT_REQUEST_URL' => env('IMPORT_REQUEST_URL', ''),
    'PASSWORD_RESET_URL' => env('PASSWORD_RESET_URL', ''),

    'notifications' => [
        'keep' => env('NOTIFICATIONS_KEEP', 30),
    ],

    'points' => [
        'to_usd_rate' => env('POINT_TO_USD_RATE', 0.2),
        'per_view' => env('POINTS_PER_VIEW', 0.01),
        'per_like_hero' => env('POINTS_PER_LIKE_HERO', 150),
        'per_like_non_hero' => env('POINTS_PER_LIKE_NON_HERO', 50),
        'per_dislike_hero' => env('POINTS_PER_DISLIKE_HERO', 150),
        'per_dislike_non_hero' => env('POINTS_PER_DISLIKE_NON_HERO', 50),
        'per_subscribe_hero' => env('POINTS_PER_SUBSCRIBE_HERO', 3),
        'per_subscribe_non_hero' => env('POINTS_PER_SUBSCRIBE_NON_HERO', 1),
    ],
];
