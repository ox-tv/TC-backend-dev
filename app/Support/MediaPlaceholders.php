<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

final class MediaPlaceholders
{
    public static function enabled(): bool
    {
        return (bool) config('media_placeholders.enabled');
    }

    public static function videoUrlForModelId(?int $videoId): ?string
    {
        $urls = static::resolvedVideoUrls();
        if ($urls === []) {
            return null;
        }

        return static::pick($urls, $videoId);
    }

    public static function thumbnailUrlForModelId(?int $videoId): string
    {
        $urls = static::imageUrlsForRelativeDirs([config('media_placeholders.thumbnail_directory')]);
        if ($urls !== []) {
            return static::pick($urls, $videoId);
        }

        return asset('media/r2-placeholder.svg');
    }

    public static function channelAvatarUrlForId(?int $channelId): string
    {
        if ($channelId === null || $channelId === 0) {
            return asset('media/r2-placeholder.svg');
        }

        $custom = static::imageUrlsForRelativeDirs([
            config('media_placeholders.channel_avatar_directory'),
        ]);
        if ($custom !== []) {
            return static::pick($custom, $channelId);
        }

        return route('placeholders.channel_avatar', ['channel' => $channelId], true);
    }

    public static function channelCoverUrlForId(?int $channelId): string
    {
        $urls = static::imageUrlsForRelativeDirs([
            config('media_placeholders.channel_cover_directory'),
            config('media_placeholders.channel_cover_builtin_directory'),
        ]);
        if ($urls !== []) {
            return static::pick($urls, $channelId + 7919);
        }

        return asset('media/r2-placeholder.svg');
    }

    /**
     * Mirrors getThumbnails() shape so the front-end keeps the same keys.
     */
    public static function thumbnailVariants(string $canonicalUrl): array
    {
        $result = ['original' => $canonicalUrl];

        foreach (config('upload.thumbnail_sizes') as $size) {
            $key = ($size['w'] ?: 'auto') . '_' . ($size['h'] ?: 'auto');
            $result[$key] = $canonicalUrl;
        }

        return $result;
    }

    private static function pick(array $pool, ?int $seedId): string
    {
        if ($seedId !== null && $seedId !== 0) {
            return $pool[crc32((string) $seedId) % count($pool)];
        }

        return $pool[array_rand($pool)];
    }

    /**
     * @param  array<int, string|null>  $relativeDirs Public-relative paths, e.g. media/placeholders/foo
     * @return array<int, string> asset() URLs, sorted, unique
     */
    private static function imageUrlsForRelativeDirs(array $relativeDirs): array
    {
        $seen = [];
        $out = [];

        foreach ($relativeDirs as $relative) {
            foreach (static::scanOneImageDir($relative) as $url) {
                if (! isset($seen[$url])) {
                    $seen[$url] = true;
                    $out[] = $url;
                }
            }
        }

        sort($out);

        return $out;
    }

    private static function scanOneImageDir(?string $relative): array
    {
        if ($relative === null || $relative === '') {
            return [];
        }

        $dir = public_path(trim($relative, '/'));
        $extOK = array_map('strtolower', config('media_placeholders.thumbnail_extensions'));

        if (! is_dir($dir)) {
            return [];
        }

        $paths = [];
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (! is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext !== '' && in_array($ext, $extOK, true)) {
                $paths[] = $path;
            }
        }
        sort($paths);

        return array_values(array_map(static function (string $abs) {
            $rel = ltrim(str_replace('\\', '/', substr($abs, strlen(public_path()))), '/');

            return asset($rel);
        }, $paths));
    }

    private static function resolvedVideoUrls(): array
    {
        $urls = [];

        foreach (config('media_placeholders.video_urls', []) as $line) {
            $line = trim((string) $line);
            static::collectVideoUrlLine($urls, $line);
        }

        $fileRel = config('media_placeholders.video_urls_file');
        $path = $fileRel ? public_path(trim($fileRel, '/')) : null;
        if ($path !== null && is_file($path)) {
            foreach (File::lines($path) as $line) {
                $line = trim((string) $line);
                if ($line === '' || strncmp($line, '#', 1) === 0) {
                    continue;
                }
                static::collectVideoUrlLine($urls, $line);
            }
        }

        $videosDir = public_path(trim(config('media_placeholders.sample_video_directory'), '/'));
        if (is_dir($videosDir)) {
            foreach (glob($videosDir . DIRECTORY_SEPARATOR . '*.{mp4,webm,mov,ogg}', GLOB_BRACE) ?: [] as $path) {
                if (! is_file($path)) {
                    continue;
                }
                $rel = ltrim(str_replace('\\', '/', substr($path, strlen(public_path()))), '/');
                $urls[] = asset($rel);
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    private static function collectVideoUrlLine(array &$urls, string $line): void
    {
        if ($line === '') {
            return;
        }
        if (preg_match('#^https?://#i', $line)) {
            $urls[] = $line;

            return;
        }
        if (strpos($line, '/') === 0) {
            $urls[] = url($line);

            return;
        }
        $urls[] = asset(trim($line, '/'));
    }
}
