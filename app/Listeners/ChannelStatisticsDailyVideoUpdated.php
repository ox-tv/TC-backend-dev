<?php

namespace App\Listeners;

use App\Events\VideoUpdated;
use App\Events\VideoViewed;
use App\Models\Channel2StatisticsDaily;
use App\Models\ChannelStatisticsDaily;
use App\Models\Video;
use Carbon\Carbon;

class ChannelStatisticsDailyVideoUpdated
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoUpdated $event)
    {
        $oldVideo = $event->oldVideo;
        $video = $event->video;
        $channel = $video->channel;

        $statistics = Channel2StatisticsDaily::firstOrNew([
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        if ($video->status == Video::STATUS_PUBLISHED && $oldVideo->status != Video::STATUS_PUBLISHED){
            $statistics->published_videos += 1;
        }

        if ($video->status != Video::STATUS_PUBLISHED && $oldVideo->status == Video::STATUS_PUBLISHED){
            $statistics->unpublished_videos += 1;
        }

        $statistics->save();

        return $statistics;
    }
}
