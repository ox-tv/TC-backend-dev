<?php

namespace App\Listeners;

use App\Events\VideoUploaded;
use App\Events\VideoViewed;
use App\Models\ChannelStatisticsDaily;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ChannelStatisticsDailyVideoUploaded
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoUploaded $event)
    {
        $channel = $event->channel;

        $statistics = ChannelStatisticsDaily::firstOrNew([
            'channel_id' => $channel->id,
            'date' => date('Y-m-d'),
        ]);

        $statistics->upload_videos_total += 1;

        $statistics->save();

        return $statistics;
    }
}
