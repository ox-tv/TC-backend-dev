<?php

return [

    'SITE_NAME' => env('SITE_NAME', ''),

    'EMAIL_VERIFICATION_URL' => env('EMAIL_VERIFICATION_URL', ''),
    'PUBLISHER_PANEL_URL' => env('PUBLISHER_PANEL_URL', ''),
    'IMPORT_REQUEST_URL' => env('IMPORT_REQUEST_URL', ''),
    'PASSWORD_RESET_URL' => env('PASSWORD_RESET_URL', ''),

    'notifications' => [
        'keep' => env('NOTIFICATIONS_KEEP', 30),
    ]
];
