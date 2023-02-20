<?php

return [

    'SITE_NAME' => env('SITE_NAME', ''),

    'PUBLISHER_PANEL_URL' => env('PUBLISHER_PANEL_URL', ''),

    'MWA_EMAIL_VERIFICATION_URL' => env('MWA_EMAIL_VERIFICATION_URL', ''),
    'PUBLISHER_EMAIL_VERIFICATION_URL' => env('PUBLISHER_EMAIL_VERIFICATION_URL', ''),

    'IMPORT_REQUEST_URL' => env('IMPORT_REQUEST_URL', ''),

    'MWA_PASSWORD_RESET_URL' => env('MWA_PASSWORD_RESET_URL', ''),
    'PUBLISHER_PASSWORD_RESET_URL' => env('PUBLISHER_PASSWORD_RESET_URL', ''),
    'ADMIN_PASSWORD_RESET_URL' => env('ADMIN_PASSWORD_RESET_URL', ''),

    'MWA_ETH_ADDRESS_CONFIRMATION_URL' => env('MWA_ETH_ADDRESS_CONFIRMATION_URL', ''),
    'PUBLISHER_ETH_ADDRESS_CONFIRMATION_URL' => env('PUBLISHER_ETH_ADDRESS_CONFIRMATION_URL', ''),

    'MWA_MAGIC_LOGIN_LINK' => env('MWA_MAGIC_LOGIN_LINK', ''),
    'PUBLISHER_MAGIC_LOGIN_LINK' => env('PUBLISHER_MAGIC_LOGIN_LINK', ''),
    'ADMIN_MAGIC_LOGIN_LINK' => env('ADMIN_MAGIC_LOGIN_LINK', ''),

    'PUBLISHER_ACCOUNT_DELETION_URL' => env('PUBLISHER_ACCOUNT_DELETION_URL', ''),
    'MWA_ACCOUNT_DELETION_URL' => env('MWA_ACCOUNT_DELETION_URL', ''),

    'PUBLISHER_SUPPORT_URL' => env('PUBLISHER_SUPPORT_URL', ''),

    'notifications' => [
        'keep' => env('NOTIFICATIONS_KEEP', 30),
    ],

    // Remove points after a while
    'points' => [
        'to_usd_rate' => env('POINT_TO_USD_RATE', 0.2),
        'per_view' => env('POINTS_PER_VIEW', 0.1),
        'per_like_hero' => env('POINTS_PER_LIKE_HERO', 150),
        'per_like_non_hero' => env('POINTS_PER_LIKE_NON_HERO', 50),
        'per_dislike_hero' => env('POINTS_PER_DISLIKE_HERO', 150),
        'per_dislike_non_hero' => env('POINTS_PER_DISLIKE_NON_HERO', 50),
        'per_subscribe_hero' => env('POINTS_PER_SUBSCRIBE_HERO', 3),
        'per_subscribe_non_hero' => env('POINTS_PER_SUBSCRIBE_NON_HERO', 1),
    ],


    'stable_coins_symbol' => ['usdt', 'usdc', 'busd', 'dai', 'tusd', 'usdp', 'usdd', 'gusd', 'fei', 'usdtc', 'frax', 'usdj', 'lusd']
];
