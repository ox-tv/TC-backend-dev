<?php

namespace App\Console\Commands\ModifingDatabaseData;

use App\Models\Channel2StatisticsDaily;
use App\Models\ChannelStatisticsDaily;
use App\Models\PaymentDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MoveOldStatisticsToNewChannelStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:statistics:move-old-to-new-channel-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Channel Statistics
        $channelStatistics = ChannelStatisticsDaily::all();

        foreach ($channelStatistics as $statistic){
            $newStatistics = Channel2StatisticsDaily::firstOrNew([
                'channel_id' => $statistic->channel_id,
                'video_id' => null,
                'date' => $statistic->date,
            ]);

            $newStatistics->subscribers_hero += $statistic->subscribers_hero;
            $newStatistics->subscribers_non_hero += $statistic->subscribers_non_hero;
            $newStatistics->subscribers_total += $statistic->subscribers_total;
            $newStatistics->unsubscribers_hero += $statistic->unsubscribers_hero;
            $newStatistics->unsubscribers_non_hero += $statistic->unsubscribers_non_hero;
            $newStatistics->unsubscribers_total += $statistic->unsubscribers_total;
            $newStatistics->upload_videos_total += $statistic->upload_videos_total;
            $newStatistics->published_videos += $statistic->published_videos;
            $newStatistics->unpublished_videos += $statistic->unpublished_videos;
            $newStatistics->save();
        }

        // Video Statistics
        $videoStatistics = \App\Models\VideoStatisticsDaily::all();

        foreach ($videoStatistics as $statistic){
            $newStatistics = Channel2StatisticsDaily::firstOrNew([
                'channel_id' => $statistic->channel_id,
                'video_id' => $statistic->video_id,
                'date' => $statistic->date,
            ]);

            $newStatistics->views_hero += $statistic->views_hero;
            $newStatistics->views_non_hero += $statistic->views_non_hero;
            $newStatistics->views_total += $statistic->views_total;
            $newStatistics->likes_hero += $statistic->likes_hero;
            $newStatistics->likes_non_hero += $statistic->likes_non_hero;
            $newStatistics->likes_total += $statistic->likes_total;
            $newStatistics->dislikes_hero += $statistic->dislikes_hero;
            $newStatistics->dislikes_non_hero += $statistic->dislikes_non_hero;
            $newStatistics->dislikes_total += $statistic->dislikes_total;
            $newStatistics->comments_hero += $statistic->comments_hero;
            $newStatistics->comments_non_hero += $statistic->comments_non_hero;
            $newStatistics->comments_total += $statistic->comments_total;
            $newStatistics->watch_time_hero += $statistic->watch_time_hero;
            $newStatistics->watch_time_non_hero += $statistic->watch_time_non_hero;
            $newStatistics->watch_time_total += $statistic->watch_time_total;
            $newStatistics->save();
        }

        return 0;
    }
}
