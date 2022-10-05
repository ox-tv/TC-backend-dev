<?php

return [

    'thumbnail_sizes' => [
        ['w' => 800, 'h' => null],
        ['w' => 600, 'h' => null],
        ['w' => 400, 'h' => null],
        ['w' => 256, 'h' => null],
        ['w' => 128, 'h' => null],
        ['w' => 48, 'h' => null],
    ],

    'presign_url_type' => env('PRESIGN_URL_TYPE', 's3'),

];
