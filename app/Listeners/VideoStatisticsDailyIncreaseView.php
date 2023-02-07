<?php

namespace App\Listeners;

use App\Events\VideoViewed;
use App\Models\Channel2StatisticsDaily;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class VideoStatisticsDailyIncreaseView
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
    public function handle(VideoViewed $event)
    {
        $user = $event->user;
        $video = $event->video;
        $channel = $video->channel;
        $pointsPerView = config('general.points.per_view');

        $statistics = Channel2StatisticsDaily::firstOrNew([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->views_total += 1;

        if($user && $user->is_hero){
            $statistics->views_hero += 1;
        }else{
            $statistics->views_non_hero += 1;
        }

        if ($channel->monetization_qualified_at && $channel->monetization_qualified_at < Carbon::now()){
            $statistics->points += $pointsPerView;
        }

        $statistics->point_details = $statistics->calcPointDetails();

        $statistics->save();

        return $statistics;
    }
}
