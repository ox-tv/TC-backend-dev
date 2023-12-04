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

    'loyalty' => [
        'per_watch_video_as_hero' => env('USER_POINT_PER_WATCH_VIDEO_AS_HERO', 20),
        'per_watch_video_as_non_hero' => env('USER_POINT_PER_WATCH_VIDEO_AS_NON_HERO', 10),
        'per_comment_liked_as_hero' => env('USER_POINT_PER_COMMENT_LIKED_AS_HERO', 10),
        'per_comment_liked_as_non_hero' => env('USER_POINT_PER_COMMENT_LIKED_AS_NON_HERO', 5),
        'per_referrer_as_hero' => env('USER_POINT_PER_REFERRER_AS_HERO', 2000),
        'per_referrer_as_non_hero' => env('USER_POINT_PER_REFERRER_AS_NON_HERO', 1000),
        'referral' => env('USER_POINT_PER_REFERRER_AS_NON_HERO', 1000),
    ],

    'token' => [
        // as publisher
        'publish_a_media' => 500,
        'answer_a_comment' => 100,
        'referrer_as_publisher' => 1000,

        // as end user
        'referral_via_publisher' => 100,

        'referrer' => 5,
        'referrer_as_hero' => 10,
        'watch_a_video' => 5,
        'watch_a_video_as_hero' => 10,
        'fill_custom_feed' => 25,
        'fill_custom_feed_as_hero' => 50,
        'liked_comment' => 0,
        'liked_comment_as_hero' => 0,
        'buying_yearly_membership' => 5000,
        'buying_yearly_membership_as_hero' => 5000,

    ],

];
