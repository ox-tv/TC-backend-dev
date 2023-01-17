<?php

namespace App\Listeners;

use App\Events\VideoCreated;
use App\Events\VideoViewed;
use App\Models\ChannelStatisticsDaily;
use App\Models\Video;
use Carbon\Carbon;

class ChannelStatisticsDailyVideoCreated
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoCreated $event)
    {
        $video = $event->video;
        $channel = $video->channel;

        $statistics = ChannelStatisticsDaily::firstOrNew([
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->upload_videos_total += 1;

        if ($video->status == Video::STATUS_PUBLISHED){
            $statistics->published_videos += 1;
        }

        $statistics->save();

        return $statistics;
    }
}
