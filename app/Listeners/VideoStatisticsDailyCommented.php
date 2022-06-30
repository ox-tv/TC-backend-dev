<?php

namespace App\Listeners;

use App\Events\VideoCommented;
use App\Events\VideoViewed;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class VideoStatisticsDailyCommented
{
    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoCommented $event)
    {
        $user = $event->user;
        $video = $event->video;
        $channel = $video->channel;

        $statistics = VideoStatisticsDaily::firstOrNew([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->comments_total += 1;

        if($user && $user->is_hero){
            $statistics->comments_hero += 1;
        }else{
            $statistics->comments_non_hero += 1;
        }

        $statistics->save();

        return $statistics;
    }
}
