<?php

namespace App\Listeners;

use App\Events\VideoViewed;
use App\Events\VideoWatched;
use App\Models\UserStatisticsDaily;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VideoWatchedDataForVideoStatisticsDaily
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoWatched $event)
    {
        $user = $event->user;
        $video = $event->video;
        $channel = $video->channel;
        $startTime = $event->startTime;
        $endTime = $event->endTime;
        $duration = $endTime - $startTime;


        // Add +1 to user statistics
        $statistics = VideoStatisticsDaily::firstOrNew([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->watch_time_total += $duration;

        if($user && $user->is_hero){
            $statistics->watch_time_hero += $duration;
        }else{
            $statistics->watch_time_non_hero += $duration;
        }

        $statistics->save();

        return $statistics;
    }
}
