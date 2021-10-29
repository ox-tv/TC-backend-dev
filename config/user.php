<?php

return [

    'points' => [
        'per_watch_video_as_hero' => env('USER_POINT_PER_WATCH_VIDEO_AS_HERO', 20),
        'per_watch_video_as_non_hero' => env('USER_POINT_PER_WATCH_VIDEO_AS_NON_HERO', 10),
        'per_comment_liked_as_hero' => env('USER_POINT_PER_COMMENT_LIKED_AS_HERO', 10),
        'per_comment_liked_as_non_hero' => env('USER_POINT_PER_COMMENT_LIKED_AS_NON_HERO', 5),
        'per_referrer_as_hero' => env('USER_POINT_PER_REFERRER_AS_HERO', 2000),
        'per_referrer_as_non_hero' => env('USER_POINT_PER_REFERRER_AS_NON_HERO', 1000),
    ],

];
