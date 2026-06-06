<?php

namespace App\Console\Commands;

use Amir\Permission\Models\Role;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Language;
use App\Models\Tag;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imports the locally-downloaded sample YouTube content (../sample-videos) into the
 * platform: one publisher user + Channel per source channel, plus its Videos with
 * media, thumbnails, tags, category, duration, counts and published dates.
 *
 * Media: mp4 files are symlinked into the local `videos` disk (avoids duplicating
 * ~1.7GB); thumbnails and channel art are copied. Absolute URLs are written so the
 * media loads regardless of APP_URL.
 *
 * Note: requires MEDIA_PLACEHOLDERS_ENABLED=false in .env for real media to surface.
 */
class ImportSampleContent extends Command
{
    protected $signature = 'sample:import
        {--path= : Path to the sample-videos directory (default ../sample-videos)}
        {--host=http://127.0.0.1:8000 : Base URL used to build media links}
        {--password=password : Password for the created publisher users}
        {--fresh : Delete previously imported sample data first}';

    protected $description = 'Import ../sample-videos channels and videos into the platform.';

    private string $host;

    public function handle(): int
    {
        $base = rtrim($this->option('path') ?: base_path('../sample-videos'), '/');
        $this->host = rtrim($this->option('host'), '/');

        if (!is_dir($base)) {
            $this->error("sample-videos dir not found: $base");
            return 1;
        }

        if ($this->option('fresh')) {
            $this->purge();
        }

        // Storage roots (under storage/app/public, exposed via storage:link).
        $videosRoot = storage_path('app/public/videos');
        $thumbsRoot = storage_path('app/public/videos-thumbnails');
        $chanRoot   = storage_path('app/public/channels');
        foreach ([$videosRoot, $thumbsRoot, $chanRoot] as $d) {
            if (!is_dir($d)) {
                mkdir($d, 0775, true);
            }
        }

        $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;
        $category = Category::where('name', 'LIKE', '%Gaming%')->first()
            ?: Category::where('name', 'LIKE', '%Game%')->first()
            ?: tap(new Category(), function ($c) {
                $c->name = 'Gaming';
                $c->slug = 'gaming';
                $c->status = 1;
                $c->save();
            });
        $language = Language::where('code', 'en')->first();
        $this->info("Using category #{$category->id} '{$category->name}'"
            . ($language ? ", language #{$language->id}" : ''));

        $dirs = array_filter(glob($base . '/*'), fn($p) => is_dir($p) && is_file($p . '/channel.json'));
        if (!$dirs) {
            $this->error('No channel folders with channel.json found.');
            return 1;
        }

        $totalCh = 0;
        $totalVid = 0;
        foreach ($dirs as $dir) {
            $key = basename($dir);
            $meta = json_decode(file_get_contents($dir . '/channel.json'), true) ?: [];
            $this->line("== {$key} ==");

            DB::transaction(function () use ($dir, $key, $meta, $publisherRoleId, $category, $language, $chanRoot, $videosRoot, $thumbsRoot, &$totalCh, &$totalVid) {
                $user = $this->makePublisher($key, $meta, $publisherRoleId);
                $channel = $this->makeChannel($dir, $key, $meta, $user, $language, $chanRoot);
                $totalCh++;
                $n = $this->importVideos($dir, $channel, $user, $category, $language, $videosRoot, $thumbsRoot);
                $totalVid += $n;
                $this->info("   {$channel->name}: {$n} videos (channel #{$channel->id}, owner {$user->email})");
            });
        }

        $this->newLine();
        $this->info("DONE: {$totalCh} channels, {$totalVid} videos imported.");
        $this->warn('If media does not display, set MEDIA_PLACEHOLDERS_ENABLED=false in .env and run: php artisan config:clear');
        return 0;
    }

    private function makePublisher(string $key, array $meta, int $roleId): User
    {
        $email = strtolower($key) . '@sample.oxin.tv';
        $user = User::where('email', $email)->first() ?: new User();
        $user->username = $user->username ?: ($meta['custom_url'] ?: $key);
        $user->email = $email;
        if (!$user->exists) {
            $user->password = bcrypt($this->option('password'));
        }
        $user->role_id = $roleId;
        if (in_array('email_verified_at', $user->getFillable(), true) || true) {
            $user->email_verified_at = $user->email_verified_at ?: now();
        }
        $user->save();
        return $user;
    }

    private function makeChannel(string $dir, string $key, array $meta, User $user, ?Language $language, string $chanRoot): Channel
    {
        $name = $meta['title'] ?: $key;
        $channel = Channel::where('user_id', $user->id)->first() ?: new Channel();
        $channel->user_id = $user->id;
        $channel->name = $name;
        $channel->description = $meta['description'] ?? null;
        $channel->slug = $channel->slug ?: $this->uniqueSlug(Channel::class, $name);
        $channel->url_hash = $channel->url_hash ?: $this->uniqueHash(Channel::class);
        $channel->website = $meta['channel_url'] ?? null;
        if (!empty($meta['custom_url'])) {
            $channel->slogan = '@' . $meta['custom_url'];
        }
        if ($language) {
            $channel->language_id = $language->id;
        }
        $channel->status = Channel::STATUS_PUBLISHED;

        // avatar + cover: copy into channels disk, set absolute URLs
        if (is_file($dir . '/avatar.jpg')) {
            $fn = $channel->slug . '-avatar.jpg';
            copy($dir . '/avatar.jpg', $chanRoot . '/' . $fn);
            $url = $this->host . '/storage/channels/' . $fn;
            $channel->avatar_url = $url;
            $channel->avatar = $url;
        }
        if (is_file($dir . '/cover.jpg')) {
            $fn = $channel->slug . '-cover.jpg';
            copy($dir . '/cover.jpg', $chanRoot . '/' . $fn);
            $url = $this->host . '/storage/channels/' . $fn;
            $channel->cover_url = $url;
            $channel->cover = $url;
        }
        $channel->save();
        return $channel;
    }

    private function importVideos(string $dir, Channel $channel, User $user, Category $category, ?Language $language, string $videosRoot, string $thumbsRoot): int
    {
        $count = 0;
        foreach (glob($dir . '/videos/*.json') as $jsonFile) {
            // skip the raw yt-dlp dumps (*.info.json)
            if (Str::endsWith($jsonFile, '.info.json')) {
                continue;
            }
            $vid = basename($jsonFile, '.json');
            $mp4 = $dir . '/videos/' . $vid . '.mp4';
            if (!is_file($mp4)) {
                continue;
            }
            $d = json_decode(file_get_contents($jsonFile), true) ?: [];

            // symlink the (large) mp4 into the videos disk; copy the thumbnail
            $videoFile = $vid . '.mp4';
            $target = $videosRoot . '/' . $videoFile;
            if (!file_exists($target)) {
                @symlink(realpath($mp4), $target);
            }
            $thumbUrl = null;
            if (is_file($dir . '/videos/' . $vid . '.jpg')) {
                copy($dir . '/videos/' . $vid . '.jpg', $thumbsRoot . '/' . $vid . '.jpg');
                $thumbUrl = $this->host . '/storage/videos-thumbnails/' . $vid . '.jpg';
            }

            $title = $d['title'] ?: $vid;
            $video = Video::where('youtube_link', $d['webpage_url'] ?? '###')
                ->where('channel_id', $channel->id)->first() ?: new Video();
            $video->title = $title;
            $video->slug = $video->slug ?: $this->uniqueSlug(Video::class, $title);
            $video->description = $d['description'] ?? null;
            $video->user_id = $user->id;
            $video->channel_id = $channel->id;
            $video->category_id = $category->id;
            if ($language) {
                $video->language_id = $language->id;
            }
            $video->media_type = Video::MEDIA_TYPE_VIDEO;
            $video->upload_method = Video::UPLOAD_METHOD_YOUTUBE;
            $video->youtube_link = $d['webpage_url'] ?? null;
            $video->file_path = $videoFile;
            $video->file_url = $this->host . '/storage/videos/' . $videoFile;
            if ($thumbUrl) {
                $video->thumbnail = $thumbUrl;
                $video->thumbnail_url = $thumbUrl;
            }
            if (!empty($d['duration_seconds'])) {
                $video->duration = (float) $d['duration_seconds'];
            }
            $video->view_count = (int) ($d['view_count'] ?? 0);
            $video->status = Video::STATUS_PUBLISHED;
            $video->published_at = $this->parseDate($d['published_at'] ?? null);
            $video->save();

            // pivots: category + channel + tags
            $video->categories()->syncWithoutDetaching([$category->id]);
            $video->channels()->syncWithoutDetaching([$channel->id]);
            $this->attachTags($video, $d['tags'] ?? []);

            $count++;
        }
        return $count;
    }

    private function attachTags(Video $video, array $tags): void
    {
        $ids = [];
        foreach (array_slice(array_filter(array_map('trim', $tags)), 0, 12) as $name) {
            $tag = Tag::firstOrCreate(
                ['name' => $name],
                ['status' => 1, 'creation_scope' => Tag::CREATION_SCOPE_IMPORTER]
            );
            $ids[] = $tag->id;
        }
        if ($ids) {
            $video->tags()->syncWithoutDetaching($ids);
        }
    }

    private function parseDate(?string $ymd): ?Carbon
    {
        if (!$ymd) {
            return now();
        }
        try {
            return Carbon::createFromFormat('Ymd', $ymd)->startOfDay();
        } catch (\Throwable $e) {
            return now();
        }
    }

    private function uniqueSlug(string $model, string $title): string
    {
        $bases = Str::slug($title) ?: Str::lower(Str::random(6));
        do {
            $slug = $bases . '-' . Str::lower(Str::random(5));
        } while ($model::where('slug', $slug)->exists());
        return $slug;
    }

    private function uniqueHash(string $model): string
    {
        do {
            $hash = Str::random(12);
        } while ($model::where('url_hash', $hash)->exists());
        return $hash;
    }

    private function purge(): void
    {
        $emails = User::where('email', 'LIKE', '%@sample.oxin.tv')->pluck('id');
        if ($emails->isEmpty()) {
            $this->line('Nothing to purge.');
            return;
        }
        $channelIds = Channel::whereIn('user_id', $emails)->pluck('id');
        $videoIds = Video::whereIn('channel_id', $channelIds)->pluck('id');
        Video::whereIn('id', $videoIds)->each(function ($v) {
            $v->tags()->detach();
            $v->categories()->detach();
            $v->channels()->detach();
            $v->forceDelete();
        });
        Channel::whereIn('id', $channelIds)->each(fn($c) => $c->forceDelete());
        User::whereIn('id', $emails)->each(fn($u) => $u->forceDelete());
        $this->warn("Purged {$channelIds->count()} channels, {$videoIds->count()} videos and their owners.");
    }
}
