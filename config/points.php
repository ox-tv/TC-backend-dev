<?php

return [

    'monetize' => [
        'to_usd_rate' => env('MONETIZE_POINT_TO_USD_RATE', 0.2),
        'per_view_video' => env('MONETIZE_POINTS_PER_VIEW_VIDEO', 0.1),
        'per_like_video_as_hero' => env('MONETIZE_POINTS_PER_LIKE_VIDEO_AS_HERO', 150),
        'per_like_video_as_non_hero' => env('MONETIZE_POINTS_PER_LIKE_VIDEO_AS_NON_HERO', 50),
        'per_dislike_video_as_hero' => env('MONETIZE_POINTS_PER_DISLIKE_VIDEO_AS_HERO', 150),
        'per_dislike_video_as_non_hero' => env('MONETIZE_POINTS_PER_DISLIKE_VIDEO_AS_NON_HERO', 50),
        'per_subscribe_channel_as_hero' => env('MONETIZE_POINTS_PER_SUBSCRIBE_CHANNEL_AS_HERO', 3),
        'per_subscribe_channel_as_non_hero' => env('MONETIZE_POINTS_PER_SUBSCRIBE_CHANNEL_AS_NON_HERO', 1),
        'per_referral' => env('MONETIZE_POINTS_PER_REFERRAL', 1000),
    ],


];
