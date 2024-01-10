<?php

return [

    // Remove points after a while
    'points' => [
        'per_watch_video_as_hero' => env('USER_POINT_PER_WATCH_VIDEO_AS_HERO', 20),
        'per_watch_video_as_non_hero' => env('USER_POINT_PER_WATCH_VIDEO_AS_NON_HERO', 10),
        'per_comment_liked_as_hero' => env('USER_POINT_PER_COMMENT_LIKED_AS_HERO', 10),
        'per_comment_liked_as_non_hero' => env('USER_POINT_PER_COMMENT_LIKED_AS_NON_HERO', 5),
        'per_referrer_as_hero' => env('USER_POINT_PER_REFERRER_AS_HERO', 2000),
        'per_referrer_as_non_hero' => env('USER_POINT_PER_REFERRER_AS_NON_HERO', 1000),
    ],

    'nft_contract_addresses' => [
        'white' => env('NFT_WHITE_CONTRACT_ADDRESS'),
        'black' => env('NFT_BLACK_CONTRACT_ADDRESS'),
    ],

    'max_token_for_watching_video_per_day' => [
        'white_1' => 60,
        'white_1,5' => 90,
        'white_2' => 120,
        'white_2,5' => 150,
        'white_3' => 180,
        'black_1' => 300,
        'black_1,5' => 450,
        'black_2' => 600,
        'black_2,5' => 750,
        'black_3' => 900,
    ],

];
