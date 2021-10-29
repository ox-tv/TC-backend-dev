<?php

namespace App\Listeners;

use App\Events\VideoViewed;
use App\Models\UserStatisticsDaily;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class VideoViewedDataForUserStatisticsDaily
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

        if (!$user) {
            return;
        }

        $statistics = UserStatisticsDaily::firstOrNew([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        $statistics->video_views_count_total += 1;

        if($user->is_hero){
            $statistics->video_views_count_as_hero += 1;
        }else{
            $statistics->video_views_count_as_non_hero += 1;
        }

        $statistics->calcPoints();

        $statistics->save();

        return $statistics;
    }
}
