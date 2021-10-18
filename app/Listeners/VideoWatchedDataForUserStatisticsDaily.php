<?php

namespace App\Listeners;

use App\Events\VideoViewed;
use App\Events\VideoWatched;
use App\Models\UserStatisticsDaily;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class VideoWatchedDataForUserStatisticsDaily
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
        $startTime = $event->startTime;
        $endTime = $event->endTime;
        $videoDuration = $video->duration;

        $watchTimeDuration = DB::table('watch_times')
            ->where('video_id', $video->id)
            ->where('user_id', $user->id)
            ->selectRaw("SUM(end_time - start_time) as duration")
            ->first()->duration?:0;

        $beforePercent = ($watchTimeDuration - ($endTime - $startTime)) * 100 / $videoDuration;
        $afterPercent = $watchTimeDuration * 100 / $videoDuration;

        if (!($beforePercent < 70 && $afterPercent >= 70)){
            return;
        }

        // Add +1 to user statistics
        $statistics = UserStatisticsDaily::firstOrNew([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        $statistics->video_watch_count_total += 1;

        if($user->is_hero){
            $statistics->video_watch_count_as_hero += 1;
        }else{
            $statistics->video_watch_count_as_non_hero += 1;
        }

        $statistics->save();

        return $statistics;
    }
}
