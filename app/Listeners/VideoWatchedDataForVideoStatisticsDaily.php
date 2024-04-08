<?php

namespace App\Listeners;

use App\Events\VideoWatched;
use App\Models\Channel2StatisticsDaily;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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

    public function handle(VideoWatched $event)
    {
        $user = $event->user;
        $video = $event->video;
        $channel = $video->channel;
        $startTime = $event->startTime;
        $endTime = $event->endTime;
        $duration = $endTime - $startTime;


        // Add +1 to user statistics
        /*$statistics = Channel2StatisticsDaily::firstOrNew([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);*/

        $statistics = Cache::remember("Channel2StatisticsDaily_channel{$channel->id}_video{$video->id}_today", Carbon::now()->endOfDay() , function () use ($channel, $video){
            return Channel2StatisticsDaily::firstOrNew([
                'video_id' => $video->id,
                'channel_id' => $channel->id,
                'date' => Carbon::now()->startOfDay(),
            ]);
        });

        $statistics->watch_time_total += $duration;

        if($user && $user->is_hero){
            $statistics->watch_time_hero += $duration;
        }else{
            $statistics->watch_time_non_hero += $duration;
        }

        $statistics->save();

        Cache::put("Channel2StatisticsDaily_channel{$channel->id}_video{$video->id}_today", $statistics, Carbon::now()->endOfDay());

        return $statistics;
    }
}
