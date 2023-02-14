<?php

return [
    'base_url' => env('YI_BASE_URL'),
    'account' => env('YI_ACCOUNT'),
    'auto_import_frequency' => env('YI_AUTO_IMPORT_FREQUENCY', 4),
    'auto_import_excluded_tags_for_cryptocurrency' => env('YI_AUTO_IMPORT_EXCLUDED_TAGS_FOR_CRYPTOCURRENCY', ""),

];
