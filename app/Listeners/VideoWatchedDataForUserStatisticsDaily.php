<?php

namespace App\Listeners;

use App\Events\VideoWatched;
use App\Models\UserStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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

    public function handle(VideoWatched $event)
    {
        if (!$event->user){
            return 0;
        }

        $user = $event->user;
        $video = $event->video;
        $startTime = $event->startTime;
        $endTime = $event->endTime;
        $videoDuration = $video->duration;

        /*
        $watchTimeDuration = DB::table('watch_times')
            ->where('video_id', $video->id)
            ->where('user_id', $user->id)
            ->selectRaw("SUM(end_time - start_time) as duration")
            ->first()->duration?:0;
        */

        $watchTimes = Cache::get("watchtime_user{$user->id}_video{$video->id}");
        //$watchTimes = DB::table('watch_times')->where('video_id', $video->id)->where('user_id', $user->id)->select(["end_time", "start_time"])->get();

        $totalTimes = [];
        foreach ($watchTimes as $watchTime){
            $totalTimes[] = $watchTime->end_time - $watchTime->start_time;
        }

        $watchTimeDuration = array_sum($totalTimes);

        $beforePercent = ($watchTimeDuration - ($endTime - $startTime)) * 100 / $videoDuration;
        $afterPercent = $watchTimeDuration * 100 / $videoDuration;

        if (!($beforePercent < 50 && $afterPercent >= 50)){
            return;
        }

        // Add +1 to user statistics
        $statistics = UserStatisticsDaily::firstOrNew([
            'user_id' => $user->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->video_watch_count_total += 1;

        if($user->is_hero){
            $statistics->video_watch_count_as_hero += 1;
        }else{
            $statistics->video_watch_count_as_non_hero += 1;
        }

        $statistics->calcPoints();

        $statistics->save();

        return $statistics;
    }
}
