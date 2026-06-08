<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use App\Models\MonetizePoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use MongoDB\BSON\UTCDateTime;

/**
 * Seeds Mongo collections used by publisher dashboard APIs (avoid Eloquent BSON date quirks on newer PHP/mongo ext).
 */
class SeedPublisherDashboardDemo extends Command
{
    protected $signature = 'publisher:dashboard-demo
                            {email? : Publisher email}
                            {--days=90 : Days back from today to fill}
                            {--all : Seed every publisher}';

    protected $description = 'Fill MongoDB demo stats so the publisher dashboard summary and charts look populated.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $to = Carbon::now()->endOfDay();
        $from = Carbon::now()->subDays($days)->startOfDay();

        $emailsArg = $this->argument('email');
        $all = (bool) $this->option('all');

        if ($emailsArg) {
            $users = User::publishers()->where('email', $emailsArg)->get();
            if ($users->isEmpty()) {
                $this->error('No publisher found with that email.');

                return 1;
            }
        } elseif ($all) {
            $users = User::publishers()->orderBy('id')->get();
        } else {
            $this->warn('Pass a publisher email, or use --all.');
            $this->line('Example: php artisan publisher:dashboard-demo conor@oxintv.com');

            return 1;
        }

        foreach ($users as $user) {
            $channel = $user->channel;
            if (!$channel) {
                $slug = 'demo-' . $user->id . '-' . substr(sha1((string) $user->id), 0, 8);
                $channel = Channel::create([
                    'user_id' => $user->id,
                    'name' => ($user->username ?: $user->email) . ' — Demo Channel',
                    'slug' => $slug,
                    'description' => 'Auto-created for local dashboard demo data.',
                    'status' => Channel::STATUS_PUBLISHED,
                ]);
                $this->info('Created channel id ' . $channel->id . ' for ' . $user->email);
            }

            $this->seedForChannel($channel, $from, $to);
            $this->info('Dashboard demo stats written for channel ' . $channel->id . ' (' . $user->email . ').');
        }

        return 0;
    }

    protected function mongoMs(Carbon $c): UTCDateTime
    {
        return new UTCDateTime((int) ($c->unix() * 1000));
    }

    protected function purgeExistingDemo(Channel $channel, Carbon $from, Carbon $to): void
    {
        $g = $this->mongoMs($from->copy()->startOfDay());
        $l = $this->mongoMs($to->copy()->endOfDay());
        $filter = [
            'channel_id' => (int) $channel->id,
            'video_id' => null,
            'date' => ['$gte' => $g, '$lte' => $l],
        ];

        Channel2StatisticsDaily::raw(function ($collection) use ($filter) {
            $collection->deleteMany($filter);
        });

        MonetizePoint::raw(function ($collection) use ($channel, $g, $l) {
            $collection->deleteMany([
                'channel_id' => (int) $channel->id,
                'demo_dashboard' => true,
                'date' => ['$gte' => $g, '$lte' => $l],
            ]);
        });
    }

    protected function seedForChannel(Channel $channel, Carbon $from, Carbon $to): void
    {
        $this->purgeExistingDemo($channel, $from, $to);

        $day = $from->copy()->startOfDay();
        $i = 0;

        while ($day->lte($to)) {
            $wave = sin($i / 5.8) * 0.42 + 0.58;
            $waveSlow = sin($i / 18.0) * 0.22 + 0.78;

            $views = (int) round(6500 * $wave * $waveSlow + 2100 + ($i % 7) * 180);
            $likes = (int) max(35, round(140 * $wave + 48 + ($channel->id % 13)));
            $dislikes = (int) max(0, round(18 * $waveSlow + ($i % 5)));
            $comments = (int) max(12, round(95 * $wave + 35));
            $watchTime = (int) round(185000 * $wave * $waveSlow + 45000);
            $subs = (int) max(8, round(52 * sin($i / 11.5 + 1.7) + 38));
            $unsubs = (int) max(0, min($subs - 3, round(14 * sin($i / 9.2 + 0.9) + 8)));
            $published = (int) max(0, round(6 * $waveSlow + (($i % 4) === 0 ? 2 : 0)));
            $unpublished = (int) max(0, round(1 + ($i % 6 === 0 ? 1 : 0)));
            $uploads = $published + (int) max(0, round(1 + ($i % 5 === 0 ? 1 : 0)));

            $dayStartMs = $this->mongoMs($day->copy()->startOfDay());
            $dayEndMs = $this->mongoMs($day->copy()->endOfDay());

            Channel2StatisticsDaily::raw(function ($collection) use ($channel, $dayStartMs, $views, $likes, $dislikes, $comments, $watchTime, $subs, $unsubs, $published, $unpublished, $uploads) {
                $collection->insertOne([
                    'channel_id' => (int) $channel->id,
                    'video_id' => null,
                    'date' => $dayStartMs,
                    'views_total' => $views,
                    'views_hero' => (int) round($views * 0.22),
                    'views_non_hero' => (int) round($views * 0.78),
                    'likes_total' => $likes,
                    'likes_hero' => (int) round($likes * 0.18),
                    'likes_non_hero' => (int) round($likes * 0.82),
                    'dislikes_total' => $dislikes,
                    'dislikes_hero' => (int) round($dislikes * 0.2),
                    'dislikes_non_hero' => (int) round($dislikes * 0.8),
                    'comments_total' => $comments,
                    'comments_hero' => (int) round($comments * 0.15),
                    'comments_non_hero' => (int) round($comments * 0.85),
                    'watch_time_total' => $watchTime,
                    'watch_time_hero' => (int) round($watchTime * 0.2),
                    'watch_time_non_hero' => (int) round($watchTime * 0.8),
                    'subscribers_total' => $subs,
                    'subscribers_hero' => (int) max(0, round($subs * 0.12)),
                    'subscribers_non_hero' => (int) max(0, round($subs * 0.88)),
                    'unsubscribers_total' => $unsubs,
                    'unsubscribers_hero' => (int) max(0, round($unsubs * 0.1)),
                    'unsubscribers_non_hero' => (int) max(0, round($unsubs * 0.9)),
                    'upload_videos_total' => $uploads,
                    'published_videos' => $published,
                    'unpublished_videos' => $unpublished,
                ]);
            });

            $pointsAmount = (int) max(40, round(420 * $wave + 150 + ($i % 9) * 22));

            MonetizePoint::raw(function ($collection) use ($channel, $dayStartMs, $dayEndMs, $pointsAmount) {
                $collection->insertOne([
                    'channel_id' => (int) $channel->id,
                    'date' => $dayStartMs,
                    'type' => MonetizePoint::TYPE_VIDEO_VIEWED,
                    'amount' => $pointsAmount,
                    'activated_at' => $dayEndMs,
                    'demo_dashboard' => true,
                ]);
            });

            $day->addDay();
            $i++;
        }
    }
}
