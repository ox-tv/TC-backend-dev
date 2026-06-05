<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary fake media URLs (offline / broken R2)
    |--------------------------------------------------------------------------
    |
    | When enabled, Video + Channel image URLs are replaced using local files
    | under public/ (and built-in SVGs). Choices are stable per model id.
    | Set MEDIA_PLACEHOLDERS_ENABLED=true in .env; turn off when CDN works again.
    |
    */

    'enabled' => env('MEDIA_PLACEHOLDERS_ENABLED', false),

    /*
    | Directory under public/ containing thumbnail images (*.jpg, *.png, …).
    | Example: drop files into TC-backend/public/media/placeholders/video-thumbnails/
    */
    'thumbnail_directory' => env(
        'MEDIA_PLACEHOLDERS_THUMB_DIR',
        'media/placeholders/video-thumbnails'
    ),

    'thumbnail_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'],

    /*
    | Drop .mp4 / .webm / .mov / .ogg files here — each is served via asset().
    */
    'sample_video_directory' => env(
        'MEDIA_PLACEHOLDERS_SAMPLE_VIDEO_DIR',
        'media/placeholders/sample-videos'
    ),

    /*
    | Optional newline-separated list in public/.
    | Lines: full https URL, absolute path (/media/...), or relative to public (media/...).
    | Lines starting with # are ignored.
    */
    'video_urls_file' => env(
        'MEDIA_PLACEHOLDERS_VIDEO_LIST',
        'media/placeholders/sample-videos.txt'
    ),

    /*
    | Extra video URLs directly in config (mixed with list file entries).
    | Use full URLs or app paths served from public/, e.g.:
    | 'https://storage.googleapis.com/...big-buck-bunny.mp4'
    */
    'video_urls' => array_values(array_filter(array_map(
        'trim',
        explode('|', env('MEDIA_PLACEHOLDERS_VIDEO_URLS', ''))
    ))),

    /*
    | Channel avatar: upload images here OR use dynamic /placeholder/channel/{id}/avatar.svg (first letter).
    */
    'channel_avatar_directory' => env(
        'MEDIA_PLACEHOLDERS_CHANNEL_AVATAR_DIR',
        'media/placeholders/channel-avatars'
    ),

    'channel_cover_directory' => env(
        'MEDIA_PLACEHOLDERS_CHANNEL_COVER_DIR',
        'media/placeholders/channel-covers'
    ),

    'channel_cover_builtin_directory' => env(
        'MEDIA_PLACEHOLDERS_CHANNEL_COVER_BUILTIN',
        'media/placeholders/_builtin/channel-covers'
    ),

];
